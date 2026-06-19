<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Tenant;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TenantController extends Controller
{
    use ScopesOrganization;

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Tenant::class);

        $tenants = Tenant::where('organization_id', $this->organizationId())
            ->when($request->search, fn ($q, $s) => $q->where(fn ($sub) => $sub->where('full_name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")))
            ->latest()
            ->paginate(15);

        return view('tenants.index', compact('tenants'));
    }

    public function create() { Gate::authorize('create', Tenant::class); return view('tenants.form', ['tenant' => new Tenant()]); }

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
        return view('tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant)
    {
        Gate::authorize('update', $tenant);
        return view('tenants.form', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant, ActivityLogger $logger)
    {
        Gate::authorize('update', $tenant);
        $tenant->update($this->validated($request));
        $logger->log('tenant.updated', $tenant);
        return redirect()->route('tenants.show', $tenant);
    }

    public function destroy(Tenant $tenant)
    {
        Gate::authorize('delete', $tenant);
        $tenant->delete();
        return redirect()->route('tenants.index');
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
