<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UserController extends Controller
{
    use ScopesOrganization;

    public function index()
    {
        abort_unless(auth()->user()->role->value === 'owner', 403);
        Gate::authorize('viewAny', User::class);
        return view('users.index', ['users' => User::where('organization_id', $this->organizationId())->orderBy('name')->get()]);
    }

    public function create()
    {
        abort_unless(auth()->user()->role->value === 'owner', 403);
        Gate::authorize('create', User::class);
        return view('users.form', ['user' => new User()]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->role->value === 'owner', 403);
        Gate::authorize('create', User::class);
        $data = $this->validated($request);
        $data['organization_id'] = $this->organizationId();
        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return redirect()->route('users.index');
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->role->value === 'owner' && $user->organization_id === $this->organizationId(), 403);
        Gate::authorize('update', $user);
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->role->value === 'owner' && $user->organization_id === $this->organizationId(), 403);
        Gate::authorize('update', $user);
        $data = $this->validated($request, true);
        abort_if($this->wouldDemoteLastOwner($user, $data['role']), 422, 'A workspace must have at least one active owner.');

        $oldRole = $user->role->value;

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);

        if ($oldRole !== $user->role->value) {
            app(ActivityLogger::class)->log('user.role_changed', $user, "Role changed from {$oldRole} to {$user->role->value}.");
        }

        return redirect()->route('users.index');
    }

    public function deactivate(User $user)
    {
        abort_unless(auth()->user()->role->value === 'owner' && $user->organization_id === $this->organizationId(), 403);
        Gate::authorize('deactivate', $user);
        abort_if($this->isLastActiveOwner($user), 422, 'A workspace must have at least one active owner.');

        if ($user->is_active) {
            $user->update(['is_active' => false]);
            app(ActivityLogger::class)->log('user.deactivated', $user, 'User access was deactivated.');
        }

        return redirect()->route('users.index');
    }

    public function reactivate(User $user)
    {
        abort_unless(auth()->user()->role->value === 'owner' && $user->organization_id === $this->organizationId(), 403);
        Gate::authorize('reactivate', $user);

        if (! $user->is_active) {
            $user->update(['is_active' => true]);
            app(ActivityLogger::class)->log('user.reactivated', $user, 'User access was reactivated.');
        }

        return redirect()->route('users.index');
    }

    private function validated(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($request->route('user')),
            ],
            'role' => ['required', new Enum(Role::class)],
            'password' => [$updating ? 'nullable' : 'required', 'confirmed', 'min:8'],
        ]);
    }

    private function wouldDemoteLastOwner(User $user, string $newRole): bool
    {
        return $user->role === Role::Owner
            && $newRole !== Role::Owner->value
            && $this->isLastActiveOwner($user);
    }

    private function isLastActiveOwner(User $user): bool
    {
        if ($user->role !== Role::Owner || ! $user->is_active) {
            return false;
        }

        return User::where('organization_id', $user->organization_id)
            ->where('role', Role::Owner->value)
            ->where('is_active', true)
            ->count() === 1;
    }
}
