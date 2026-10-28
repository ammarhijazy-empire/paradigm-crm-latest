<?php

namespace App\Http\Controllers;

use App\Domain\DocumentService;
use App\Domain\InquiryValidator;
use App\Domain\RfqService;
use App\Http\Requests\RfqRequest;
use App\Models\AiRun;
use App\Models\Client;
use App\Models\Port;
use App\Models\ShipmentFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RfqController extends Controller
{
    public function create(Request $request)
    {
        $this->authorize('create', ShipmentFile::class);
        return view('rfq.form', [
            'file' => null,
            'clients' => Client::orderBy('company')->get(['id', 'company']),
            'ports' => Port::where('active', true)->orderBy('country')->orderBy('name_en')->get(),
            'preset' => ['client_id' => $request->integer('client') ?: null],
        ]);
    }

    public function store(RfqRequest $request, RfqService $rfq, DocumentService $docs)
    {
        $data = $request->payload();
        $send = $request->input('action') === 'send';
        $run = $this->aiRun($request);
        if ($send && $run && ! $request->boolean('ai_checked')) {
            return back()->withInput()->withErrors(['workflow' => [__('ai.confirm_fields_first')]]);
        }
        $file = DB::transaction(function () use ($request, $rfq, $docs, $data, $send, $run) {
            if ($request->hasFile('msds')) {
                $data['inq']['msds'] = $docs->store($request->file('msds'), 'msds', $request->user())->id;
            }
            $file = $rfq->create($data, $request->user(), $send);
            foreach ($request->file('attachments', []) as $up) {
                $docs->store($up, 'rfq_attachment', $request->user(), $file);
            }
            if ($run) {
                $run->update(['status' => 'accepted', 'file_id' => $file->id]);
                \App\Models\Document::whereIn('id', $run->input_ref['document_ids'] ?? [])->update(['file_id' => $file->id]);
            }
            if (! empty($data['inq']['msds'])) {
                \App\Models\Document::whereKey($data['inq']['msds'])->update(['file_id' => $file->id]);
            }
            return $file;
        });
        return redirect()->route('files.show', $file)->with('ok', __($send ? 'rfq.sent' : 'rfq.saved_draft', ['ref' => $file->ref]));
    }

    public function edit(ShipmentFile $file)
    {
        $this->authorize('updateRfq', $file);
        return view('rfq.form', [
            'file' => $file,
            'clients' => Client::orderBy('company')->get(['id', 'company']),
            'ports' => Port::where('active', true)->orderBy('country')->orderBy('name_en')->get(),
            'preset' => [],
        ]);
    }

    public function update(RfqRequest $request, ShipmentFile $file, RfqService $rfq)
    {
        $this->authorize('updateRfq', $file);
        $data = $request->payload();
        unset($data['client_id']);
        $data['inq'] = $data['inq'] + array_intersect_key($file->inq ?? [], ['msds' => 1]);
        $file->update($data);
        app(\App\Domain\FileStageService::class)->history($file, $request->user(), 'rfq_edited');
        if ($request->input('action') === 'send' && $file->pricing_status === 'draft') {
            $rfq->sendDraft($file->refresh(), $request->user());
            return redirect()->route('files.show', $file)->with('ok', __('rfq.sent', ['ref' => $file->ref]));
        }
        return redirect()->route('files.show', $file)->with('ok', __('common.saved'));
    }

    /** Server-side completeness check used by the form for live feedback. */
    public function check(RfqRequest $request)
    {
        return response()->json(['problems' => InquiryValidator::messages($request->payload())]);
    }

    private function aiRun(Request $request): ?AiRun
    {
        $id = $request->integer('ai_run_id');
        return $id ? AiRun::where('id', $id)->where('user_id', $request->user()->id)->where('feature', 'whatsapp_rfq')->where('status', 'done')->first() : null;
    }
}
