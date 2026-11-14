<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'small_business',
            'currency' => 'USD',
        ]);

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Main Checking',
            'type' => 'checking',
            'balance' => 5000.00,
            'initial_balance' => 5000.00,
            'currency' => 'USD',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Financial Dashboard');
        $response->assertSee('Current Balance');
        $response->assertSee('$5,000.00');
    }

    public function test_recommended_sidebar_navigation_items_are_present(): void
    {
        $user = User::factory()->create(['currency' => 'INR']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        // All 12 recommended sidebar items
        $response->assertSee('Dashboard');
        $response->assertSee('Transactions');
        $response->assertSee('Income');
        $response->assertSee('Expenses');
        $response->assertSee('Categories');
        $response->assertSee('Accounts');
        $response->assertSee('Budgets');
        $response->assertSee('Financial Goals');
        $response->assertSee('AI Assistant');
        $response->assertSee('Reports');
        $response->assertSee('Notifications');
        $response->assertSee('Settings');
    }

    public function test_admin_sidebar_navigation_items_are_present_for_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->assignRole($adminRole);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
        // All 8 admin sidebar items
        $response->assertSee('Overview');
        $response->assertSee('Users');
        $response->assertSee('Roles &amp; Permissions', false);
        $response->assertSee('Categories');
        $response->assertSee('Subscription Plans');
        $response->assertSee('AI Usage');
        $response->assertSee('System Settings');
        $response->assertSee('Audit Logs');
        $response->assertSee('Back to App Dashboard');
    }

    public function test_non_admin_cannot_access_admin_console(): void
    {
        $user = User::factory()->create(['role' => 'individual']);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(403);
    }
}

