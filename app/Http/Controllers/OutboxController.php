<?php

namespace App\Http\Controllers;

use App\Jobs\SendOutboxEmail;
use App\Models\EmailOutbox;
use Illuminate\Http\Request;

class OutboxController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isManagement(), 403);
        $q = EmailOutbox::latest();
        if ($s = $request->input('status')) {
            $q->where('status', $s);
        }
        return view('outbox.index', ['rows' => $q->paginate(40)->withQueryString()]);
    }

    public function retry(Request $request, EmailOutbox $row)
    {
        abort_unless($request->user()->isManagement(), 403);
        $row->update(['status' => 'queued', 'error' => null]);
        SendOutboxEmail::dispatch($row->id);
        return $this->ok('outbox.requeued');
    }
}
