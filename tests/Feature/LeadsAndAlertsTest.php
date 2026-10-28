<?php

namespace Tests\Feature;

use App\Domain\Alerts;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeadKey;
use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeadsAndAlertsTest extends TestCase
{
    use RefreshDatabase;

    private function key(): string
    {
        LeadKey::create(['name' => 'site', 'key_hash' => hash('sha256', 'lk_secret'), 'active' => true]);
        return 'lk_secret';
    }

    #[Test]
    public function the_lead_api_needs_the_secret_key(): void
    {
        $this->postJson('/api/leads', ['company' => 'X', 'phone' => '0100'])->assertStatus(401);
        $this->postJson('/api/leads', ['company' => 'X', 'phone' => '0100'], ['X-Lead-Key' => 'wrong'])->assertStatus(401);
        $this->postJson('/api/leads', ['company' => 'X'], ['X-Lead-Key' => $this->key()])->assertStatus(422);
    }

    #[Test]
    public function leads_rotate_between_salespeople_who_get_leads(): void
    {
        $key = $this->key();
        [$a, $b] = [$this->user('sales'), $this->user('sales')];
        $c = $this->user('sales');
        foreach ([$a, $b] as $u) {
            $u->forceFill(['gets_leads' => true])->save();
        }
        foreach (range(1, 4) as $i) {
            $this->postJson('/api/leads', ['company' => "Co {$i}", 'email' => "c{$i}@x.example"], ['X-Lead-Key' => $key])->assertCreated();
        }
        $this->assertSame([$a->id, $b->id, $a->id, $b->id], Lead::orderBy('id')->pluck('assigned_to')->all());
        $this->assertSame(0, Lead::where('assigned_to', $c->id)->count());
    }

    #[Test]
    public function the_lead_api_is_rate_limited(): void
    {
        $key = $this->key();
        $this->user('sales')->forceFill(['gets_leads' => true])->save();
        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/api/leads', ['company' => "Co {$i}", 'email' => "c{$i}@x.example"], ['X-Lead-Key' => $key]);
        }
        $this->postJson('/api/leads', ['company' => 'Too many', 'email' => 'x@x.example'], ['X-Lead-Key' => $key])->assertStatus(429);
    }

    #[Test]
    public function a_late_lead_alerts_the_salesperson_and_management_once(): void
    {
        Notification::fake();
        $admin = $this->user('admin');
        $s = $this->user('sales');
        Lead::create(['company' => 'Late Co', 'assigned_to' => $s->id, 'received_at' => now()->subHours(6), 'sla_due_at' => now()->subHours(2)]);
        $this->assertSame(1, app(Alerts::class)->leadSla());
        $this->assertSame(0, app(Alerts::class)->leadSla());
        Notification::assertSentTo([$s, $admin], CrmNotification::class, fn ($n) => $n->type === 'lead_sla');
    }

    #[Test]
    public function stage_moves_notify_the_next_owner_on_the_server(): void
    {
        Notification::fake();
        $ops = $this->user('ops');
        $customs = $this->user('customs');
        $f = $this->fileFor(stage: 4, attrs: ['bl_no' => 'B', 'atd' => now(), 'eta' => now()->addDay(), 'carrier' => 'MSC']);
        app(\App\Domain\FileStageService::class)->advance($f, $ops);
        Notification::assertSentTo($customs, CrmNotification::class, fn ($n) => $n->type === 'stage_moved');
        Notification::assertNotSentTo($ops, CrmNotification::class);
    }

    #[Test]
    public function mentions_in_notes_notify_the_person_mentioned(): void
    {
        Notification::fake();
        $hassan = User::factory()->role('ops')->create(['name' => 'Hassan Ali']);
        $f = $this->fileFor();
        $this->actingAs($f->sales)->post("/files/{$f->number}/notes", ['body' => '@Hassan please book this'])->assertRedirect();
        Notification::assertSentTo($hassan, CrmNotification::class, fn ($n) => $n->type === 'mentioned');
    }

    #[Test]
    public function follow_ups_and_invoice_dues_are_scheduled_alerts(): void
    {
        Notification::fake();
        $s = $this->user('sales');
        Client::factory()->create(['owner_id' => $s->id, 'next_follow_up' => now('Africa/Cairo')->toDateString()]);
        $this->assertSame(1, app(Alerts::class)->followUps());
        $acc = $this->user('accounts');
        $f = $this->fileFor($s, 6);
        Invoice::create(['number' => 'INV-T-1', 'file_id' => $f->id, 'client_id' => $f->client_id, 'currency' => 'USD', 'issue_date' => now()->subDays(10), 'due_date' => now('Africa/Cairo')->addDays(3)->toDateString(), 'total' => 100, 'subtotal' => 100, 'status' => 'issued', 'created_by' => $acc->id]);
        $this->assertSame(1, app(Alerts::class)->invoices());
        Notification::assertSentTo($acc, CrmNotification::class, fn ($n) => $n->type === 'invoice_due');
    }

    #[Test]
    public function notifications_are_shown_in_each_users_language(): void
    {
        $u = $this->user('sales');
        $u->notify(new CrmNotification('file_closed', ['ref' => 'PC-26-001'], '/files/PC-26-001'));
        $data = $u->notifications()->first()->data;
        $this->assertSame('PC-26-001 اتقفل', CrmNotification::text($data, 'ar'));
        $this->assertSame('PC-26-001 is closed', CrmNotification::text($data, 'en'));
    }

    #[Test]
    public function the_scheduler_lists_every_job(): void
    {
        $out = \Illuminate\Support\Facades\Artisan::call('schedule:list');
        $list = \Illuminate\Support\Facades\Artisan::output();
        foreach (['crm:rates', 'crm:alerts leads', 'crm:alerts followups', 'crm:alerts invoices', 'crm:alerts vendors', 'crm:alerts waiting', 'crm:alerts weekly', 'crm:alerts monthly', 'crm:backup', 'FetchRateEmails'] as $job) {
            $this->assertStringContainsString($job, $list);
        }
    }
}
