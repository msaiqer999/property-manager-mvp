@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-xl font-semibold">{{ $user->exists ? 'Edit user' : 'Invite user' }}</h1>
<form method="post" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="max-w-xl space-y-4 rounded border bg-white p-4 shadow-sm">
@csrf @if($user->exists) @method('put') @endif
<label class="block text-sm font-medium">Name <input name="name" value="{{ old('name', $user->name) }}" class="tap-target mt-1 w-full rounded border p-2" required></label>
<label class="block text-sm font-medium">Email <input name="email" type="email" value="{{ old('email', $user->email) }}" class="tap-target mt-1 w-full rounded border p-2" required></label>
<label class="block text-sm font-medium">Role <select name="role" class="tap-target mt-1 w-full rounded border p-2">@foreach(['owner','manager','accountant','caretaker'] as $role)<option @selected(old('role', optional($user->role)->value)==$role)>{{ $role }}</option>@endforeach</select></label>
<label class="block text-sm font-medium">Password <input name="password" type="password" class="tap-target mt-1 w-full rounded border p-2" @required(! $user->exists)></label>
<label class="block text-sm font-medium">Confirm password <input name="password_confirmation" type="password" class="tap-target mt-1 w-full rounded border p-2" @required(! $user->exists)></label>
<button class="tap-target w-full rounded bg-slate-900 px-4 text-white sm:w-auto">Save</button>
</form>
@endsection
