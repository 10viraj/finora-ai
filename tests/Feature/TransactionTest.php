<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_record_transaction_with_attachment_and_balance_updates(): void
    {
        Storage::fake('local');

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

        $receipt = UploadedFile::fake()->create('invoice.pdf', 120, 'application/pdf');

        $response = $this->actingAs($user)->post('/transactions', [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'income',
            'amount' => 500.00,
            'fee' => 0.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Website Development Retainer',
            'payment_method' => 'bank_transfer',
            'is_recurring' => 1,
            'recurrence_interval' => 'monthly',
            'receipt' => $receipt,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'description' => 'Website Development Retainer',
            'amount' => 500.00,
            'is_recurring' => true,
            'recurrence_interval' => 'monthly',
        ]);

        $account->refresh();
        $this->assertEquals(1500.00, $account->balance);

        $tx = Transaction::where('description', 'Website Development Retainer')->first();
        $this->assertNotNull($tx->receipt_path);
        Storage::disk('local')->assertExists($tx->receipt_path);
    }

    public function test_user_can_edit_and_update_transaction(): void
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
            'name' => 'Office Supplies',
            'type' => 'expense',
        ]);

        $tx = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 100.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Desk Accessories',
            'payment_method' => 'credit_card',
            'status' => 'completed',
        ]);
        $account->recalculateBalance();
        $this->assertEquals(900.00, $account->balance);

        // Update amount from 100 to 150
        $response = $this->actingAs($user)->put("/transactions/{$tx->id}", [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 150.00,
            'fee' => 0,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Desk Accessories and Monitor Arm',
            'payment_method' => 'credit_card',
        ]);

        $response->assertSessionHas('success');

        $tx->refresh();
        $this->assertEquals(150.00, $tx->amount);
        $this->assertEquals('Desk Accessories and Monitor Arm', $tx->description);

        $account->refresh();
        $this->assertEquals(850.00, $account->balance);
    }

    public function test_user_can_filter_transactions_by_payment_method_and_type(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Checking',
            'type' => 'checking',
            'balance' => 1000.00,
            'initial_balance' => 1000.00,
            'currency' => 'USD',
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 50.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Coffee Meeting',
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 200.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Software License',
            'payment_method' => 'credit_card',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->get('/transactions?payment_method=cash');
        $response->assertStatus(200);
        $response->assertSee('Coffee Meeting');
        $response->assertDontSee('Software License');
    }

    public function test_user_can_delete_transaction_and_restore_balance(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Checking',
            'type' => 'checking',
            'balance' => 1000.00,
            'initial_balance' => 1000.00,
            'currency' => 'USD',
        ]);

        $tx = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 250.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Refundable Equipment Deposit',
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
        ]);
        $account->recalculateBalance();
        $this->assertEquals(750.00, $account->balance);

        $response = $this->actingAs($user)->delete("/transactions/{$tx->id}");
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('transactions', ['id' => $tx->id]);

        $account->refresh();
        $this->assertEquals(1000.00, $account->balance);
    }
}
