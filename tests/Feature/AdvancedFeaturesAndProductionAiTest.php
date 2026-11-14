<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\Category;
use App\Models\FinancialGoal;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\BudgetAlertNotification;
use App\Notifications\UnusualExpenseNotification;
use App\Services\AI\AIClient;
use App\Services\AI\ReceiptScannerService;
use App\Services\AnomalyDetectionService;
use App\Services\CurrencyService;
use App\Services\SubscriptionService;
use App\Services\TransactionCsvImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdvancedFeaturesAndProductionAiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $checkingAccount;
    protected Account $savingsAccount;
    protected Category $diningCat;
    protected Category $shoppingCat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Aditi Sharma',
            'email' => 'aditi@finora.test',
            'currency' => 'INR',
            'subscription_plan' => 'free',
            'subscription_status' => 'active',
        ]);

        $this->checkingAccount = Account::create([
            'user_id' => $this->user->id,
            'name' => 'HDFC Salary Checking',
            'type' => 'checking',
            'balance' => 50000.00,
            'initial_balance' => 50000.00,
            'currency' => 'INR',
            'is_active' => true,
        ]);

        $this->savingsAccount = Account::create([
            'user_id' => $this->user->id,
            'name' => 'ICICI High Yield Savings',
            'type' => 'savings',
            'balance' => 20000.00,
            'initial_balance' => 20000.00,
            'currency' => 'INR',
            'is_active' => true,
        ]);

        $this->diningCat = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Food & Dining',
            'type' => 'expense',
            'icon' => 'bi-cup-hot',
            'color' => '#f97316',
        ]);

        $this->shoppingCat = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Shopping & Retail',
            'type' => 'expense',
            'icon' => 'bi-bag',
            'color' => '#8b5cf6',
        ]);
    }

    /**
     * 1. Receipt Scanning: Upload a receipt and extract amount, date, and merchant using OCR.
     */
    public function test_receipt_scanning_ocr_extraction(): void
    {
        $token = $this->user->createToken('MobileApp')->plainTextToken;

        $scanner = app(ReceiptScannerService::class);

        // Simulated receipt content
        $receiptContent = "STARBUCKS COFFEE\nStore #4829 Mumbai\nDate: 2026-11-12\n1x Caramel Macchiato: 380.00\n1x Croissant: 170.00\nTOTAL: 550.00\nThank you!";
        $file = UploadedFile::fake()->createWithContent('starbucks_receipt_550.txt', $receiptContent);

        $result = $scanner->scan($this->user, $file);

        $this->assertStringContainsString('Starbucks', $result['merchant']);
        $this->assertEquals(550.00, $result['amount']);
        $this->assertEquals('2026-11-12', $result['date']);
        $this->assertEquals($this->diningCat->id, $result['category_id']);

        // Verify API endpoint
        $res = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/receipts/scan', [
                'receipt' => UploadedFile::fake()->createWithContent('amazon_receipt_1499.txt', "AMAZON RETAIL\nOrder Date: 2026-11-10\nTotal: 1499.00"),
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.merchant', 'Amazon Retail')
            ->assertJsonPath('data.amount', 1499);
    }

    /**
     * 2. Recurring Expenses: Automatically create scheduled transactions.
     */
    public function test_recurring_expenses_automatic_processing(): void
    {
        $rec = RecurringTransaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->checkingAccount->id,
            'category_id' => $this->diningCat->id,
            'type' => 'expense',
            'amount' => 999.00,
            'description' => 'Netflix 4K Family Subscription',
            'recurrence_interval' => 'monthly',
            'start_date' => now()->toDateString(),
            'next_run_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $job = new \App\Jobs\ProcessRecurringTransactions();
        $job->handle();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'amount' => 999.00,
            'description' => '[Auto-Recurring] Netflix 4K Family Subscription',
        ]);

        $rec->refresh();
        $this->assertTrue(now()->diffInDays($rec->next_run_date) >= 28);
    }

    /**
     * 3. Anomaly Detection: Statistical comparison with 90-day category average without fraud labeling.
     */
    public function test_anomaly_detection_statistical_rules_without_fraud_label(): void
    {
        // Populate historical category average: 3 past meals at ₹400 each (average = ₹400)
        for ($i = 1; $i <= 3; $i++) {
            Transaction::create([
                'user_id' => $this->user->id,
                'account_id' => $this->checkingAccount->id,
                'category_id' => $this->diningCat->id,
                'type' => 'expense',
                'amount' => 400.00,
                'transaction_date' => now()->subDays(10 * $i)->toDateString(),
                'description' => "Regular Dinner #{$i}",
                'payment_method' => 'upi',
                'status' => 'completed',
            ]);
        }

        // An unusually large transaction: ₹2,500 (6.25x average)
        $txService = app(\App\Services\TransactionService::class);
        $tx = $txService->recordTransaction($this->user, [
            'account_id' => $this->checkingAccount->id,
            'category_id' => $this->diningCat->id,
            'type' => 'expense',
            'amount' => 2500.00,
            'transaction_date' => now()->toDateString(),
            'description' => 'Luxury Anniversary Dinner at Taj',
            'payment_method' => 'card',
        ]);

        $anomalyService = app(AnomalyDetectionService::class);
        $eval = $anomalyService->evaluateTransaction($tx);

        $this->assertNotNull($eval);
        $this->assertTrue($eval['is_anomaly']);
        $this->assertEquals(400.00, $eval['historical_average']);
        $this->assertEquals(6.3, $eval['multiplier']);
        $this->assertStringContainsString('not independently labeled as fraudulent activity', $eval['ai_explanation']);

        // Check AiInsight created
        $this->assertDatabaseHas('ai_insights', [
            'user_id' => $this->user->id,
            'insight_type' => 'unusual_expense',
            'title' => "Unusual Expense Detected: Luxury Anniversary Dinner at Taj",
        ]);
    }

    /**
     * 4. Savings Goals: Track progress towards financial targets.
     */
    public function test_savings_goals_tracking_and_fund_deposit(): void
    {
        $token = $this->user->createToken('MobileApp')->plainTextToken;

        // Create goal via API
        $createRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/goals', [
                'name' => 'Goa Vacation Fund',
                'target_amount' => 30000.00,
                'current_amount' => 5000.00,
                'target_date' => now()->addMonths(6)->toDateString(),
                'category' => 'travel',
            ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('data.name', 'Goa Vacation Fund');

        $goalId = $createRes->json('data.id');

        // Deposit funds towards goal
        $depositRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/goals/{$goalId}/deposit", [
                'amount' => 10000.00,
            ]);

        $depositRes->assertStatus(200)
            ->assertJsonPath('data.current_amount', 15000)
            ->assertJsonPath('data.progress_percentage', 50);

        // List goals
        $listRes = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/goals');
        $listRes->assertStatus(200)
            ->assertJsonPath('data.total_target', 30000)
            ->assertJsonPath('data.total_saved', 15000);
    }

    /**
     * 5. Multi-Account Support: Manage cash, bank, and wallet balances with transfers.
     */
    public function test_multi_account_support_and_transfers(): void
    {
        $token = $this->user->createToken('MobileApp')->plainTextToken;

        // Transfer ₹5,000 from checking to savings
        $txService = app(\App\Services\TransactionService::class);
        $tx = $txService->recordTransaction($this->user, [
            'account_id' => $this->checkingAccount->id,
            'destination_account_id' => $this->savingsAccount->id,
            'type' => 'transfer',
            'amount' => 5000.00,
            'transaction_date' => now()->toDateString(),
            'description' => 'Monthly Savings Sweep',
            'payment_method' => 'bank_transfer',
        ]);

        $this->checkingAccount->refresh();
        $this->savingsAccount->refresh();

        $this->assertEquals(45000.00, (float) $this->checkingAccount->balance);
        $this->assertEquals(25000.00, (float) $this->savingsAccount->balance);
        $this->assertEquals(70000.00, (float) $this->user->fresh()->total_balance);
    }

    /**
     * 6. Multi-Currency Support: Support different currencies with explicit exchange rates.
     */
    public function test_multi_currency_explicit_exchange_rates_and_conversion(): void
    {
        $token = $this->user->createToken('MobileApp')->plainTextToken;

        $currencyService = app(CurrencyService::class);

        // Default baseline rate: 1 USD = 84.50 INR
        $rate = $currencyService->getExchangeRate('USD', 'INR');
        $this->assertEquals(84.50, $rate);

        $converted = $currencyService->convert(100.00, 'USD', 'INR');
        $this->assertEquals(8450.00, $converted);

        // Test API conversion
        $res = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/currencies/convert', [
                'amount' => 50,
                'from' => 'EUR',
                'to' => 'INR',
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.exchange_rate', 91.2)
            ->assertJsonPath('data.converted_amount', 4560);
    }

    /**
     * 7. Notifications: Alert when budget thresholds are crossed and unusual expenses occur.
     */
    public function test_budget_threshold_and_unusual_expense_notifications(): void
    {
        $token = $this->user->createToken('MobileApp')->plainTextToken;

        $budget = Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->diningCat->id,
            'name' => 'Monthly Dining Budget',
            'amount' => 5000.00,
            'period' => 'monthly',
            'alert_percentage' => 80,
            'status' => true,
        ]);

        $this->user->notify(new BudgetAlertNotification($budget, 85.0));

        $res = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/notifications');
        $res->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertGreaterThanOrEqual(1, $res->json('count'));
    }

    /**
     * 8. CSV Import: Import existing transaction records.
     */
    public function test_csv_import_of_transactions(): void
    {
        $token = $this->user->createToken('MobileApp')->plainTextToken;

        $csvContent = "Date,Description,Amount,Type,Category\n" .
                      "2026-11-01,Salary Direct Deposit,65000.00,income,Salary\n" .
                      "2026-11-03,Supermarket Groceries,3200.00,expense,Food & Dining\n" .
                      "2026-11-05,Winter Jacket,2800.00,expense,Shopping & Retail\n";

        $file = UploadedFile::fake()->createWithContent('bank_statement.csv', $csvContent);

        $res = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/transactions/import/csv', [
                'csv_file' => $file,
                'account_id' => $this->checkingAccount->id,
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('imported', 3)
            ->assertJsonPath('skipped', 0);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'description' => 'Winter Jacket',
            'amount' => 2800.00,
        ]);
    }

    /**
     * 9. Audit Trail: Track important financial changes.
     */
    public function test_audit_trail_logging_on_financial_mutations(): void
    {
        $token = $this->user->createToken('MobileApp')->plainTextToken;

        $txService = app(\App\Services\TransactionService::class);
        $tx = $txService->recordTransaction($this->user, [
            'account_id' => $this->checkingAccount->id,
            'type' => 'expense',
            'amount' => 1200.00,
            'transaction_date' => now()->toDateString(),
            'description' => 'Client Lunch',
            'payment_method' => 'card',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'transaction.created',
        ]);

        $res = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/audit-logs');
        $res->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertGreaterThanOrEqual(1, $res->json('count'));
    }

    /**
     * 10. Subscription Management & SaaS Quotas.
     */
    public function test_subscription_management_and_quota_upgrade(): void
    {
        $token = $this->user->createToken('MobileApp')->plainTextToken;

        $subService = app(SubscriptionService::class);
        $sub = $subService->getUserSubscription($this->user);

        $this->assertEquals('free', $sub['plan_id']);
        $this->assertEquals(20, $sub['daily_ai_limit']);

        // Upgrade to Pro via API
        $res = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/subscription/upgrade', [
                'plan' => 'pro',
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.plan_id', 'pro')
            ->assertJsonPath('data.daily_ai_limit', 500);

        $this->assertEquals('pro', $this->user->fresh()->subscription_plan);
    }

    /**
     * Production AI Integration: Cost tracking, Usage limits, and HTTP retry error handling.
     */
    public function test_ai_cost_tracking_and_usage_limits(): void
    {
        $client = app(AIClient::class);

        // Test cost calculation
        $cost = $client->calculateCost('gpt-4o-mini', 500, 200);
        $this->assertEquals(700, $cost['total_tokens']);
        $this->assertGreaterThan(0, $cost['cost_usd']);
        $this->assertGreaterThan(0, $cost['cost_inr']);

        // Test usage limits enforcement
        $this->user->update([
            'subscription_plan' => 'free',
            'daily_ai_queries_count' => 20,
            'last_ai_query_date' => now()->toDateString(),
        ]);

        $chatService = app(\App\Services\AI\AIChatService::class);
        $result = $chatService->chat($this->user, 'How much did I spend?');

        $this->assertStringContainsString('Daily AI query limit reached', $result['answer']);
        $this->assertStringContainsString('upgrade', strtolower($result['answer']));
    }
}
