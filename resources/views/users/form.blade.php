@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-xl font-semibold">{{ $user->exists ? __('users.edit') : __('users.invite') }}</h1>
<form method="post" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="max-w-xl space-y-4 rounded border bg-brand-surface p-4 shadow-sm">
@csrf @if($user->exists) @method('put') @endif
<label class="block text-sm font-medium">{{ __('users.fields.name') }} <input name="name" value="{{ old('name', $user->name) }}" class="tap-target mt-1 w-full rounded border p-2" required></label>
<label class="block text-sm font-medium">{{ __('users.fields.email') }} <input name="email" type="email" value="{{ old('email', $user->email) }}" class="tap-target mt-1 w-full rounded border p-2" required></label>
<label class="block text-sm font-medium">{{ __('users.fields.role') }} <select name="role" class="form-select-safe tap-target mt-1 w-full rounded border p-2">@foreach(['owner','manager','accountant','caretaker'] as $role)<option value="{{ $role }}" @selected(old('role', optional($user->role)->value)==$role)>{{ __('users.roles.'.$role) }}</option>@endforeach</select></label>
<label class="block text-sm font-medium">{{ __('users.fields.password') }} <input name="password" type="password" class="tap-target mt-1 w-full rounded border p-2" @required(! $user->exists)></label>
<label class="block text-sm font-medium">{{ __('users.fields.password_confirmation') }} <input name="password_confirmation" type="password" class="tap-target mt-1 w-full rounded border p-2" @required(! $user->exists)></label>
<button class="tap-target w-full rounded bg-brand-primary px-4 text-white sm:w-auto">{{ __('app.actions.save') }}</button>
</form>
@endsection
