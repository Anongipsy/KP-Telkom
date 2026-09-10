<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Telkom B2B Monitoring');
        $response->assertSee('Masuk ke Dashboard');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->am()->create([
            'email' => 'am@telkom.co.id',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);

        $response = $this->post(route('login.attempt'), [
            'email' => 'am@telkom.co.id',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'login',
            'target_type' => 'user',
            'target_reference' => 'am@telkom.co.id',
        ]);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'am@telkom.co.id',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->from(route('login'))->post(route('login.attempt'), [
            'email' => 'am@telkom.co.id',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'failed_login',
            'target_reference' => 'am@telkom.co.id',
        ]);
    }

    public function test_user_cannot_login_with_non_existent_email(): void
    {
        $response = $this->from(route('login'))->post(route('login.attempt'), [
            'email' => 'unknown@telkom.co.id',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'failed_login',
            'target_reference' => 'unknown@telkom.co.id',
        ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create([
            'email' => 'inactive@telkom.co.id',
            'password' => bcrypt('password'),
        ]);

        $response = $this->from(route('login'))->post(route('login.attempt'), [
            'email' => 'inactive@telkom.co.id',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'failed_login_inactive',
            'target_reference' => 'inactive@telkom.co.id',
        ]);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'logout',
        ]);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_am_can_access_dashboard(): void
    {
        $am = User::factory()->am()->create();

        $response = $this->actingAs($am)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Account Manager');
        $response->assertSee($am->name);
    }

    public function test_authenticated_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Admin / Developer');
        $response->assertSee($admin->name);
    }

    public function test_am_cannot_access_admin_route(): void
    {
        $am = User::factory()->am()->create();

        $response = $this->actingAs($am)->get(route('admin.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_route(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
    }

    public function test_login_attempts_are_throttled_after_too_many_failures(): void
    {
        $user = User::factory()->create([
            'email' => 'throttled@telkom.co.id',
            'password' => bcrypt('password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.attempt'), [
                'email' => 'throttled@telkom.co.id',
                'password' => 'wrong-pass',
            ]);
        }

        // 6th attempt should be throttled
        $response = $this->post(route('login.attempt'), [
            'email' => 'throttled@telkom.co.id',
            'password' => 'wrong-pass',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'failed_login_throttled',
        ]);
    }

    public function test_inactive_user_is_logged_out_on_subsequent_request(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user);

        // Simulate admin deactivating user account
        $user->update(['is_active' => false]);

        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
