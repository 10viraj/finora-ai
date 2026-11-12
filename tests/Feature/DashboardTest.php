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
}
