<?php

namespace Tests\Feature;

use App\Domain\PricingService;
use App\Models\EmailOutbox;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScopeAndPrivacyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function sales_only_see_their_own_files_and_clients(): void
    {
        $a = $this->user('sales');
        $b = $this->user('sales');
        $mine = $this->fileFor($a)->load('client');
        $theirs = $this->fileFor($b)->load('client');

        $this->actingAs($a)->get('/files')->assertSee($mine->number)->assertDontSee($theirs->number);
        $this->actingAs($a)->get("/files/{$theirs->number}")->assertNotFound();
        $this->actingAs($a)->get('/clients')->assertSee($mine->client->company)->assertDontSee($theirs->client->company);
        $this->actingAs($a)->get("/clients/{$theirs->client->id}")->assertNotFound();
    }

    #[Test]
    public function management_sees_every_file(): void
    {
        $f1 = $this->fileFor();
        $f2 = $this->fileFor();
        $this->actingAs($this->user('admin'))->get('/files')->assertSee($f1->number)->assertSee($f2->number);
    }

    #[Test]
    public function pricing_never_receives_client_contact_details(): void
    {
        $f = $this->fileFor(stage: 2);
        $c = $f->client;
        $pricing = $this->user('pricing');
        foreach (['overview', 'rfq', 'pricing', 'quote', 'shipping', 'docs', 'history'] as $tab) {
            $html = $this->actingAs($pricing)->get("/files/{$f->number}?tab={$tab}")->assertOk()->getContent();
            $this->assertStringNotContainsString($c->email, $html, "email shown on {$tab}");
            $this->assertStringNotContainsString($c->phone, $html, "phone shown on {$tab}");
            $this->assertStringNotContainsString($c->contact_person, $html, "contact shown on {$tab}");
        }
        $this->actingAs($pricing)->get("/clients/{$c->id}")->assertStatus(403);
        $this->actingAs($pricing)->getJson('/clients/duplicates?phone='.urlencode($c->phone))->assertStatus(403);
        $this->actingAs($pricing)->get("/files/{$f->number}/quotation.pdf")->assertStatus(403);
    }

    #[Test]
    public function the_rfq_email_to_vendors_has_no_client_contact_or_name(): void
    {
        $f = $this->fileFor(stage: 2);
        $v = Vendor::create(['name' => 'Agent X', 'type' => 'agent', 'email' => 'x@agent.example', 'active' => true]);
        app(PricingService::class)->sendRfq($f, [$v->id], $this->user('pricing'));
        $mail = EmailOutbox::firstOrFail();
        $this->assertStringContainsString($f->ref.'-OPS', $mail->subject);
        foreach ([$f->client->email, $f->client->phone, $f->client->contact_person, $f->client->company] as $secret) {
            $this->assertStringNotContainsString($secret, $mail->body_html);
        }
    }

    #[Test]
    public function actual_costs_and_profit_are_for_accounts_and_management_only(): void
    {
        $f = $this->fileFor(stage: 6);
        $f->costLines()->create(['type' => 'freight', 'currency' => 'USD', 'estimated' => 1000, 'actual' => 1234.56, 'exchange_rate' => 1]);
        $this->actingAs($this->user('accounts'))->get("/files/{$f->number}?tab=money")->assertSee('1,234.56', false);
        $this->actingAs($f->sales)->get("/files/{$f->number}?tab=money")->assertDontSee('1,234.56', false)->assertSee(__('common.hidden_for_role'));
    }

    #[Test]
    public function protected_fields_are_ignored_on_client_and_rfq_updates(): void
    {
        $sales = $this->user('sales');
        $f = $this->fileFor($sales, attrs: ['pricing_status' => 'draft']);
        $other = $this->user('sales');
        $this->actingAs($sales)->put("/clients/{$f->client->id}", $f->client->only(['company', 'contact_person', 'phone', 'email', 'sector']) + ['owner_id' => $other->id, 'credit_limit' => 999999, 'credit_days' => 90]);
        $c = $f->client->fresh();
        $this->assertSame($sales->id, $c->owner_id);
        $this->assertEquals(0, (float) $c->credit_limit);

        $payload = ['client_id' => $f->client_id, 'direction' => 'import', 'service' => 'fcl', 'origin' => 'CNSHA', 'destination' => 'EGALY', 'incoterm' => 'FOB',
            'inq' => $f->inq, 'action' => 'draft', 'stage' => 7, 'status' => 'closed', 'sales_id' => $other->id, 'pricing_status' => 'won', 'checks' => ['delivered' => true]];
        $this->actingAs($sales)->put("/files/{$f->number}/rfq", $payload)->assertRedirect();
        $f->refresh();
        $this->assertSame(1, $f->stage);
        $this->assertSame('open', $f->status);
        $this->assertSame($sales->id, $f->sales_id);
        $this->assertSame('draft', $f->pricing_status);
        $this->assertFalse($f->check('delivered'));
    }
}
