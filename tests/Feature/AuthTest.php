<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function register(string $email)
    {
        return $this->post('/register', ['name' => 'Test', 'email' => $email, 'password' => 'Secret12345', 'password_confirmation' => 'Secret12345']);
    }

    #[Test]
    public function only_company_emails_can_sign_up(): void
    {
        $this->register('someone@gmail.com')->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function the_first_account_becomes_management_and_the_next_gets_no_role(): void
    {
        $this->register('ammar@paradigmcargo.com');
        $this->assertTrue(User::where('email', 'ammar@paradigmcargo.com')->first()->isManagement());
        auth()->logout();
        $this->register('new@paradigmcargo.com');
        $second = User::where('email', 'new@paradigmcargo.com')->first();
        $this->assertTrue($second->roles->isEmpty());
    }

    #[Test]
    public function a_user_without_a_role_is_blocked_everywhere(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->get('/dashboard')->assertStatus(403)->assertSee(__('auth.waiting_title'));
        $this->actingAs($u)->get('/files')->assertStatus(403);
        $this->actingAs($u)->getJson('/files')->assertStatus(403);
    }

    #[Test]
    public function a_deactivated_user_is_signed_out(): void
    {
        $u = $this->user('sales');
        $u->forceFill(['active' => false])->save();
        $this->actingAs($u)->get('/dashboard')->assertRedirect(route('login'));
    }

    #[Test]
    public function login_is_rate_limited(): void
    {
        $this->user('sales');
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'x@paradigmcargo.com', 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => 'x@paradigmcargo.com', 'password' => 'wrong'])->assertStatus(429);
    }

    #[Test]
    public function guests_are_sent_to_login_and_pages_carry_security_headers(): void
    {
        $this->get('/files')->assertRedirect(route('login'));
        $r = $this->get('/login')->assertOk();
        $this->assertStringContainsString("frame-ancestors 'none'", $r->headers->get('Content-Security-Policy'));
        $this->assertSame('DENY', $r->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $r->headers->get('X-Content-Type-Options'));
    }
}
