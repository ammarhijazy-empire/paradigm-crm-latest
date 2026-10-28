<?php

namespace Tests\Feature;

use App\Domain\FileStageService;
use App\Exceptions\WorkflowException;
use App\Models\DocCheck;
use App\Models\FileHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StageTest extends TestCase
{
    use RefreshDatabase;

    private function svc(): FileStageService
    {
        return app(FileStageService::class);
    }

    #[Test]
    public function stage_and_checks_cannot_be_changed_directly(): void
    {
        $f = $this->fileFor();
        $this->expectException(LogicException::class);
        $f->forceStageAttributes(['stage' => 5]);
        $f->save();
    }

    #[Test]
    public function sales_cannot_tick_an_operations_box(): void
    {
        $sales = $this->user('sales');
        $f = $this->fileFor($sales, 3);
        $this->actingAs($sales)->post("/files/{$f->number}/checks", ['item' => 'booking_done', 'value' => 1])->assertSessionHasErrors('workflow');
        $this->assertFalse($f->fresh()->check('booking_done'));
        // but sales may tick its own handover box in stage 3
        $this->actingAs($sales)->post("/files/{$f->number}/checks", ['item' => 'sales_handover', 'value' => 1])->assertSessionHasNoErrors();
        $this->assertTrue($f->fresh()->check('sales_handover'));
    }

    #[Test]
    public function only_the_stage_owner_moves_the_stage(): void
    {
        $f = $this->fileFor(stage: 3, checks: ['acid_nafeza' => true, 'booking_done' => true]);
        $this->actingAs($f->sales)->post("/files/{$f->number}/advance")->assertSessionHasErrors('workflow');
        $this->assertSame(3, $f->fresh()->stage);
        $this->actingAs($this->user('ops'))->post("/files/{$f->number}/advance")->assertSessionHasNoErrors();
        $this->assertSame(4, $f->fresh()->stage);
        $this->assertDatabaseHas('file_history', ['file_id' => $f->id, 'action' => 'moved', 'from_stage' => 3, 'to_stage' => 4]);
    }

    #[Test]
    public function stage_4_to_5_needs_bl_atd_eta_and_carrier(): void
    {
        $ops = $this->user('ops');
        $f = $this->fileFor(stage: 4);
        try {
            $this->svc()->advance($f, $ops);
            $this->fail('should be blocked');
        } catch (WorkflowException $e) {
            $this->assertCount(4, $e->problems);
        }
        $f->update(['bl_no' => 'MEDU123', 'atd' => now()->subDay(), 'eta' => now()->addWeek(), 'carrier' => 'MSC']);
        $this->svc()->advance($f->fresh(), $ops);
        $this->assertSame(5, $f->fresh()->stage);
    }

    #[Test]
    public function open_document_blockers_stop_stage_4_to_5_until_dismissed_with_a_reason(): void
    {
        $ops = $this->user('ops');
        $f = $this->fileFor(stage: 4, attrs: ['bl_no' => 'X1', 'atd' => now()->subDay(), 'eta' => now()->addDays(5), 'carrier' => 'MSC']);
        $check = DocCheck::create(['file_id' => $f->id, 'severity' => 'blocker', 'field' => 'weight', 'message' => '{"en":"weights differ","ar":"الأوزان مختلفة"}', 'status' => 'open']);
        $this->actingAs($ops)->post("/files/{$f->number}/advance")->assertSessionHasErrors('workflow');
        $this->actingAs($ops)->post("/doc-checks/{$check->id}/dismiss", ['reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs($ops)->post("/doc-checks/{$check->id}/dismiss", ['reason' => 'Supplier re-weighed; AWB is correct'])->assertSessionHasNoErrors();
        $this->actingAs($ops)->post("/files/{$f->number}/advance")->assertSessionHasNoErrors();
        $this->assertSame(5, $f->fresh()->stage);
    }

    #[Test]
    public function required_items_block_the_move_even_for_management(): void
    {
        $admin = $this->user('admin');
        $f = $this->fileFor(stage: 5);
        $this->expectException(WorkflowException::class);
        $this->svc()->advance($f, $admin);
    }

    #[Test]
    public function management_can_tick_any_box_and_move_any_stage(): void
    {
        $admin = $this->user('admin');
        $f = $this->fileFor(stage: 5);
        foreach (['release_approved', 'delivered', 'actual_costs_handed'] as $c) {
            $this->svc()->tick($f, $c, true, $admin);
        }
        $this->svc()->advance($f, $admin);
        $this->assertSame(6, $f->fresh()->stage);
        $this->svc()->back($f->fresh(), $admin, 'wrong click');
        $this->assertSame(5, $f->fresh()->stage);
    }

    #[Test]
    public function no_stage_can_be_skipped_and_only_management_moves_back(): void
    {
        $f = $this->fileFor(stage: 2);
        $this->actingAs($f->sales)->post("/files/{$f->number}/back", ['reason' => 'x'])->assertStatus(403);
        $this->actingAs($f->sales)->post("/files/{$f->number}/checks", ['item' => 'delivered', 'value' => 1])->assertSessionHasErrors('workflow');
    }

    #[Test]
    public function marking_lost_needs_a_category_and_a_reason(): void
    {
        $f = $this->fileFor(stage: 2);
        $this->actingAs($f->sales)->post("/files/{$f->number}/lost", ['category' => 'price', 'reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs($f->sales)->post("/files/{$f->number}/lost", ['category' => 'price', 'reason' => 'Went to a competitor at $3.10/kg'])->assertSessionHasNoErrors();
        $f->refresh();
        $this->assertSame('lost', $f->status);
        $this->assertSame(2, $f->lost_stage);
        $this->assertTrue(FileHistory::where('file_id', $f->id)->where('action', 'lost')->exists());
    }

    #[Test]
    public function stage_7_advance_closes_the_file(): void
    {
        $sales = $this->user('sales');
        $f = $this->fileFor($sales, 7, ['after_sale_call' => true]);
        $this->svc()->advance($f, $sales);
        $this->assertSame('closed', $f->fresh()->status);
    }
}
