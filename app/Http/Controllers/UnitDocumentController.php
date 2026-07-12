<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitDocument;
use App\Support\PrivateDocumentStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class UnitDocumentController extends Controller
{
    public function store(Request $request, Unit $unit, PrivateDocumentStorage $documents)
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
            'document' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,webp', 'extensions:pdf,jpg,jpeg,png,webp'],
        ]);

        $file = $request->file('document');
        $uploaded = $documents->store(
            $file,
            $documents->unitDocumentPrefix($organizationId, (int) $unit->id)
        );

        try {
            UnitDocument::create([
                'organization_id' => $organizationId,
                'unit_id' => $unit->id,
                'uploaded_by' => auth()->id(),
                'title' => $data['title'],
                'category' => $data['category'],
                'notes' => $data['notes'] ?? null,
                'disk' => $uploaded['disk'],
                'path' => $uploaded['path'],
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        } catch (Throwable $exception) {
            $documents->delete($uploaded['disk'], $uploaded['path']);

            throw $exception;
        }

        return redirect()
            ->route('units.show', $unit)
            ->with('success', __('unit_documents.messages.uploaded'));
    }

    public function download(UnitDocument $unitDocument, PrivateDocumentStorage $documents)
    {
        $unitDocument->loadMissing('unit.building');

        Gate::authorize('view', $unitDocument->unit);

        if ((int) $unitDocument->organization_id !== (int) auth()->user()->organization_id) {
            abort(403);
        }

        $path = $this->validatedPrivatePath(
            $unitDocument->path,
            $documents,
            (int) $unitDocument->organization_id,
            (int) $unitDocument->unit_id
        );

        $disk = $documents->legacyDisk($unitDocument->disk);

        if (! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        return Storage::disk($disk)->download($path, $this->safeDownloadName($unitDocument), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(UnitDocument $unitDocument, PrivateDocumentStorage $documents)
    {
        $unitDocument->loadMissing('unit.building');

        Gate::authorize('update', $unitDocument->unit);

        if ((int) $unitDocument->organization_id !== (int) auth()->user()->organization_id) {
            abort(403);
        }

        $path = $this->validatedPrivatePath(
            $unitDocument->path,
            $documents,
            (int) $unitDocument->organization_id,
            (int) $unitDocument->unit_id
        );

        $disk = $documents->legacyDisk($unitDocument->disk);

        $documents->delete($disk, $path);

        $unit = $unitDocument->unit;
        $unitDocument->delete();

        return redirect()
            ->route('units.show', $unit)
            ->with('success', __('unit_documents.messages.deleted'));
    }

    private function validatedPrivatePath(?string $storedPath, PrivateDocumentStorage $documents, int $organizationId, int $unitId): string
    {
        return $documents->validatePath($storedPath, [
            $documents->unitDocumentPrefix($organizationId, $unitId),
            $documents->legacyUnitDocumentPrefix($organizationId, $unitId),
        ]);
    }

    private function safeDownloadName(UnitDocument $unitDocument): string
    {
        $extension = pathinfo($unitDocument->path, PATHINFO_EXTENSION);
        $extension = preg_match('/^[A-Za-z0-9]{1,10}$/', $extension) ? strtolower($extension) : 'bin';
        $baseName = Str::slug($unitDocument->title) ?: $unitDocument->category ?: 'unit-document';

        return "{$baseName}-{$unitDocument->id}.{$extension}";
    }
}
