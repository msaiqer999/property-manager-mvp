<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UnitDocumentController extends Controller
{
    public function store(Request $request, Unit $unit)
    {
        Gate::authorize('update', $unit);

        $unit->loadMissing('building');
        $organizationId = (int) $unit->building->organization_id;

        if ($organizationId !== (int) auth()->user()->organization_id) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(UnitDocument::CATEGORIES)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'document' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,webp'],
        ]);

        $file = $request->file('document');
        $prefix = $this->storagePrefix($organizationId, (int) $unit->id);
        $disk = $this->unitDocumentsDisk();
        $path = $file->store($prefix, $disk);

        UnitDocument::create([
            'organization_id' => $organizationId,
            'unit_id' => $unit->id,
            'uploaded_by' => auth()->id(),
            'title' => $data['title'],
            'category' => $data['category'],
            'notes' => $data['notes'] ?? null,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return redirect()
            ->route('units.show', $unit)
            ->with('success', __('unit_documents.messages.uploaded'));
    }

    public function download(UnitDocument $unitDocument)
    {
        $unitDocument->loadMissing('unit.building');

        Gate::authorize('view', $unitDocument->unit);

        if ((int) $unitDocument->organization_id !== (int) auth()->user()->organization_id) {
            abort(403);
        }

        $path = $this->validatedPrivatePath(
            $unitDocument->path,
            $this->storagePrefix((int) $unitDocument->organization_id, (int) $unitDocument->unit_id)
        );

        $disk = $unitDocument->disk ?: $this->unitDocumentsDisk();

        if (! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        return Storage::disk($disk)->download($path, $this->safeDownloadName($unitDocument), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function unitDocumentsDisk(): string
    {
        $disk = trim((string) config('filesystems.unit_documents_disk', 'local'));

        return $disk !== '' ? $disk : 'local';
    }

    private function storagePrefix(int $organizationId, int $unitId): string
    {
        return "unit-documents/{$organizationId}/{$unitId}";
    }

    private function validatedPrivatePath(?string $storedPath, string $prefix): string
    {
        $rawPath = trim((string) $storedPath);

        if ($rawPath === ''
            || str_contains($rawPath, '..')
            || str_starts_with($rawPath, '/')
            || preg_match('/^[A-Za-z]:[\/\\\\]/', $rawPath)
            || ! str_starts_with($rawPath, $prefix.'/')) {
            abort(404);
        }

        return $rawPath;
    }

    private function safeDownloadName(UnitDocument $unitDocument): string
    {
        $extension = pathinfo($unitDocument->path, PATHINFO_EXTENSION);
        $extension = preg_match('/^[A-Za-z0-9]{1,10}$/', $extension) ? strtolower($extension) : 'bin';
        $baseName = Str::slug($unitDocument->title) ?: $unitDocument->category ?: 'unit-document';

        return "{$baseName}-{$unitDocument->id}.{$extension}";
    }
}
