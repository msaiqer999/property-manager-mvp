@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-xl font-semibold">{{ __('activity_logs.title') }}</h1>
<x-table min-width="min-w-[48rem]"><tbody>@foreach($logs as $log)<tr class="border-t"><td class="p-4 whitespace-nowrap"><bdi dir="ltr">{{ $log->created_at }}</bdi></td><td class="p-4 font-medium">{{ $log->user?->name }}</td><td class="p-4 whitespace-nowrap">@php($actionKey = 'activity_logs.actions.'.$log->action) @if(\Illuminate\Support\Facades\Lang::has($actionKey)){{ __($actionKey) }}@else<bdi dir="ltr">{{ $log->action }}</bdi>@endif</td><td class="p-4 min-w-64">{{ $log->description }}</td></tr>@endforeach</tbody></x-table>
{{ $logs->links() }}
@endsection
