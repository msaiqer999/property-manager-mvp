<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(string $action, Model $subject, ?string $description = null): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        ActivityLog::create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'description' => $description,
        ]);
    }
}
