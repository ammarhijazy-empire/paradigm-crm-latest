<?php

namespace App\Http\Controllers;

use App\Ai\AiGuard;
use App\Domain\DocumentService;
use App\Jobs\RunAiFeature;
use App\Models\AiRun;
use Illuminate\Http\Request;

class AiController extends Controller
{
    /** 13.1 — start reading a WhatsApp chat / voice notes. The form polls status(). */
    public function whatsapp(Request $request, AiGuard $guard, DocumentService $docs)
    {
        abort_unless($request->user()->actsAs('sales'), 403);
        $request->validate([
            'text' => ['nullable', 'string', 'max:60000'],
            'files' => ['array', 'max:10'], 'files.*' => ['file', 'mimes:txt,zip,opus,ogg,oga,m4a,mp3,aac,wav,mp4', 'max:'.DocumentService::MAX_KB],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
        ]);
        if (! trim((string) $request->input('text')) && ! $request->hasFile('files')) {
            return response()->json(['message' => __('ai.nothing_to_read')], 422);
        }
        try {
            $guard->check('whatsapp_rfq');
        } catch (\App\Ai\AiException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        $ids = [];
        foreach ($request->file('files', []) as $up) {
            $ext = strtolower($up->getClientOriginalExtension());
            $ids[] = $docs->store($up, in_array($ext, ['txt', 'zip'], true) ? 'whatsapp_chat' : 'voice_note', $request->user(), null, $request->integer('client_id') ?: null)->id;
        }
        $run = AiRun::create(['feature' => 'whatsapp_rfq', 'user_id' => $request->user()->id, 'status' => 'queued', 'input_ref' => ['text' => (string) $request->input('text'), 'document_ids' => $ids]]);
        RunAiFeature::dispatch($run->id);
        return response()->json(['id' => $run->id, 'status_url' => route('ai.status', $run)]);
    }

    public function status(Request $request, AiRun $run)
    {
        $this->authorize('view', $run);
        return response()->json(['status' => $run->status, 'error' => $run->status === 'failed' ? $run->error : null, 'output' => $run->status === 'done' ? $run->output : null]);
    }

    public function usage(Request $request, AiGuard $guard)
    {
        abort_unless($request->user()->isManagement(), 403);
        return view('ai.usage', [
            'runs' => AiRun::with('user', 'file')->latest()->paginate(40),
            'spent' => $guard->spentThisMonth(), 'limit' => $guard->limit(),
            'byFeature' => AiRun::where('created_at', '>=', now()->startOfMonth())->selectRaw('feature, count(*) as n, sum(cost_usd) as cost')->groupBy('feature')->get()->keyBy('feature'),
            'configured' => app(\App\Ai\AiClient::class)->configured(),
        ]);
    }
}
