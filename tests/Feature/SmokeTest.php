<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\ShipmentFile;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Opens every screen as every role in both languages: no server errors, no raw translation keys, correct direction. */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seedReference = false;

    private const GROUPS = 'app|nav|files|rfq|pricing|quote|docs|invoice|clients|leads|vendors|perf|users|settings|ai|common|dash|workflow|history|shipping|services|cargo|pay|costs|sections|sectors|lost|roles|stages|checks|notify|outbox|auth|validation';

    #[Test]
    public function every_page_renders_for_every_role_in_arabic_and_english(): void
    {
        $this->seed(DemoSeeder::class);
        $pages = ['/dashboard', '/files', '/files?status=', '/operations', '/rfq/new', '/pricing', '/clients', '/clients/create', '/leads', '/vendors', '/vendors/create', '/invoices', '/collections', '/performance', '/users', '/settings', '/ai/usage', '/outbox', '/notifications'];
        foreach (ShipmentFile::all() as $f) {
            foreach (['overview', 'rfq', 'pricing', 'quote', 'shipping', 'docs', 'money', 'history'] as $tab) {
                $pages[] = "/files/{$f->number}?tab={$tab}";
            }
            $pages[] = "/files/{$f->number}/rfq";
        }
        foreach (Client::all() as $c) {
            $pages[] = "/clients/{$c->id}";
            $pages[] = "/clients/{$c->id}/edit";
        }
        foreach (Invoice::all() as $i) {
            $pages[] = "/invoices/{$i->number}";
        }

        $checked = 0;
        foreach (['ammar', 'abdullah', 'nour', 'karim', 'hassan', 'tamer', 'mona'] as $who) {
            $u = User::where('email', "{$who}@paradigmcargo.com")->firstOrFail();
            foreach (['ar', 'en'] as $lang) {
                $u->forceFill(['locale' => $lang])->save();
                foreach ($pages as $p) {
                    $r = $this->actingAs($u->fresh())->get($p);
                    $status = $r->getStatusCode();
                    $this->assertNotSame(500, $status, "{$who}/{$lang} {$p} → 500: ".($r->exception?->getMessage() ?? ''));
                    $this->assertContains($status, [200, 403, 404], "{$who}/{$lang} {$p} → {$status}");
                    if ($status === 200) {
                        $html = $r->getContent();
                        $this->assertStringContainsString('dir="'.($lang === 'ar' ? 'rtl' : 'ltr').'"', $html, "{$p} direction");
                        $text = html_entity_decode(strip_tags(preg_replace('#<(script|style|template)\b.*?</\1>#s', '', $html)));
                        $this->assertDoesNotMatchRegularExpression('/(?<![\w@\/.-])('.self::GROUPS.')\.[a-z][a-z0-9_]+(?![\w@.\/-])/', $text, "{$who}/{$lang} {$p} shows a raw translation key");
                        $checked++;
                    }
                }
            }
        }
        $this->assertGreaterThan(500, $checked);
    }

    #[Test]
    public function management_alone_opens_every_page(): void
    {
        $this->seed(DemoSeeder::class);
        $admin = User::factory()->role('admin')->create();
        foreach (ShipmentFile::all() as $f) {
            foreach (['overview', 'pricing', 'quote', 'shipping', 'docs', 'money', 'history'] as $tab) {
                $this->actingAs($admin)->get("/files/{$f->number}?tab={$tab}")->assertOk();
            }
        }
        foreach (['/rfq/new', '/pricing', '/operations', '/clients', '/leads', '/vendors', '/invoices', '/collections', '/performance', '/users', '/settings'] as $p) {
            $this->actingAs($admin)->get($p)->assertOk();
        }
    }
}
