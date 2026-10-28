<?php

namespace App\Http\Controllers;

use App\Domain\ClientService;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function __construct(private ClientService $clients) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Client::class);
        $q = Client::with('owner')->orderBy('company');
        if ($s = $request->input('status')) {
            $q->where('status', $s);
        }
        if ($t = trim((string) $request->input('q'))) {
            $q->where(fn ($w) => $w->where('company', 'like', "%{$t}%")->orWhere('contact_person', 'like', "%{$t}%")->orWhere('phone', 'like', "%{$t}%")->orWhere('email', 'like', "%{$t}%"));
        }
        if ($request->boolean('due')) {
            $q->whereNotNull('next_follow_up')->where('next_follow_up', '<=', today());
        }
        return view('clients.index', ['clients' => $q->paginate(30)->withQueryString()]);
    }

    public function create()
    {
        $this->authorize('create', Client::class);
        return view('clients.form', ['client' => new Client(['status' => 'lead']), 'owners' => User::role('sales')->get()]);
    }

    public function store(ClientRequest $request)
    {
        $dups = $this->clients->duplicates($request->input('phone'), $request->input('email'));
        if ($dups->isNotEmpty() && ! $request->boolean('confirm_duplicate')) {
            return back()->withInput()->with('duplicates', $dups);
        }
        $c = $this->clients->create($request->validated(), $request->user());
        return redirect()->route('clients.show', $c)->with('ok', __('common.saved'));
    }

    public function show(Client $client)
    {
        $this->authorize('view', $client);
        return view('clients.show', ['client' => $client->load('owner', 'activities.user', 'files', 'invoices')]);
    }

    public function edit(Client $client)
    {
        $this->authorize('update', $client);
        return view('clients.form', ['client' => $client, 'owners' => User::role('sales')->get()]);
    }

    public function update(ClientRequest $request, Client $client)
    {
        $this->clients->update($client, $request->validated(), $request->user());
        return redirect()->route('clients.show', $client)->with('ok', __('common.saved'));
    }

    public function duplicates(Request $request)
    {
        abort_unless($request->user()->actsAs('sales'), 403);
        $d = $this->clients->duplicates($request->input('phone'), $request->input('email'), $request->integer('ignore') ?: null);
        // Only company + owner name: no contact details of other salespeople's clients.
        return response()->json($d->map(fn ($c) => ['company' => $c->company, 'owner' => $c->owner?->name]));
    }

    public function activity(Request $request, Client $client)
    {
        $this->authorize('update', $client);
        $d = $request->validate(['type' => ['required', Rule::in(['call', 'whatsapp', 'meeting', 'email', 'note'])], 'body' => ['required', 'string', 'max:3000'], 'rating' => ['nullable', 'integer', 'between:1,5'], 'next_follow_up' => ['nullable', 'date']]);
        $this->clients->logActivity($client, $d, $request->user());
        return $this->ok('common.saved');
    }
}
