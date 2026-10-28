<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Who can open which screen. Management (admin only, no other role) opens everything. */
class AccessTest extends TestCase
{
    use RefreshDatabase;

    public static function matrix(): array
    {
        //            path            admin sales pricing review ops customs accounts
        return [
            'dashboard' => ['/dashboard', [200, 200, 200, 200, 200, 200, 200]],
            'files' => ['/files', [200, 200, 200, 200, 200, 200, 200]],
            'rfq' => ['/rfq/new', [200, 200, 403, 403, 403, 403, 403]],
            'pricing' => ['/pricing', [200, 403, 200, 200, 403, 403, 403]],
            'operations' => ['/operations', [200, 403, 403, 403, 200, 200, 403]],
            'clients' => ['/clients', [200, 200, 403, 403, 403, 403, 200]],
            'leads' => ['/leads', [200, 200, 403, 403, 403, 403, 403]],
            'vendors' => ['/vendors', [200, 403, 200, 200, 200, 200, 200]],
            'invoices' => ['/invoices', [200, 403, 403, 403, 403, 403, 200]],
            'collections' => ['/collections', [200, 403, 403, 403, 403, 403, 200]],
            'performance' => ['/performance', [200, 200, 403, 403, 403, 403, 403]],
            'users' => ['/users', [200, 403, 403, 403, 403, 403, 403]],
            'settings' => ['/settings', [200, 403, 403, 403, 403, 403, 403]],
            'ai usage' => ['/ai/usage', [200, 403, 403, 403, 403, 403, 403]],
            'outbox' => ['/outbox', [200, 403, 403, 403, 403, 403, 403]],
        ];
    }

    #[Test]
    #[DataProvider('matrix')]
    public function each_role_opens_only_its_screens(string $path, array $expected): void
    {
        foreach (['admin', 'sales', 'pricing', 'pricing_review', 'ops', 'customs', 'accounts'] as $i => $role) {
            $this->actingAs($this->user($role))->get($path)->assertStatus($expected[$i]);
        }
    }

    #[Test]
    public function management_without_any_other_role_can_act_in_every_department(): void
    {
        $admin = $this->user('admin');
        $this->assertFalse($admin->hasRole('sales'));
        $f = $this->fileFor(stage: 3);
        foreach (['view', 'updateRfq', 'price', 'review', 'quote', 'operate', 'clear', 'bill', 'viewMoney', 'viewClientContact', 'approveMargin', 'moveBack', 'markLost'] as $ability) {
            $this->assertTrue($admin->can($ability, $f), "admin cannot {$ability}");
        }
    }

    #[Test]
    public function non_management_cannot_change_users_or_settings(): void
    {
        $sales = $this->user('sales');
        $other = $this->user('ops');
        $this->actingAs($sales)->put("/users/{$other->id}", ['name' => 'x', 'roles' => ['admin']])->assertStatus(403);
        $this->assertFalse($other->fresh()->isManagement());
        $this->actingAs($sales)->put('/settings', ['min_margin_usd' => 0])->assertStatus(403);
    }

    #[Test]
    public function management_assigns_roles_and_cannot_remove_its_own_admin_role(): void
    {
        $admin = $this->user('admin');
        $new = \App\Models\User::factory()->create();
        $this->actingAs($admin)->put("/users/{$new->id}", ['name' => 'New', 'roles' => ['sales'], 'active' => 1, 'sales_target' => 5000, 'commission_pct' => 10])->assertSessionHasNoErrors();
        $this->assertTrue($new->fresh()->hasRole('sales'));
        $this->assertEquals(5000, (float) $new->fresh()->sales_target);
        $this->actingAs($admin)->put("/users/{$admin->id}", ['name' => 'Me', 'roles' => ['sales'], 'active' => 1]);
        $this->assertTrue($admin->fresh()->isManagement());
        $this->assertDatabaseHas('activity_log', ['description' => 'roles_changed', 'subject_id' => $new->id]);
    }
}
