@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-md rounded border bg-white p-5 shadow-sm">
    <h1 class="mb-4 text-xl font-semibold">Choose new password</h1>
    <form method="post" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label class="block text-sm font-medium">Email <input name="email" type="email" value="{{ old('email', $email) }}" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <label class="block text-sm font-medium">Password <input name="password" type="password" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <label class="block text-sm font-medium">Confirm password <input name="password_confirmation" type="password" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <button class="tap-target w-full rounded bg-slate-900 px-4 text-white">Reset password</button>
    </form>
</div>
@endsection
