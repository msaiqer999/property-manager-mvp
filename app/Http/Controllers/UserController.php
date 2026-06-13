<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UserController extends Controller
{
    use ScopesOrganization;

    public function index()
    {
        abort_unless(auth()->user()->role->value === 'owner', 403);
        return view('users.index', ['users' => User::where('organization_id', $this->organizationId())->orderBy('name')->get()]);
    }

    public function create()
    {
        abort_unless(auth()->user()->role->value === 'owner', 403);
        return view('users.form', ['user' => new User()]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->role->value === 'owner', 403);
        $data = $this->validated($request);
        $data['organization_id'] = $this->organizationId();
        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return redirect()->route('users.index');
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->role->value === 'owner' && $user->organization_id === $this->organizationId(), 403);
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->role->value === 'owner' && $user->organization_id === $this->organizationId(), 403);
        $data = $this->validated($request, true);
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);
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
}
