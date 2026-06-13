<?php

namespace App\Http\Controllers;

class ActivityLogController extends Controller
{
    public function __invoke()
    {
        $logs = auth()->user()->organization->activityLogs()->with('user')->latest()->paginate(30);

        return view('activity-logs.index', compact('logs'));
    }
}
