<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Throwable;

class ActivityLogger
{
    public static function log(string $action, ?string $module = null, ?Model $subject = null, array $properties = [], ?Request $request = null): void
    {
        $request ??= request();

        try {
            ActivityLog::query()->create([
                'user_id' => $request->user()?->id,
                'action' => $action,
                'module' => $module,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'properties' => $properties,
            ]);
        } catch (Throwable) {
        }
    }
}
