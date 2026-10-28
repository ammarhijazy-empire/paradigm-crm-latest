<?php

namespace App\Http\Controllers;

use App\Domain\ClientService;
use App\Domain\LeadService;
use App\Models\Lead;
use App\Models\User;
use App\Support\Sectors;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function __construct(private LeadService $leads) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Lead::class);
        $u = $request->user();
        $q = Lead::with('assignee', 'client')->latest('received_at');
        if (! $u->isManagement()) {
            $q->where('assigned_to', $u->id);
        }
        return view('leads.index', ['leads' => $q->paginate(30), 'next' => $this->leads->nextAssignee(), 'reps' => User::role('sales')->where('active', true)->get()]);
    }

    public function contacted(Request $request, Lead $lead)
    {
        $this->leads->markContacted($lead, $request->user());
        return $this->ok('common.saved');
    }

    public function convert(Request $request, Lead $lead, ClientService $clients)
    {
        $this->authorize('update', $lead);
        $d = $request->validate(['sector' => ['required', Rule::in(Sectors::KEYS)]]);
        $c = $clients->fromLead($lead, $request->user(), $d['sector']);
        return redirect()->route('clients.edit', $c)->with('ok', __('leads.converted'));
    }

    public function assign(Request $request, Lead $lead)
    {
        $d = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        $this->leads->reassign($lead, $request->user(), User::findOrFail($d['user_id']));
        return $this->ok('common.saved');
    }
}
