@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-md rounded border bg-white p-5 shadow-sm">
    <h1 class="mb-4 text-xl font-semibold">Reset password</h1>
    @if(session('status'))<p class="mb-4 rounded bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</p>@endif
    <form method="post" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <label class="block text-sm font-medium">Email <input name="email" type="email" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <button class="tap-target w-full rounded bg-slate-900 px-4 text-white">Send reset link</button>
    </form>
</div>
@endsection
