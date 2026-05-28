<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index()
    {
        $logs = ActivityLog::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        return view('activity-logs.index', compact('logs'));
    }
}
