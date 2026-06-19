<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        $sessions = DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->select('sessions.*', 'users.name as user_name', 'users.email as user_email')
            ->orderByDesc('last_activity')
            ->get();

        return view('sessions.index', [
            'sessions' => $sessions,
            'currentSessionId' => $request->session()->getId(),
        ]);
    }

    public function destroy(Request $request, string $session): RedirectResponse
    {
        if ($session === $request->session()->getId()) {
            return redirect()->route('sessions.index')->with('error', 'Session aktif tidak dapat dihapus dari halaman ini.');
        }

        DB::table('sessions')->where('id', $session)->delete();
        ActivityLogger::log('delete', 'session', null, ['session_id' => $session], $request);

        return redirect()->route('sessions.index')->with('status', 'Session berhasil dihapus.');
    }
}
