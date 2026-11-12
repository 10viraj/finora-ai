<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_record_transaction_and_balance_updates(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Operating Account',
            'type' => 'business_checking',
            'balance' => 1000.00,
            'initial_balance' => 1000.00,
            'currency' => 'USD',
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Client Revenue',
            'type' => 'income',
        ]);

        $response = $this->actingAs($user)->post('/transactions', [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'income',
            'amount' => 500.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Website Development Retainer',
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'description' => 'Website Development Retainer',
            'amount' => 500.00,
        ]);

        $account->refresh();
        $this->assertEquals(1500.00, $account->balance);
    }
}
