<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsAndAnalyticsTest extends TestCase
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
            'name' => 'Report Test User',
            'role' => 'small_business',
            'business_name' => 'Apex Analytics Ltd',
            'currency' => 'USD',
        ]);

        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Primary Checking Account',
            'type' => 'business_checking',
            'balance' => 30000.00,
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
            'name' => 'Cloud Compute',
            'type' => 'expense',
            'icon' => 'bi-server',
            'color' => '#6366f1',
        ]);

        // Seed some sample transactions
        // Income
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'income',
            'amount' => 5000.00,
            'transaction_date' => '2026-11-05',
            'description' => 'Consulting Invoice #101',
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
        ]);

        // Expense in parent category
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->parentCategory->id,
            'type' => 'expense',
            'amount' => 300.00,
            'transaction_date' => '2026-11-09',
            'description' => 'GitHub Enterprise Team',
            'payment_method' => 'credit_card',
            'status' => 'completed',
        ]);

        // Expense in subcategory
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->subCategory->id,
            'type' => 'expense',
            'amount' => 700.00,
            'transaction_date' => '2026-11-10',
            'description' => 'AWS Production Database',
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
        ]);
    }

    public function test_daily_report_view_renders_correctly(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/reports?period=daily&date=2026-11-10');
        $response->assertStatus(200);
        $response->assertSee('Daily Report');
        $response->assertSee('AWS Production Database');
        $response->assertSee('700.00');
    }

    public function test_weekly_report_view_renders_correctly(): void
    {
        $this->actingAs($this->user);

        // 2026-11-10 is Tuesday of that week
        $response = $this->get('/reports?period=weekly&week=2026-11-10');
        $response->assertStatus(200);
        $response->assertSee('Weekly Report');
        $response->assertSee('GitHub Enterprise Team');
        $response->assertSee('AWS Production Database');
    }

    public function test_monthly_report_view_renders_with_trends_and_categories(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/reports?period=monthly&month=2026-11');
        $response->assertStatus(200);
        $response->assertSee('Financial Reports &amp; Analytics', false);
        $response->assertSee('5,000.00'); // Income
        $response->assertSee('1,000.00'); // Total Expenses (300 + 700)
        $response->assertSee('4,000.00'); // Net Savings
        $response->assertSee('80%');      // Savings Rate (4000/5000 = 80%)
        $response->assertSee('Software &amp; Subscriptions', false);
    }

    public function test_category_wise_expenses_rolls_up_subcategories(): void
    {
        $reportService = app(ReportService::class);
        $data = $reportService->getReportData($this->user, 'monthly', '2026-11');

        $this->assertNotEmpty($data['category_breakdown']);
        $cat = $data['category_breakdown'][0];

        $this->assertEquals('Software & Subscriptions', $cat['name']);
        // 300 (parent) + 700 (child) = 1000
        $this->assertEquals(1000.00, $cat['total_spent']);
        $this->assertEquals(2, $cat['transaction_count']);
        $this->assertEquals(100.0, $cat['share_percentage']);
    }

    public function test_savings_analysis_metrics(): void
    {
        $reportService = app(ReportService::class);
        $data = $reportService->getReportData($this->user, 'monthly', '2026-11');

        $savingsAnalysis = $data['savings_analysis'];

        $this->assertEquals(4000.00, $savingsAnalysis['net_savings']);
        $this->assertEquals(80.0, $savingsAnalysis['savings_rate']);
        $this->assertEquals('excellent', $savingsAnalysis['status']);
        $this->assertEquals(48000.00, $savingsAnalysis['projected_annual_savings']); // 4000 * 12
    }

    public function test_pdf_report_download(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/reports/pdf?period=monthly&month=2026-11');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_excel_and_csv_reports_download(): void
    {
        $this->actingAs($this->user);

        // Test XLSX download
        $excelResponse = $this->get('/reports/excel?period=monthly&month=2026-11&format=xlsx');
        $excelResponse->assertStatus(200);

        // Test CSV download
        $csvResponse = $this->get('/reports/excel?period=monthly&month=2026-11&format=csv');
        $csvResponse->assertStatus(200);
    }

    public function test_reports_api_endpoints_work_with_sanctum(): void
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        // 1. GET /api/v1/reports/summary
        $summaryRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/reports/summary?period=monthly&month=2026-11');

        $summaryRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.income', 5000)
            ->assertJsonPath('summary.expense', 1000)
            ->assertJsonPath('summary.savings', 4000);

        // 2. GET /api/v1/reports/trends
        $trendsRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/reports/trends?period=monthly&month=2026-11');

        $trendsRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'period', 'trends' => ['labels', 'income', 'expense', 'net']]);

        // 3. GET /api/v1/reports/category-expenses
        $catRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/reports/category-expenses?period=monthly&month=2026-11');

        $catRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('category_expenses.0.name', 'Software & Subscriptions')
            ->assertJsonPath('category_expenses.0.total_spent', 1000);
    }
}
