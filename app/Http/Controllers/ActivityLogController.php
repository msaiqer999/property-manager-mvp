<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Gate;

class ActivityLogController extends Controller
{
    public function __invoke()
    {
        Gate::authorize('viewAny', ActivityLog::class);

        $logs = auth()->user()->organization->activityLogs()->with('user')->latest()->paginate(30);

        return view('activity-logs.index', compact('logs'));
    }
}
