<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;
    protected Category $parentCategory;
    protected Category $subCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'small_business',
            'business_name' => 'Acme Corporation',
            'currency' => 'USD',
        ]);

        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Operating Account',
            'type' => 'business_checking',
            'balance' => 20000.00,
            'is_active' => true,
        ]);

        $this->parentCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Software & Subscriptions',
            'type' => 'expense',
            'icon' => 'bi-cloud',
            'color' => '#6366f1',
        ]);

        $this->subCategory = Category::create([
            'user_id' => $this->user->id,
            'parent_id' => $this->parentCategory->id,
            'name' => 'Cloud Hosting AWS',
            'type' => 'expense',
            'icon' => 'bi-server',
            'color' => '#6366f1',
        ]);
    }

    public function test_user_can_view_budgets_dashboard_monthly_and_yearly(): void
    {
        $this->actingAs($this->user);

        // Monthly budget
        Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->parentCategory->id,
            'name' => 'Monthly Cloud',
            'amount' => 500.00,
            'spending_limit' => 600.00,
            'period' => 'monthly',
            'month_year' => '2026-11',
            'alert_threshold' => 80,
            'notify_on_exceed' => true,
        ]);

        // Yearly budget
        Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->parentCategory->id,
            'name' => 'Yearly Infrastructure',
            'amount' => 6000.00,
            'spending_limit' => 7000.00,
            'period' => 'yearly',
            'month_year' => '2026',
            'alert_threshold' => 75,
            'notify_on_exceed' => true,
        ]);

        // Monthly view
        $monthlyResponse = $this->get('/budgets?period=monthly&month=2026-11');
        $monthlyResponse->assertStatus(200);
        $monthlyResponse->assertSee('Monthly Cloud');
        $monthlyResponse->assertSee('Spending Limits');

        // Yearly view
        $yearlyResponse = $this->get('/budgets?period=yearly&year=2026');
        $yearlyResponse->assertStatus(200);
        $yearlyResponse->assertSee('Yearly Infrastructure');
    }

    public function test_user_can_create_monthly_and_yearly_budgets_with_spending_limits(): void
    {
        $this->actingAs($this->user);

        // Create Monthly Budget
        $response = $this->post('/budgets', [
            'name' => 'SaaS Stack Monthly',
            'category_id' => $this->parentCategory->id,
            'amount' => 800.00,
            'spending_limit' => 1000.00,
            'period' => 'monthly',
            'month_year' => '2026-11',
            'alert_threshold' => 85,
            'notify_on_exceed' => 1,
            'notes' => 'Team developer licenses',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'name' => 'SaaS Stack Monthly',
            'amount' => 800.00,
            'spending_limit' => 1000.00,
            'period' => 'monthly',
            'month_year' => '2026-11',
            'alert_threshold' => 85,
            'notes' => 'Team developer licenses',
        ]);

        // Create Yearly Budget
        $responseYearly = $this->post('/budgets', [
            'name' => 'Annual SaaS Ceiling',
            'category_id' => $this->parentCategory->id,
            'amount' => 9600.00,
            'spending_limit' => 11000.00,
            'period' => 'yearly',
            'month_year' => '2026',
            'alert_threshold' => 80,
            'notify_on_exceed' => 1,
        ]);

        $responseYearly->assertRedirect();
        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'name' => 'Annual SaaS Ceiling',
            'amount' => 9600.00,
            'spending_limit' => 11000.00,
            'period' => 'yearly',
            'month_year' => '2026',
        ]);
    }

    public function test_subcategory_expenses_roll_up_into_category_budget(): void
    {
        $budget = Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->parentCategory->id,
            'name' => 'Monthly Software Target',
            'amount' => 500.00,
            'spending_limit' => 500.00,
            'period' => 'monthly',
            'month_year' => '2026-11',
            'alert_threshold' => 80,
        ]);

        // Record expense assigned to the child subcategory
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->subCategory->id,
            'type' => 'expense',
            'amount' => 350.00,
            'transaction_date' => '2026-11-05',
            'description' => 'AWS Production Cluster',
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
        ]);

        // The parent budget should reflect the subcategory's spent amount
        $this->assertEquals(350.00, $budget->spent);
        $this->assertEquals(150.00, $budget->remaining);
        $this->assertEquals(70.0, $budget->utilization_percentage);
        $this->assertEquals('normal', $budget->status);
    }

    public function test_budget_alert_triggers_when_threshold_and_spending_limits_breached(): void
    {
        $budget = Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->parentCategory->id,
            'name' => 'Strict Software Cap',
            'amount' => 400.00,
            'spending_limit' => 500.00,
            'period' => 'monthly',
            'month_year' => '2026-11',
            'alert_threshold' => 80, // 80% of limit $500 = $400
        ]);

        // Expense of $420 pushes it into Warning (84% of limit)
        $tx = Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->parentCategory->id,
            'type' => 'expense',
            'amount' => 420.00,
            'transaction_date' => '2026-11-08',
            'description' => 'Enterprise Licenses',
            'payment_method' => 'credit_card',
            'status' => 'completed',
        ]);

        $this->assertEquals('warning', $budget->status);
        $this->assertEquals(84.0, $budget->utilization_percentage);

        // Expense of additional $100 (total $520) pushes it over spending limit $500 -> Exceeded
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->parentCategory->id,
            'type' => 'expense',
            'amount' => 100.00,
            'transaction_date' => '2026-11-10',
            'description' => 'Additional Seat License',
            'payment_method' => 'credit_card',
            'status' => 'completed',
        ]);

        $this->assertEquals('exceeded', $budget->status);
        $this->assertEquals(20.00, $budget->overspent_amount);
        $this->assertFalse($budget->is_favorable);
    }

    public function test_recording_transaction_flashes_budget_alert_notification(): void
    {
        $this->actingAs($this->user);

        Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->parentCategory->id,
            'name' => 'Monthly Software',
            'amount' => 200.00,
            'spending_limit' => 200.00,
            'period' => 'monthly',
            'month_year' => now()->format('Y-m'),
            'alert_threshold' => 80,
        ]);

        // Post transaction that will exceed this budget
        $response = $this->post('/transactions', [
            'account_id' => $this->account->id,
            'category_id' => $this->parentCategory->id,
            'type' => 'expense',
            'amount' => 250.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Annual IDE Renewal',
            'payment_method' => 'credit_card',
        ]);

        $response->assertSessionHas('budget_alert');
        $sessionAlert = session('budget_alert');
        $this->assertEquals('danger', $sessionAlert['severity']);
        $this->assertStringContainsString('limit exceeded', strtolower($sessionAlert['message']));
    }

    public function test_budget_vs_actual_comparison_and_csv_export(): void
    {
        $this->actingAs($this->user);

        Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->parentCategory->id,
            'name' => 'Software Budget',
            'amount' => 1000.00,
            'spending_limit' => 1200.00,
            'period' => 'monthly',
            'month_year' => '2026-11',
            'alert_threshold' => 80,
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->parentCategory->id,
            'type' => 'expense',
            'amount' => 650.00,
            'transaction_date' => '2026-11-04',
            'description' => 'Monthly Cloud Billing',
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
        ]);

        // Test web view contains comparison tab
        $response = $this->get('/budgets?tab=vs_actual&month=2026-11');
        $response->assertStatus(200);
        $response->assertSee('Budget vs. Actual Spending');
        $response->assertSee('Software &amp; Subscriptions', false);
        $response->assertSee('650.00');

        // Test CSV download
        $csvResponse = $this->get('/budgets/export/csv?period=monthly&month=2026-11');
        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_budget_api_endpoints_work_with_sanctum(): void
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->parentCategory->id,
            'name' => 'API Tested Budget',
            'amount' => 500.00,
            'spending_limit' => 600.00,
            'period' => 'monthly',
            'month_year' => '2026-11',
            'alert_threshold' => 80,
        ]);

        // 1. GET /api/v1/budgets
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/budgets?period=monthly&month=2026-11');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('budgets.0.name', 'API Tested Budget');

        // 2. GET /api/v1/budgets/comparison
        $compResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/budgets/comparison?period=monthly&month=2026-11');

        $compResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['comparison', 'chart_data']);

        // 3. POST /api/v1/budgets
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/budgets', [
                'name' => 'New API Budget',
                'category_id' => $this->parentCategory->id,
                'amount' => 300.00,
                'spending_limit' => 350.00,
                'period' => 'monthly',
                'month_year' => '2026-11',
                'alert_threshold' => 75,
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('budget.name', 'New API Budget');
    }
}
