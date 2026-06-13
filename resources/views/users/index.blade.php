@extends('layouts.app')
@section('content')
<div class="mb-4 grid gap-3 sm:flex sm:items-center sm:justify-between"><h1 class="text-xl font-semibold">Users</h1><a class="tap-target flex items-center justify-center rounded bg-slate-900 px-4 text-sm font-medium text-white" href="{{ route('users.create') }}">Invite user</a></div>
<x-table><tbody>@foreach($users as $user)<tr class="border-t"><td class="p-4 font-medium">{{ $user->name }}</td><td class="p-4">{{ $user->email }}</td><td class="p-4"><span class="rounded bg-slate-100 px-2 py-1 text-xs">{{ $user->role->value }}</span></td><td class="p-4"><a class="tap-target inline-flex items-center rounded border px-3 text-slate-700" href="{{ route('users.edit', $user) }}">Edit</a></td></tr>@endforeach</tbody></x-table>
@endsection
