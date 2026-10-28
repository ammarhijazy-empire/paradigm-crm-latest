<?php

namespace App\Http\Controllers;

use App\Ai\AiGuard;
use App\Domain\DocumentService;
use App\Domain\FileStageService;
use App\Jobs\RunAiFeature;
use App\Models\AiRun;
use App\Models\DocCheck;
use App\Models\Document;
use App\Models\ShipmentFile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function store(Request $request, ShipmentFile $file, DocumentService $docs)
    {
        $this->authorize('uploadDocuments', $file);
        $d = $request->validate(['type' => ['required', Rule::in(DocumentService::TYPES)], 'files' => ['required', 'array', 'max:10'], 'files.*' => ['file', 'mimes:'.DocumentService::ALLOWED_MIMES, 'max:'.DocumentService::MAX_KB]]);
        foreach ($request->file('files') as $up) {
            $docs->store($up, $d['type'], $request->user(), $file);
        }
        app(FileStageService::class)->history($file, $request->user(), 'documents_uploaded', null, null, __('docs.t_'.$d['type']).' ×'.count($request->file('files')));
        return redirect()->to(route('files.show', $file).'?tab=docs')->with('ok', __('docs.uploaded'));
    }

    public function download(Request $request, Document $document, DocumentService $docs)
    {
        $this->authorize('view', $document);
        return $docs->download($document);
    }

    public function runCheck(Request $request, ShipmentFile $file, AiGuard $guard)
    {
        $this->authorize('resolveDocChecks', $file);
        $guard->check('doc_check');
        $run = AiRun::create(['feature' => 'doc_check', 'user_id' => $request->user()->id, 'file_id' => $file->id, 'status' => 'queued', 'input_ref' => ['file_id' => $file->id]]);
        RunAiFeature::dispatch($run->id);
        return redirect()->to(route('files.show', $file).'?tab=docs')->with('ok', __('docs.check_queued'));
    }

    public function dismiss(Request $request, DocCheck $check)
    {
        $file = $check->file;
        $this->authorize('resolveDocChecks', $file);
        $d = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $check->update(['status' => 'dismissed', 'dismiss_reason' => $d['reason'], 'resolved_by' => $request->user()->id, 'resolved_at' => now()]);
        app(FileStageService::class)->history($file, $request->user(), 'doc_issue_dismissed', null, null, $d['reason']);
        return redirect()->to(route('files.show', $file).'?tab=docs')->with('ok', __('docs.dismissed'));
    }
}
