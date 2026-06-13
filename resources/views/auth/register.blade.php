@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-md rounded border bg-white p-5 shadow-sm">
    <h1 class="mb-4 text-xl font-semibold">Create owner account</h1>
    <form method="post" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <label class="block text-sm font-medium">Organization <input name="organization_name" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <label class="block text-sm font-medium">Name <input name="name" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <label class="block text-sm font-medium">Email <input name="email" type="email" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <label class="block text-sm font-medium">Password <input name="password" type="password" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <label class="block text-sm font-medium">Confirm password <input name="password_confirmation" type="password" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <button class="tap-target w-full rounded bg-slate-900 px-4 text-white">Register</button>
    </form>
</div>
@endsection
