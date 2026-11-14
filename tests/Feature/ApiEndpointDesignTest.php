<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEndpointDesignTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->user = User::where('email', 'alex@example.com')->first();
        $this->token = $this->user->createToken('TestClient')->plainTextToken;
    }

    // ==========================================
    // 1. Authentication APIs
    // ==========================================

    public function test_api_user_registration(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Kavita Verma',
            'email' => 'kavita@example.com',
            'password' => 'SecurePass123!',
            'currency' => 'INR',
            'business_name' => 'Verma Consulting',
            'role' => 'individual',
            'device_name' => 'FlutterMobileApp',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'token', 'user' => ['id', 'name', 'email', 'currency']]);

        $this->assertDatabaseHas('users', [
            'email' => 'kavita@example.com',
            'currency' => 'INR',
        ]);

        // Default checking account automatically initialized
        $this->assertDatabaseHas('accounts', [
            'name' => 'Primary Account',
            'currency' => 'INR',
        ]);
    }

    public function test_api_user_login_and_logout(): void
    {
        // Login
        $loginRes = $this->postJson('/api/v1/login', [
            'email' => 'alex@example.com',
            'password' => 'password123',
            'device_name' => 'iOS App',
        ]);

        $loginRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'token', 'user']);

        $token = $loginRes->json('token');

        // Current User Profile
        $userRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/user');

        $userRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', 'alex@example.com');

        // Logout (Revoke Token)
        $logoutRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/logout');

        $logoutRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Token revoked successfully');
    }

    public function test_api_forgot_password(): void
    {
        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => 'alex@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    // ==========================================
    // 2. Transaction APIs
    // ==========================================

    public function test_api_transaction_crud_and_summary(): void
    {
        $account = $this->user->accounts()->first();
        $category = Category::where('type', 'expense')->first();
        $paymentMethod = PaymentMethod::first();

        // 1. POST /api/v1/transactions
        $createRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/transactions', [
                'account_id' => $account->id,
                'category_id' => $category?->id,
                'payment_method_id' => $paymentMethod?->id,
                'type' => 'expense',
                'amount' => 2500.00,
                'transaction_date' => now()->format('Y-m-d'),
                'description' => 'Office Broadband Bill',
                'reference_number' => 'REF-API-101',
                'notes' => 'Gigabit fiber connection',
            ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.amount', '2500.00')
            ->assertJsonPath('data.description', 'Office Broadband Bill');

        $txId = $createRes->json('data.id');

        // 2. GET /api/v1/transactions/{id}
        $getRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/v1/transactions/{$txId}");

        $getRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $txId);

        // 3. PUT /api/v1/transactions/{id}
        $updateRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/v1/transactions/{$txId}", [
                'account_id' => $account->id,
                'category_id' => $category?->id,
                'type' => 'expense',
                'amount' => 2750.00,
                'transaction_date' => now()->format('Y-m-d'),
                'description' => 'Office Broadband Bill (Updated with Static IP)',
                'payment_method' => 'bank_transfer',
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.amount', '2750.00')
            ->assertJsonPath('data.description', 'Office Broadband Bill (Updated with Static IP)');

        // 4. GET /api/v1/transactions/summary
        $summaryRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/transactions/summary?month=' . now()->format('Y-m'));

        $summaryRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'month', 'summary' => ['total_income', 'total_expense', 'net_savings', 'transactions_count']]);

        // 5. DELETE /api/v1/transactions/{id}
        $delRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/v1/transactions/{$txId}");

        $delRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Transaction deleted successfully');

        $this->assertSoftDeleted('transactions', ['id' => $txId]);
    }

    public function test_api_transaction_ownership_authorization_policy(): void
    {
        $userB = User::where('email', 'sarah@example.com')->first();
        $tokenB = $userB->createToken('SarahApp')->plainTextToken;

        $userATransaction = Transaction::withoutGlobalScopes()->where('user_id', $this->user->id)->first();
        $this->assertNotNull($userATransaction);

        // Sarah cannot view Alex's transaction
        $viewRes = $this->withHeader('Authorization', 'Bearer ' . $tokenB)
            ->getJson("/api/v1/transactions/{$userATransaction->id}");
        $viewRes->assertStatus(403);

        // Sarah cannot update Alex's transaction
        $updateRes = $this->withHeader('Authorization', 'Bearer ' . $tokenB)
            ->putJson("/api/v1/transactions/{$userATransaction->id}", [
                'account_id' => $userB->accounts()->first()->id,
                'type' => 'expense',
                'amount' => 500,
                'transaction_date' => now()->format('Y-m-d'),
                'description' => 'Hijacked transaction',
                'payment_method' => 'cash',
            ]);
        $updateRes->assertStatus(403);

        // Sarah cannot delete Alex's transaction
        $delRes = $this->withHeader('Authorization', 'Bearer ' . $tokenB)
            ->deleteJson("/api/v1/transactions/{$userATransaction->id}");
        $delRes->assertStatus(403);
    }

    // ==========================================
    // 3. Budget APIs
    // ==========================================

    public function test_api_budget_crud_and_progress(): void
    {
        $category = Category::where('type', 'expense')->first();

        // 1. POST /api/v1/budgets
        $createRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/budgets', [
                'name' => 'Q4 Cloud Infrastructure Budget',
                'category_id' => $category?->id,
                'amount' => 12000.00,
                'spending_limit' => 15000.00,
                'period' => 'monthly',
                'month_year' => now()->format('Y-m'),
                'alert_threshold' => 85,
            ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('budget.name', 'Q4 Cloud Infrastructure Budget');

        $budgetId = $createRes->json('budget.id');

        // 2. GET /api/v1/budgets/{id}
        $getRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/v1/budgets/{$budgetId}");

        $getRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('budget.id', $budgetId);

        // 3. GET /api/v1/budgets/{id}/progress
        $progRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/v1/budgets/{$budgetId}/progress");

        $progRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['budget_id', 'amount', 'spending_limit', 'spent', 'remaining', 'utilization_percentage', 'status']]);

        // 4. PUT /api/v1/budgets/{id}
        $updateRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/v1/budgets/{$budgetId}", [
                'amount' => 14000.00,
                'spending_limit' => 16000.00,
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('budget.amount', '14000.00');

        // 5. GET /api/v1/budgets/progress
        $allProgRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/budgets/progress');

        $allProgRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'summary', 'budgets']);

        // 6. DELETE /api/v1/budgets/{id}
        $delRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/v1/budgets/{$budgetId}");

        $delRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('budgets', ['id' => $budgetId]);
    }

    public function test_api_budget_ownership_authorization_policy(): void
    {
        $userB = User::where('email', 'sarah@example.com')->first();
        $tokenB = $userB->createToken('SarahApp')->plainTextToken;

        $userABudget = Budget::withoutGlobalScopes()->where('user_id', $this->user->id)->first();
        $this->assertNotNull($userABudget);

        // Sarah cannot access Alex's budget
        $viewRes = $this->withHeader('Authorization', 'Bearer ' . $tokenB)
            ->getJson("/api/v1/budgets/{$userABudget->id}");
        $viewRes->assertStatus(403);

        // Sarah cannot delete Alex's budget
        $delRes = $this->withHeader('Authorization', 'Bearer ' . $tokenB)
            ->deleteJson("/api/v1/budgets/{$userABudget->id}");
        $delRes->assertStatus(403);
    }

    // ==========================================
    // 4. AI APIs
    // ==========================================

    public function test_api_ai_endpoints(): void
    {
        // 1. POST /api/v1/ai/chat
        $chatRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/ai/chat', [
                'question' => 'How can I reduce operational expenses this quarter?',
            ]);

        $chatRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'conversation_id', 'question', 'answer', 'tokens_used']);

        $convoId = $chatRes->json('conversation_id');

        // 2. GET /api/v1/ai/conversations
        $convoRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/ai/conversations');

        $convoRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data']);

        // 3. GET /api/v1/ai/insights
        $insightsRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/ai/insights');

        $insightsRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'count', 'insights']);

        // 4. POST /api/v1/ai/analyze
        $analyzeRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/ai/analyze');

        $analyzeRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'health_score', 'snapshot', 'insights_count']);

        // 5. GET /api/v1/ai/recommendations
        $recRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/ai/recommendations');

        $recRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'total_potential_savings', 'currency', 'recommendations']);

        // 6. POST /api/v1/ai/summarize
        $sumRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/ai/summarize', [
                'month' => now()->format('Y-m'),
            ]);

        $sumRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'month', 'summary']);
    }

    // ==========================================
    // 5. Report APIs
    // ==========================================

    public function test_api_report_endpoints_and_exports(): void
    {
        // 1. GET /api/v1/reports/monthly
        $monthlyRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/reports/monthly?month=' . now()->format('Y-m'));

        $monthlyRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('period', 'monthly')
            ->assertJsonStructure(['success', 'period', 'summary', 'category_breakdown', 'trends', 'savings_analysis']);

        // 2. GET /api/v1/reports/yearly
        $yearlyRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/reports/yearly?year=' . now()->format('Y'));

        $yearlyRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('period', 'yearly')
            ->assertJsonStructure(['success', 'period', 'summary', 'category_breakdown', 'trends']);

        // 3. GET /api/v1/reports/categories
        $catRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/reports/categories?period=monthly');

        $catRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'period', 'total_expense', 'categories']);

        // 4. GET /api/v1/reports/export/pdf
        $pdfRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->get('/api/v1/reports/export/pdf?period=monthly');

        $pdfRes->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $pdfRes->headers->get('content-type'));

        // 5. GET /api/v1/reports/export/excel
        $excelRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->get('/api/v1/reports/export/excel?period=monthly&format=csv');

        $excelRes->assertStatus(200);
        $this->assertStringContainsString('text/csv', $excelRes->headers->get('content-type'));
    }

    // ==========================================
    // 6. Security & Throttling
    // ==========================================

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/transactions');
        $response->assertStatus(401);
    }
}

