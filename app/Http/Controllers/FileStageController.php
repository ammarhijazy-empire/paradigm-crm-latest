<?php

namespace App\Http\Controllers;

use App\Domain\FileStageService;
use App\Models\ShipmentFile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FileStageController extends Controller
{
    public function __construct(private FileStageService $stages) {}

    public function tick(Request $request, ShipmentFile $file)
    {
        $this->authorize('view', $file);
        $d = $request->validate(['item' => ['required', 'string', 'max:40'], 'value' => ['required', 'boolean']]);
        $this->stages->tick($file, $d['item'], (bool) $d['value'], $request->user());
        return $request->expectsJson() ? response()->json(['ok' => true, 'gates' => $this->stages->gates($file->refresh())]) : back();
    }

    public function advance(Request $request, ShipmentFile $file)
    {
        $this->authorize('view', $file);
        $this->stages->advance($file, $request->user());
        return back()->with('ok', $file->status === 'closed' ? __('workflow.closed_ok') : __('workflow.moved_ok', ['n' => $file->stage]));
    }

    public function back(Request $request, ShipmentFile $file)
    {
        $this->authorize('moveBack', $file);
        $d = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->stages->back($file, $request->user(), $d['reason']);
        return back()->with('ok', __('workflow.moved_ok', ['n' => $file->stage]));
    }

    public function lost(Request $request, ShipmentFile $file)
    {
        $this->authorize('markLost', $file);
        $d = $request->validate(['category' => ['required', Rule::in(FileStageService::LOST_CATEGORIES)], 'reason' => ['required', 'string', 'max:1000']]);
        $this->stages->markLost($file, $request->user(), $d['category'], $d['reason']);
        return back()->with('ok', __('workflow.lost_ok'));
    }

    public function reopen(Request $request, ShipmentFile $file)
    {
        $this->authorize('moveBack', $file);
        $this->stages->reopen($file, $request->user());
        return back()->with('ok', __('workflow.reopened'));
    }
}
