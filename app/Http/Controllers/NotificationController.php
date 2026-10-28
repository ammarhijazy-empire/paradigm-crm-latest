<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('notifications.index', ['items' => $request->user()->notifications()->paginate(40)]);
    }

    public function open(Request $request, string $id)
    {
        $n = $request->user()->notifications()->findOrFail($id);
        $n->markAsRead();
        $url = $n->data['url'] ?? route('dashboard');
        return redirect()->to(str_starts_with($url, url('/')) ? $url : route('dashboard'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return back();
    }

    public function subscribe(Request $request)
    {
        $d = $request->validate(['endpoint' => ['required', 'url', 'max:500'], 'keys.p256dh' => ['required', 'string'], 'keys.auth' => ['required', 'string']]);
        $request->user()->updatePushSubscription($d['endpoint'], $d['keys']['p256dh'], $d['keys']['auth']);
        return response()->json(['ok' => true]);
    }
}
