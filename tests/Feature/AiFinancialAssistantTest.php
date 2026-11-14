<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AiInsight;
use App\Models\Budget;
use App\Models\Category;
use App\Models\MonthlyFinancialSummary;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AiFinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiFinancialAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;
    protected Category $softwareCat;
    protected Category $diningCat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Demo Founder',
            'role' => 'small_business',
            'business_name' => 'Nexus Systems LLC',
            'currency' => 'USD',
        ]);

        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Operating Chase Checking',
            'type' => 'business_checking',
            'balance' => 25000.00,
            'is_active' => true,
        ]);

        $this->softwareCat = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Software & Subscriptions',
            'type' => 'expense',
            'icon' => 'bi-cloud',
            'color' => '#6366f1',
        ]);

        $this->diningCat = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Food & Business Dining',
            'type' => 'expense',
            'icon' => 'bi-cup-hot',
            'color' => '#f97316',
        ]);
    }

    public function test_ai_advisor_dashboard_renders_with_tabs_and_metrics(): void
    {
        $this->actingAs($this->user);

        // Record a transaction
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->softwareCat->id,
            'type' => 'expense',
            'amount' => 450.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'AWS & Cloud Hosting',
            'payment_method' => 'credit_card',
            'status' => 'completed',
        ]);

        $response = $this->get('/ai-advisor');
        $response->assertStatus(200);
        $response->assertSee('AI Financial Assistant');
        $response->assertSee('Financial Health Score');
        $response->assertSee('Ask Questions About Your Spending');
        $response->assertSee('Unusual Expenses');
        $response->assertSee('Saving Suggestions');
        $response->assertSee('Budget Recommendations');
        $response->assertSee('Monthly Summaries');
    }

    public function test_unusual_expense_detection_identifies_anomalies(): void
    {
        // Total monthly expense: $1000, of which $750 is a single large transaction (>30%)
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->softwareCat->id,
            'type' => 'expense',
            'amount' => 750.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Massive Server Overhaul',
            'payee_payer' => 'AWS Cloud Services',
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->diningCat->id,
            'type' => 'expense',
            'amount' => 150.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Team Lunch',
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $aiService = app(AiFinancialService::class);
        $anomalies = $aiService->detectUnusualExpenses($this->user);

        $this->assertNotEmpty($anomalies);
        $this->assertStringContainsString('Unusual Large Outlay', $anomalies[0]['title']);
        $this->assertEquals(750.00, $anomalies[0]['metrics_snapshot']['amount']);
    }

    public function test_personalized_saving_suggestions_generation(): void
    {
        // Monthly income & software expenses
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'income',
            'amount' => 8000.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Client Retainer',
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->softwareCat->id,
            'type' => 'expense',
            'amount' => 500.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Developer SaaS Subscriptions',
            'payment_method' => 'credit_card',
            'status' => 'completed',
        ]);

        $aiService = app(AiFinancialService::class);
        $tips = $aiService->generatePersonalizedSavingSuggestions($this->user);

        $this->assertNotEmpty($tips);

        $titles = collect($tips)->pluck('title')->toArray();
        $this->assertTrue(collect($titles)->contains(fn($t) => str_contains($t, 'SaaS Subscription Audit')));
        $this->assertTrue(collect($titles)->contains(fn($t) => str_contains($t, 'Reserve Sweep')));
    }

    public function test_monthly_financial_summary_generation_and_export(): void
    {
        $this->actingAs($this->user);

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'income',
            'amount' => 10000.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Project Milestone',
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->softwareCat->id,
            'type' => 'expense',
            'amount' => 1200.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Cloud Cluster',
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
        ]);

        $aiService = app(AiFinancialService::class);
        $summary = $aiService->generateMonthlySummary($this->user);

        $this->assertInstanceOf(MonthlyFinancialSummary::class, $summary);
        $this->assertEquals(10000.00, (float) $summary->total_income);
        $this->assertEquals(1200.00, (float) $summary->total_expenses);
        $this->assertEquals(8800.00, (float) $summary->net_savings);
        $this->assertGreaterThan(0, $summary->health_score);
        $this->assertStringContainsString('recognized revenue', strtolower($summary->executive_summary));

        // Test download text report
        $downloadResponse = $this->get('/ai-advisor/summary/download?month=' . now()->format('Y-m'));
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('content-type', 'text/plain; charset=UTF-8');
    }

    public function test_budget_recommendations_and_one_click_apply(): void
    {
        $this->actingAs($this->user);

        // Record spending in a category that has NO budget
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->softwareCat->id,
            'type' => 'expense',
            'amount' => 380.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Github and CI tool',
            'payment_method' => 'credit_card',
            'status' => 'completed',
        ]);

        $aiService = app(AiFinancialService::class);
        $recs = $aiService->generateBudgetRecommendations($this->user);

        $this->assertNotEmpty($recs);
        $this->assertEquals($this->softwareCat->id, $recs[0]['metrics_snapshot']['category_id']);

        // Create an insight from the recommendation
        $insight = AiInsight::create([
            'user_id' => $this->user->id,
            'type' => 'budget_recommendation',
            'title' => $recs[0]['title'],
            'summary' => $recs[0]['summary'],
            'metrics_snapshot' => $recs[0]['metrics_snapshot'],
        ]);

        // Test Applying the recommendation via HTTP POST
        $response = $this->post("/ai-advisor/{$insight->id}/apply-budget");
        $response->assertRedirect('/budgets');

        // Verify budget was created in database
        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category_id' => $this->softwareCat->id,
            'period' => 'monthly',
        ]);

        $createdBudget = Budget::where('user_id', $this->user->id)
            ->where('category_id', $this->softwareCat->id)
            ->first();

        $this->assertNotNull($createdBudget);
        $this->assertGreaterThanOrEqual(380.00, (float) $createdBudget->amount);

        // Verify insight was marked action_taken
        $insight->refresh();
        $this->assertTrue($insight->action_taken);
    }

    public function test_ask_questions_about_spending_interactive_qa(): void
    {
        $this->actingAs($this->user);

        // Seed transactions
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->softwareCat->id,
            'type' => 'expense',
            'amount' => 600.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'AWS Cloud Compute',
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->diningCat->id,
            'type' => 'expense',
            'amount' => 120.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Executive Dinner',
            'payment_method' => 'credit_card',
            'status' => 'completed',
        ]);

        // Query 1: Category spending
        $res1 = $this->postJson('/ai-advisor/ask', [
            'query' => 'How much did I spend on Software & Subscriptions this month?',
        ]);

        $res1->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertStringContainsString('600.00', $res1->json('answer'));

        // Query 2: Largest expense
        $res2 = $this->postJson('/ai-advisor/ask', [
            'query' => 'What was my largest expense this month?',
        ]);

        $res2->assertStatus(200);
        $this->assertStringContainsString('AWS Cloud Compute', $res2->json('answer'));
        $this->assertStringContainsString('600.00', $res2->json('answer'));

        // Query 3: Runway
        $res3 = $this->postJson('/ai-advisor/ask', [
            'query' => 'What is my current cash runway?',
        ]);

        $res3->assertStatus(200);
        $this->assertStringContainsString('runway', strtolower($res3->json('answer')));

        // Verify messages were stored in ai_chat_messages table
        $this->assertDatabaseHas('ai_chat_messages', [
            'user_id' => $this->user->id,
            'role' => 'user',
            'message' => 'How much did I spend on Software & Subscriptions this month?',
        ]);

        $this->assertDatabaseHas('ai_chat_messages', [
            'user_id' => $this->user->id,
            'role' => 'assistant',
        ]);
    }

    public function test_ai_api_endpoints_work(): void
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        // 1. POST /api/v1/ai/generate
        $genResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/ai/generate');

        $genResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        // 2. GET /api/v1/ai/insights
        $insightsResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/ai/insights');

        $insightsResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'count', 'insights']);

        // 3. GET /api/v1/ai/summary
        $summaryResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/ai/summary');

        $summaryResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'month', 'summary']);

        // 4. POST /api/v1/ai/ask
        $askResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/ai/ask', [
                'query' => 'Give me a summary of my financial standing',
            ]);

        $askResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'question', 'answer']);
    }

    public function test_core_ai_features_chat_assistant_spending_analysis_and_budget_recommendations(): void
    {
        // Setup realistic Indian Rupee (INR) user profile and transactions
        $inrUser = User::create([
            'name' => 'Vikram Malhotra',
            'email' => 'vikram@example.com',
            'password' => bcrypt('password123'),
            'currency' => 'INR',
            'role' => 'individual',
        ]);

        $account = Account::create([
            'user_id' => $inrUser->id,
            'name' => 'HDFC Savings',
            'type' => 'savings',
            'balance' => 50000.00,
            'currency' => 'INR',
        ]);

        $foodCat = Category::create(['user_id' => $inrUser->id, 'name' => 'Food', 'type' => 'expense']);
        $shopCat = Category::create(['user_id' => $inrUser->id, 'name' => 'Shopping', 'type' => 'expense']);
        $transCat = Category::create(['user_id' => $inrUser->id, 'name' => 'Transport', 'type' => 'expense']);

        // Set budget on Transport to test "transport expenses are within budget"
        Budget::create([
            'user_id' => $inrUser->id,
            'category_id' => $transCat->id,
            'name' => 'Transport Budget',
            'amount' => 5000.00,
            'spending_limit' => 5000.00,
            'period' => 'monthly',
            'month_year' => now()->format('Y-m'),
            'currency' => 'INR',
        ]);

        // Previous Month Transactions: Total expenses = ₹28,000 (Food = ₹6,667, Shopping = ₹12,000, Transport = ₹3,000)
        $prevMonth = now()->subMonth()->format('Y-m');
        Transaction::create([
            'user_id' => $inrUser->id,
            'account_id' => $account->id,
            'category_id' => $foodCat->id,
            'type' => 'expense',
            'amount' => 6666.67,
            'transaction_date' => "{$prevMonth}-15",
            'description' => 'Previous Month Groceries',
        ]);
        Transaction::create([
            'user_id' => $inrUser->id,
            'account_id' => $account->id,
            'category_id' => $shopCat->id,
            'type' => 'expense',
            'amount' => 12000.00,
            'transaction_date' => "{$prevMonth}-20",
            'description' => 'Previous Month Shopping',
        ]);
        Transaction::create([
            'user_id' => $inrUser->id,
            'account_id' => $account->id,
            'category_id' => $transCat->id,
            'type' => 'expense',
            'amount' => 3000.00,
            'transaction_date' => "{$prevMonth}-22",
            'description' => 'Previous Month Fuel',
        ]);
        Transaction::create([
            'user_id' => $inrUser->id,
            'account_id' => $account->id,
            'type' => 'income',
            'amount' => 40000.00,
            'transaction_date' => "{$prevMonth}-01",
            'description' => 'Salary',
        ]);

        // Current Month Transactions: Total Income = ₹40,000, Total Expenses = ₹32,000, Savings = ₹8,000
        $currMonth = now()->format('Y-m');
        Transaction::create([
            'user_id' => $inrUser->id,
            'account_id' => $account->id,
            'type' => 'income',
            'amount' => 40000.00,
            'transaction_date' => "{$currMonth}-01",
            'description' => 'Monthly Salary',
        ]);
        // Food: ₹8,000 (+20% increase from ₹6,666.67)
        Transaction::create([
            'user_id' => $inrUser->id,
            'account_id' => $account->id,
            'category_id' => $foodCat->id,
            'type' => 'expense',
            'amount' => 8000.00,
            'transaction_date' => "{$currMonth}-05",
            'description' => 'Current Month Food & Groceries',
        ]);
        // Shopping: ₹12,500 (Highest expense category)
        Transaction::create([
            'user_id' => $inrUser->id,
            'account_id' => $account->id,
            'category_id' => $shopCat->id,
            'type' => 'expense',
            'amount' => 12500.00,
            'transaction_date' => "{$currMonth}-10",
            'description' => 'Electronics & Clothes Shopping',
        ]);
        // Transport: ₹2,500 (Within budget of ₹5,000, 50% utilized)
        Transaction::create([
            'user_id' => $inrUser->id,
            'account_id' => $account->id,
            'category_id' => $transCat->id,
            'type' => 'expense',
            'amount' => 2500.00,
            'transaction_date' => "{$currMonth}-12",
            'description' => 'Fuel & Metro',
        ]);
        // Other essential expenses: ₹9,000 -> Total expenses = 8,000 + 12,500 + 2,500 + 9,000 = 32,000
        Transaction::create([
            'user_id' => $inrUser->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 9000.00,
            'transaction_date' => "{$currMonth}-15",
            'description' => 'Utilities & Rent',
        ]);

        $token = $inrUser->createToken('MobileApp')->plainTextToken;

        // ==========================================
        // 1. AI Financial Chat Assistant Queries
        // ==========================================

        // Query A: "How much did I spend this month?"
        $resA = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/ai/chat', [
                'question' => 'How much did I spend this month?',
            ]);
        $resA->assertStatus(200);
        $this->assertStringContainsString('32,000', $resA->json('answer'));
        $this->assertStringContainsString('₹', $resA->json('answer'));

        // Query B: "Which category has the highest expenses?"
        $resB = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/ai/chat', [
                'question' => 'Which category has the highest expenses?',
            ]);
        $resB->assertStatus(200);
        $this->assertStringContainsString('Shopping', $resB->json('answer'));
        $this->assertStringContainsString('12,500', $resB->json('answer'));

        // Query C: "How can I save ₹5,000 next month?"
        $resC = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/ai/chat', [
                'question' => 'How can I save ₹5,000 next month?',
            ]);
        $resC->assertStatus(200);
        $this->assertStringContainsString('5,000', $resC->json('answer'));
        $this->assertStringContainsString('Shopping', $resC->json('answer'));

        // Query D: "Compare my expenses with last month."
        $resD = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/ai/chat', [
                'question' => 'Compare my expenses with last month.',
            ]);
        $resD->assertStatus(200);
        $this->assertStringContainsString('32,000', $resD->json('answer'));
        $this->assertStringContainsString('Food', $resD->json('answer'));

        // ==========================================
        // 2. AI Spending Analysis Verification
        // ==========================================
        $analysisService = app(\App\Services\AI\FinancialAnalysisService::class);
        $summary = $analysisService->getAuthorizedFinancialSummary($inrUser, $currMonth);

        $this->assertNotEmpty($summary['spending_analysis']);
        $analysisText = implode(' ', $summary['spending_analysis']);
        $this->assertStringContainsString('Food expenses increased by 20%', $analysisText);
        $this->assertStringContainsString('Shopping is your highest expense category', $analysisText);
        $this->assertStringContainsString('savings rate', $analysisText);
        $this->assertStringContainsString('transport expenses are within budget', strtolower($analysisText));

        // ==========================================
        // 3. AI Budget Recommendations Verification
        // ==========================================
        $bRec = $summary['budget_recommendation'];
        $this->assertEquals(40000.0, $bRec['monthly_income']);
        $this->assertEquals(32000.0, $bRec['monthly_expenses']);
        $this->assertEquals(8000.0, $bRec['savings']);
        $this->assertStringContainsString('Review discretionary spending', $bRec['ai_suggestion']);
        $this->assertStringContainsString('10,000', $bRec['ai_suggestion']);

        // Also verify Blade view renders these sections
        $viewRes = $this->actingAs($inrUser)->get('/ai-advisor');
        $viewRes->assertStatus(200)
            ->assertSee('Food expenses increased by 20%')
            ->assertSee('Shopping is your highest expense category')
            ->assertSee('Review discretionary spending')
            ->assertSee('₹10,000');
    }

    public function test_ai_client_chat_direct_http_call(): void
    {
        config([
            'services.ai.key' => 'test-mock-key-12345',
            'services.ai.url' => 'https://api.openai.com/v1',
            'services.ai.model' => 'gpt-4o-mini',
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'https://api.openai.com/v1/chat/completions' => \Illuminate\Support\Facades\Http::response([
                'id' => 'chatcmpl-test789',
                'object' => 'chat.completion',
                'created' => time(),
                'model' => 'gpt-4o-mini',
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Finora AI live response: Your finances are well balanced.',
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 15,
                    'completion_tokens' => 12,
                    'total_tokens' => 27,
                ],
            ], 200),
        ]);

        $client = app(\App\Services\AI\AIClient::class);
        $messages = [
            ['role' => 'system', 'content' => 'You are Finora AI.'],
            ['role' => 'user', 'content' => 'Summarize my spending.'],
        ];

        $response = $client->chat($messages);

        $this->assertIsArray($response);
        $this->assertEquals('chatcmpl-test789', $response['id']);
        $this->assertEquals('Finora AI live response: Your finances are well balanced.', $response['choices'][0]['message']['content']);
        $this->assertEquals(27, $response['usage']['total_tokens']);

        \Illuminate\Support\Facades\Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() === 'https://api.openai.com/v1/chat/completions' &&
                   $request->hasHeader('Authorization', 'Bearer test-mock-key-12345') &&
                   $request['model'] === 'gpt-4o-mini' &&
                   count($request['messages']) === 2;
        });
    }
}

