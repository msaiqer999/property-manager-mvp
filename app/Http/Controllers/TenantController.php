<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Tenant;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TenantController extends Controller
{
    use ScopesOrganization;

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Tenant::class);

        $lifecycle = $this->lifecycleFilter($request);

        $tenants = Tenant::where('organization_id', $this->organizationId())
            ->when($lifecycle === 'active', fn ($q) => $q->notArchived())
            ->when($lifecycle === 'archived', fn ($q) => $q->onlyArchived())
            ->when($request->search, fn ($q, $s) => $q->where(fn ($sub) => $sub->where('full_name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('tenants.index', compact('tenants', 'lifecycle'));
    }

    public function create()
    {
        Gate::authorize('create', Tenant::class);

        return view('tenants.form', ['tenant' => new Tenant]);
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        Gate::authorize('create', Tenant::class);
        $tenant = Tenant::create($this->validated($request) + ['organization_id' => $this->organizationId()]);
        $logger->log('tenant.created', $tenant);

        return redirect()->route('tenants.show', $tenant);
    }

    public function show(Tenant $tenant)
    {
        Gate::authorize('view', $tenant);
        $tenant->load('archivedBy', 'contracts');

        return view('tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant)
    {
        Gate::authorize('update', $tenant);
        $this->ensureTenantNotArchived($tenant);

        return view('tenants.form', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant, ActivityLogger $logger)
    {
        Gate::authorize('update', $tenant);
        $this->ensureTenantNotArchived($tenant);

        $tenant->update($this->validated($request));
        $logger->log('tenant.updated', $tenant);

        return redirect()->route('tenants.show', $tenant);
    }

    public function destroy(Tenant $tenant, ActivityLogger $logger)
    {
        Gate::authorize('delete', $tenant);

        if ($tenant->archived_at !== null) {
            abort(422, __('tenants.lifecycle.cannot_delete_archived'));
        }

        if ($tenant->contracts()->exists()) {
            abort(422, __('tenants.lifecycle.cannot_delete_with_contracts'));
        }

        $tenant->delete();
        $logger->log('tenant.deleted', $tenant);

        return redirect()->route('tenants.index');
    }

    public function archiveTenant(Request $request, Tenant $tenant, ActivityLogger $logger)
    {
        Gate::authorize('archiveTenant', $tenant);
        $this->ensureTenantHasNoCurrentOrFutureActiveContract($tenant);

        if ($tenant->archived_at !== null) {
            abort(422, __('tenants.lifecycle.already_archived'));
        }

        $reason = $this->archiveReason($request);
        $now = now();

        DB::transaction(function () use ($tenant, $logger, $reason, $now) {
            $updated = Tenant::whereKey($tenant->id)
                ->where('organization_id', $this->organizationId())
                ->whereNull('archived_at')
                ->update([
                    'archived_at' => $now,
                    'archived_by' => auth()->id(),
                    'archive_reason' => $reason,
                ]);

            if ($updated !== 1) {
                abort(422, __('tenants.lifecycle.already_archived'));
            }

            $tenant->refresh();
            $logger->log('tenant.archived', $tenant, $reason);
        });

        return redirect()->route('tenants.show', $tenant);
    }

    private function lifecycleFilter(Request $request): string
    {
        $lifecycle = $request->query('lifecycle', 'active');

        return in_array($lifecycle, ['active', 'archived', 'all'], true) ? $lifecycle : 'active';
    }

    private function ensureTenantNotArchived(Tenant $tenant): void
    {
        if ($tenant->archived_at !== null) {
            abort(422, __('tenants.lifecycle.cannot_edit_archived'));
        }
    }

    private function ensureTenantHasNoCurrentOrFutureActiveContract(Tenant $tenant): void
    {
        if ($tenant->contracts()
            ->where('organization_id', $this->organizationId())
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->exists()) {
            abort(422, __('tenants.lifecycle.cannot_archive_with_current_contract'));
        }
    }

    private function archiveReason(Request $request): string
    {
        $data = $request->validate([
            'archive_reason' => ['required', 'string', 'max:1000'],
        ], [], __('tenants.attributes'));

        $reason = trim(preg_replace('/\s+/u', ' ', $data['archive_reason']));

        if ($reason === '') {
            throw ValidationException::withMessages([
                'archive_reason' => __('validation.required', ['attribute' => __('tenants.attributes.archive_reason')]),
            ]);
        }

        return $reason;
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:100'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
