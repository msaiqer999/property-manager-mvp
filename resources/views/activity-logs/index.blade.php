@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-xl font-semibold">Activity log</h1>
<x-table min-width="min-w-[48rem]"><tbody>@foreach($logs as $log)<tr class="border-t"><td class="p-4 whitespace-nowrap">{{ $log->created_at }}</td><td class="p-4 font-medium">{{ $log->user?->name }}</td><td class="p-4 whitespace-nowrap">{{ $log->action }}</td><td class="p-4 min-w-64">{{ $log->description }}</td></tr>@endforeach</tbody></x-table>
{{ $logs->links() }}
@endsection
