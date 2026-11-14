<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AiConversation;
use App\Models\AiInsight;
use App\Models\AiMessage;
use App\Models\AiRecommendation;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\Category;
use App\Models\FinancialGoal;
use App\Models\Notification;
use App\Models\PaymentMethod;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseDesignAndIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_all_18_core_and_package_database_tables_exist(): void
    {
        $coreTables = [
            'users',
            'roles',
            'permissions',
            'categories',
            'transactions',
            'payment_methods',
            'accounts',
            'budgets',
            'budget_categories',
            'recurring_transactions',
            'attachments',
            'financial_goals',
            'notifications',
            'ai_conversations',
            'ai_messages',
            'ai_insights',
            'ai_recommendations',
            'audit_logs',
        ];

        foreach ($coreTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Core table [{$table}] must exist in database.");
        }

        $packageTables = [
            'jobs',
            'job_batches',
            'failed_jobs',
            'cache',
            'cache_locks',
            'sessions',
            'model_has_roles',
            'model_has_permissions',
            'role_has_permissions',
        ];

        foreach ($packageTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Package table [{$table}] must exist in database.");
        }
    }

    public function test_transactions_table_structure_and_relations_match_specification(): void
    {
        // 1. Verify exact columns required for transactions table
        $expectedColumns = [
            'id',
            'user_id',
            'account_id',
            'category_id',
            'payment_method_id',
            'type',
            'amount',
            'description',
            'transaction_date',
            'reference_number',
            'notes',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('transactions', $column),
                "Transactions table must contain the [{$column}] column as specified."
            );
        }

        // 2. Verify relationships between Transaction and PaymentMethod
        $tx = Transaction::withoutUserScope()->whereNotNull('payment_method_id')->first();
        $this->assertNotNull($tx, 'Seeded transactions must have payment_method_id populated.');
        $this->assertInstanceOf(PaymentMethod::class, $tx->paymentMethod);
        $this->assertNotEmpty($tx->paymentMethod->name);

        // Inverse relationship
        $pm = $tx->paymentMethod;
        $this->assertTrue($pm->transactions->contains('id', $tx->id));

        // 3. Verify relationships with User, Account, Category, Attachments
        $this->assertInstanceOf(User::class, $tx->user);
        $this->assertInstanceOf(Account::class, $tx->account);
        $this->assertInstanceOf(Category::class, $tx->category);
    }

    public function test_categories_table_structure_matches_specification(): void
    {
        $expectedColumns = [
            'id',
            'user_id',
            'name',
            'type',
            'icon',
            'color',
            'is_default',
            'status',
        ];

        foreach ($expectedColumns as $col) {
            $this->assertTrue(Schema::hasColumn('categories', $col), "Categories table missing [{$col}] column.");
        }

        $systemCategory = Category::whereNull('user_id')->first();
        $this->assertNotNull($systemCategory);
        $this->assertTrue($systemCategory->is_default);
        $this->assertTrue($systemCategory->status);
    }

    public function test_budgets_table_structure_matches_specification(): void
    {
        $expectedColumns = [
            'id',
            'user_id',
            'name',
            'amount',
            'period',
            'start_date',
            'end_date',
            'alert_percentage',
            'status',
        ];

        foreach ($expectedColumns as $col) {
            $this->assertTrue(Schema::hasColumn('budgets', $col), "Budgets table missing [{$col}] column.");
        }

        $budget = Budget::withoutUserScope()->first();
        $this->assertNotNull($budget);
        $this->assertNotNull($budget->start_date);
        $this->assertNotNull($budget->end_date);
        $this->assertGreaterThan(0, $budget->alert_percentage);
        $this->assertTrue((bool) $budget->getRawOriginal('status'));
    }

    public function test_accounts_table_structure_matches_specification(): void
    {
        $expectedColumns = [
            'id',
            'user_id',
            'account_name',
            'account_type',
            'opening_balance',
            'currency',
            'status',
        ];

        foreach ($expectedColumns as $col) {
            $this->assertTrue(Schema::hasColumn('accounts', $col), "Accounts table missing [{$col}] column.");
        }

        $account = Account::withoutUserScope()->first();
        $this->assertNotNull($account);
        $this->assertNotEmpty($account->account_name);
        $this->assertNotEmpty($account->account_type);
        $this->assertNotNull($account->opening_balance);
        $this->assertTrue($account->status);
    }

    public function test_ai_insights_table_structure_matches_specification(): void
    {
        $expectedColumns = [
            'id',
            'user_id',
            'insight_type',
            'title',
            'description',
            'period_start',
            'period_end',
            'data_snapshot',
            'generated_at',
        ];

        foreach ($expectedColumns as $col) {
            $this->assertTrue(Schema::hasColumn('ai_insights', $col), "AI Insights table missing [{$col}] column.");
        }

        $insight = AiInsight::withoutUserScope()->first();
        $this->assertNotNull($insight);
        $this->assertNotEmpty($insight->insight_type);
        $this->assertNotEmpty($insight->description);
        $this->assertNotNull($insight->generated_at);
    }

    public function test_ai_messages_table_structure_matches_specification(): void
    {
        $expectedColumns = [
            'id',
            'conversation_id',
            'role',
            'content',
            'token_usage',
            'created_at',
        ];

        foreach ($expectedColumns as $col) {
            $this->assertTrue(Schema::hasColumn('ai_messages', $col), "AI Messages table missing [{$col}] column.");
        }

        $msg = AiMessage::withoutUserScope()->first();
        $this->assertNotNull($msg);
        $this->assertNotEmpty($msg->content);
        $this->assertGreaterThan(0, $msg->token_usage);
        $this->assertNotNull($msg->created_at);
        $this->assertInstanceOf(AiConversation::class, $msg->conversation);
    }

    public function test_budget_categories_table_structure_and_relations(): void
    {
        $expectedColumns = [
            'id',
            'budget_id',
            'category_id',
            'allocated_amount',
        ];

        foreach ($expectedColumns as $col) {
            $this->assertTrue(Schema::hasColumn('budget_categories', $col), "Budget Categories table missing [{$col}] column.");
        }

        $budgetCategory = BudgetCategory::first();
        $this->assertNotNull($budgetCategory);
        $this->assertGreaterThan(0, $budgetCategory->allocated_amount);
        $this->assertInstanceOf(Budget::class, $budgetCategory->budget);
        $this->assertInstanceOf(Category::class, $budgetCategory->category);

        // Test relationships on Budget and Category
        $budget = $budgetCategory->budget;
        $this->assertTrue($budget->budgetCategories->contains('id', $budgetCategory->id));

        $category = $budgetCategory->category;
        $this->assertTrue($category->budgetCategories->contains('id', $budgetCategory->id));
    }

    public function test_user_cannot_access_or_view_another_users_transactions_via_eloquent(): void
    {
        $userA = User::where('email', 'alex@example.com')->first();
        $userB = User::where('email', 'sarah@example.com')->first();

        $this->assertNotNull($userA);
        $this->assertNotNull($userB);

        // User A has seeded transactions
        $userATxCount = Transaction::withoutUserScope()->where('user_id', $userA->id)->count();
        $this->assertGreaterThan(0, $userATxCount);

        // Authenticate as User B
        $this->actingAs($userB);

        // With UserScope, User B querying Transaction::all() should only see User B's transactions (0 at start)
        $userBVisibleTx = Transaction::all();
        $this->assertCount(0, $userBVisibleTx);

        // User B cannot find User A's transaction by ID
        $userATransaction = Transaction::withoutUserScope()->where('user_id', $userA->id)->first();
        $found = Transaction::find($userATransaction->id);
        $this->assertNull($found, "User B should not be able to find User A's transaction using Transaction::find()");
    }

    public function test_user_cannot_update_or_delete_another_users_transaction_via_http(): void
    {
        $userA = User::where('email', 'alex@example.com')->first();
        $userB = User::where('email', 'sarah@example.com')->first();

        $userATx = Transaction::withoutUserScope()->where('user_id', $userA->id)->first();
        $this->assertNotNull($userATx);

        // User B attempts to edit User A's transaction via JSON API / HTTP
        $response = $this->actingAs($userB)->getJson("/transactions/{$userATx->id}/edit");
        // Under UserScope, route model binding fails with 404 (or controller aborts with 403)
        $this->assertTrue(in_array($response->status(), [403, 404]), "User B should receive 403 or 404 when accessing User A's transaction edit route.");

        // User B attempts to delete User A's transaction
        $delResponse = $this->actingAs($userB)->delete("/transactions/{$userATx->id}");
        $this->assertTrue(in_array($delResponse->status(), [403, 404]), "User B should receive 403 or 404 when attempting to delete User A's transaction.");

        // Verify transaction still exists untouched in database
        $this->assertDatabaseHas('transactions', [
            'id' => $userATx->id,
            'user_id' => $userA->id,
        ]);
    }

    public function test_multi_tenant_isolation_on_accounts_budgets_and_recurring_transactions(): void
    {
        $userA = User::where('email', 'alex@example.com')->first();
        $userB = User::where('email', 'sarah@example.com')->first();

        // User A has accounts, budgets, and recurring transactions seeded
        $this->assertGreaterThan(0, Account::withoutUserScope()->where('user_id', $userA->id)->count());
        $this->assertGreaterThan(0, Budget::withoutUserScope()->where('user_id', $userA->id)->count());
        $this->assertGreaterThan(0, RecurringTransaction::withoutUserScope()->where('user_id', $userA->id)->count());

        // Authenticate as User B
        $this->actingAs($userB);

        // User B sees only their own account (1), and none of User A's 4 accounts
        $userBAccounts = Account::all();
        $this->assertCount(1, $userBAccounts);
        $this->assertEquals($userB->id, $userBAccounts->first()->user_id);
        $this->assertEquals('Primary Checking', $userBAccounts->first()->name);

        // User B sees 0 budgets and 0 recurring transactions (since only User A has them)
        $this->assertCount(0, Budget::all());
        $this->assertCount(0, RecurringTransaction::all());

        // Target User A's budget specifically by ID
        $userABudget = Budget::withoutUserScope()->where('user_id', $userA->id)->first();
        $this->assertNull(Budget::find($userABudget->id));
    }

    public function test_automatic_owner_association_on_model_creation(): void
    {
        $userB = User::where('email', 'sarah@example.com')->first();
        $this->actingAs($userB);

        $account = Account::create([
            'name' => 'Sarah Checking Account',
            'type' => 'checking',
            'balance' => 5000.00,
            'initial_balance' => 5000.00,
            'currency' => 'USD',
        ]);

        // user_id was auto-assigned by BelongsToUser trait
        $this->assertEquals($userB->id, $account->user_id);

        $notification = Notification::create([
            'type' => 'budget_alert',
            'title' => 'Weekly Allowance',
            'message' => '50% remaining',
        ]);

        $this->assertEquals($userB->id, $notification->user_id);
    }

    public function test_spatie_roles_and_permissions_are_enforced(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $business = User::where('email', 'alex@example.com')->first();
        $individual = User::where('email', 'sarah@example.com')->first();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($business->hasRole('small_business'));
        $this->assertTrue($individual->hasRole('individual'));

        // Admin has all permissions
        $this->assertTrue($admin->can('view_transactions'));
        $this->assertTrue($admin->can('manage_budgets'));
        $this->assertTrue($admin->can('view_audit_logs'));

        // Business has transaction and budget permissions
        $this->assertTrue($business->can('create_transactions'));
        $this->assertTrue($business->can('manage_budgets'));
        $this->assertFalse($business->can('view_audit_logs'));

        // Dynamic permission granting
        $individual->givePermissionTo('view_audit_logs');
        $this->assertTrue($individual->fresh()->can('view_audit_logs'));

        // Dynamic permission revocation
        $individual->revokePermissionTo('view_audit_logs');
        $this->assertFalse($individual->fresh()->can('view_audit_logs'));
    }

    public function test_payment_methods_scope_and_isolation(): void
    {
        $userA = User::where('email', 'alex@example.com')->first();
        $userB = User::where('email', 'sarah@example.com')->first();

        // System payment methods have user_id null and are accessible to both
        $systemMethods = PaymentMethod::whereNull('user_id')->get();
        $this->assertGreaterThan(0, $systemMethods->count());

        // Create custom private payment method for User A
        $userACustomMethod = PaymentMethod::create([
            'user_id' => $userA->id,
            'name' => 'Alex Corporate Amex Platinum',
            'code' => 'amex_platinum_alex',
            'icon' => 'bi-credit-card',
            'is_active' => true,
        ]);

        // User A querying forUser sees system methods + custom method
        $userAMethods = PaymentMethod::forUser($userA->id)->get();
        $this->assertTrue($userAMethods->contains('id', $userACustomMethod->id));

        // User B querying forUser DOES NOT see User A's custom method
        $userBMethods = PaymentMethod::forUser($userB->id)->get();
        $this->assertFalse($userBMethods->contains('id', $userACustomMethod->id));
    }

    public function test_cascade_delete_cleans_up_all_financial_records_when_user_is_deleted(): void
    {
        $tempUser = User::create([
            'name' => 'Temporary Test User',
            'email' => 'temp_isolation@example.com',
            'password' => bcrypt('password123'),
            'role' => 'individual',
            'currency' => 'USD',
        ]);

        $account = Account::create([
            'user_id' => $tempUser->id,
            'name' => 'Temp Account',
            'type' => 'checking',
            'balance' => 100.00,
        ]);

        $category = Category::create([
            'user_id' => $tempUser->id,
            'name' => 'Temp Category',
            'type' => 'expense',
        ]);

        $tx = Transaction::create([
            'user_id' => $tempUser->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Temp Expense',
            'payment_method' => 'cash',
        ]);

        $budget = Budget::create([
            'user_id' => $tempUser->id,
            'category_id' => $category->id,
            'name' => 'Temp Budget',
            'amount' => 200.00,
            'period' => 'monthly',
            'month_year' => now()->format('Y-m'),
        ]);

        $recTx = RecurringTransaction::create([
            'user_id' => $tempUser->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 25.00,
            'description' => 'Temp Subscription',
            'payment_method' => 'credit_card',
            'recurrence_interval' => 'monthly',
            'start_date' => now()->format('Y-m-d'),
            'next_run_date' => now()->addMonth()->format('Y-m-d'),
        ]);

        $attachment = Attachment::create([
            'user_id' => $tempUser->id,
            'attachable_type' => Transaction::class,
            'attachable_id' => $tx->id,
            'file_name' => 'temp.pdf',
            'file_path' => 'receipts/temp.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $notification = Notification::create([
            'user_id' => $tempUser->id,
            'type' => 'test',
            'title' => 'Test Notification',
            'message' => 'Test message',
        ]);

        $conv = AiConversation::create([
            'user_id' => $tempUser->id,
            'title' => 'Temp AI Chat',
        ]);

        $audit = AuditLog::create([
            'user_id' => $tempUser->id,
            'action' => 'test.action',
        ]);

        $userId = $tempUser->id;
        $tempUser->delete();

        // Foreign keys with cascadeOnDelete guarantee full cleanup
        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->assertDatabaseMissing('accounts', ['user_id' => $userId]);
        $this->assertDatabaseMissing('categories', ['user_id' => $userId]);
        $this->assertDatabaseMissing('transactions', ['user_id' => $userId]);
        $this->assertDatabaseMissing('budgets', ['user_id' => $userId]);
        $this->assertDatabaseMissing('recurring_transactions', ['user_id' => $userId]);
        $this->assertDatabaseMissing('attachments', ['user_id' => $userId]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $userId]);
        $this->assertDatabaseMissing('ai_conversations', ['user_id' => $userId]);
        $this->assertDatabaseMissing('audit_logs', ['user_id' => $userId]);
    }

    public function test_soft_deletes_are_enforced_on_important_financial_records(): void
    {
        $user = User::where('email', 'alex@example.com')->first();
        $this->actingAs($user);

        // 1. Transaction Soft Delete
        $tx = Transaction::where('user_id', $user->id)->first();
        $this->assertNotNull($tx);
        $txId = $tx->id;

        $tx->delete();

        // Must be marked as soft-deleted (deleted_at set), still present in database table
        $this->assertSoftDeleted('transactions', ['id' => $txId]);
        $this->assertNull(Transaction::find($txId));
        $this->assertNotNull(Transaction::withTrashed()->find($txId));

        // 2. Account Soft Delete
        $account = Account::where('user_id', $user->id)->first();
        $this->assertNotNull($account);
        $accId = $account->id;

        $account->delete();
        $this->assertSoftDeleted('accounts', ['id' => $accId]);
        $this->assertNull(Account::find($accId));
        $this->assertNotNull(Account::withTrashed()->find($accId));

        // 3. Budget Soft Delete
        $budget = Budget::where('user_id', $user->id)->first();
        $this->assertNotNull($budget);
        $budgetId = $budget->id;

        $budget->delete();
        $this->assertSoftDeleted('budgets', ['id' => $budgetId]);
        $this->assertNull(Budget::find($budgetId));
        $this->assertNotNull(Budget::withTrashed()->find($budgetId));

        // 4. Recurring Transaction Soft Delete
        $recTx = RecurringTransaction::where('user_id', $user->id)->first();
        $this->assertNotNull($recTx);
        $recId = $recTx->id;

        $recTx->delete();
        $this->assertSoftDeleted('recurring_transactions', ['id' => $recId]);
        $this->assertNull(RecurringTransaction::find($recId));
        $this->assertNotNull(RecurringTransaction::withTrashed()->find($recId));
    }

    public function test_deleting_category_retains_transaction_history_with_null_category(): void
    {
        $user = User::where('email', 'alex@example.com')->first();
        $this->actingAs($user);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Deprecating Expense Category',
            'type' => 'expense',
        ]);

        $account = Account::where('user_id', $user->id)->first();

        $tx = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 450.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Important Transaction That Must Never Be Lost',
            'payment_method' => 'bank_transfer',
        ]);

        $txId = $tx->id;
        $catId = $category->id;

        // Delete the category
        $category->delete();

        // Verify category is removed from categories table
        $this->assertDatabaseMissing('categories', ['id' => $catId]);

        // CRITICAL: Verify transaction history is completely preserved!
        $this->assertDatabaseHas('transactions', [
            'id' => $txId,
            'category_id' => null, // ON DELETE SET NULL ensures category_id becomes null
            'amount' => 450.00,
            'description' => 'Important Transaction That Must Never Be Lost',
        ]);

        $freshTx = Transaction::find($txId);
        $this->assertNotNull($freshTx);
        $this->assertNull($freshTx->category_id);
    }

    public function test_currency_stored_explicitly_and_defaults_to_inr(): void
    {
        // 1. User default currency
        $newUser = User::create([
            'name' => 'Rohan Sharma',
            'email' => 'rohan@example.com',
            'password' => bcrypt('password123'),
            'role' => 'individual',
        ]);

        $this->assertEquals('INR', $newUser->currency);

        // 2. Account default currency
        $newAccount = Account::create([
            'user_id' => $newUser->id,
            'name' => 'HDFC Salary Account',
            'type' => 'savings',
        ]);

        $this->assertEquals('INR', $newAccount->currency);

        // 3. Transaction default currency
        $newTx = Transaction::create([
            'user_id' => $newUser->id,
            'account_id' => $newAccount->id,
            'type' => 'income',
            'amount' => 85000.00,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Monthly Salary',
            'payment_method' => 'bank_transfer',
        ]);

        $this->assertEquals('INR', $newTx->currency);

        // 4. Explicit non-INR currency is stored as specified
        $usdAccount = Account::create([
            'user_id' => $newUser->id,
            'name' => 'Payoneer USD Wallet',
            'type' => 'checking',
            'currency' => 'USD',
        ]);

        $this->assertEquals('USD', $usdAccount->currency);
    }

    public function test_database_transactions_used_during_financial_mutations(): void
    {
        $user = User::where('email', 'alex@example.com')->first();
        $account = Account::where('user_id', $user->id)->first();
        $initialBalance = $account->balance;

        // Verify multi-record update within DB::transaction
        DB::transaction(function () use ($user, $account) {
            Transaction::create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'type' => 'income',
                'amount' => 1000.00,
                'transaction_date' => now()->format('Y-m-d'),
                'description' => 'Transaction block test',
                'payment_method' => 'bank_transfer',
            ]);

            $account->recalculateBalance();
        });

        $account->refresh();
        $this->assertEquals($initialBalance + 1000.00, $account->balance);
    }

    public function test_er_diagram_entity_relationships_are_fully_wired_and_verified(): void
    {
        $user = User::where('email', 'alex@example.com')->first();
        $this->actingAs($user);

        // 1. USERS owns ACCOUNTS
        $this->assertTrue($user->accounts()->exists());
        $account = $user->accounts()->first();
        $this->assertEquals($user->id, $account->user->id);

        // 2. ACCOUNTS contains TRANSACTIONS
        $this->assertTrue($account->transactions()->exists());
        $tx = $account->transactions()->first();
        $this->assertEquals($account->id, $tx->account->id);

        // 3. USERS creates TRANSACTIONS
        $this->assertTrue($user->transactions()->exists());
        $this->assertEquals($user->id, $tx->user->id);

        // 4. TRANSACTIONS has ATTACHMENTS
        $attachment = Attachment::create([
            'user_id' => $user->id,
            'attachable_type' => Transaction::class,
            'attachable_id' => $tx->id,
            'file_name' => 'receipt.png',
            'file_path' => 'receipts/receipt.png',
            'file_type' => 'image/png',
            'file_size' => 1024,
            'description' => 'Business Receipt',
        ]);
        $this->assertTrue($tx->attachments->contains('id', $attachment->id));

        // 5. CATEGORIES classifies TRANSACTIONS
        $this->assertNotNull($tx->category);
        $this->assertTrue($tx->category->transactions->contains('id', $tx->id));

        // 6. PAYMENT_METHODS pays TRANSACTIONS
        $this->assertNotNull($tx->paymentMethod);
        $this->assertTrue($tx->paymentMethod->transactions->contains('id', $tx->id));

        // 7. USERS sets FINANCIAL_GOALS
        $goal = FinancialGoal::create([
            'user_id' => $user->id,
            'name' => 'Emergency Fund',
            'target_amount' => 100000.00,
            'current_amount' => 25000.00,
            'target_date' => now()->addMonths(6)->toDateString(),
            'status' => 'in_progress',
        ]);
        $this->assertTrue($user->financialGoals->contains('id', $goal->id));
        $this->assertEquals($user->id, $goal->user->id);

        // 8. USERS creates CATEGORIES
        $customCat = Category::create([
            'user_id' => $user->id,
            'name' => 'Client Entertainment',
            'type' => 'expense',
            'color' => '#6366f1',
        ]);
        $this->assertTrue($user->categories->contains('id', $customCat->id));
        $this->assertEquals($user->id, $customCat->user->id);

        // 9. USERS manages BUDGETS
        $this->assertTrue($user->budgets()->exists());
        $budget = $user->budgets()->first();
        $this->assertEquals($user->id, $budget->user->id);

        // 10. BUDGETS includes BUDGET_CATEGORIES & CATEGORIES allocates BUDGET_CATEGORIES
        $budgetCategory = BudgetCategory::create([
            'budget_id' => $budget->id,
            'category_id' => $customCat->id,
            'allocated_amount' => 15000.00,
            'spending_limit' => 15000.00,
        ]);
        $this->assertTrue($budget->budgetCategories->contains('id', $budgetCategory->id));
        $this->assertTrue($customCat->budgetCategories->contains('id', $budgetCategory->id));
        $this->assertEquals($budget->id, $budgetCategory->budget->id);
        $this->assertEquals($customCat->id, $budgetCategory->category->id);

        // 11. USERS starts AI_CONVERSATIONS & AI_CONVERSATIONS contains AI_MESSAGES
        $conversation = AiConversation::create([
            'user_id' => $user->id,
            'title' => 'Tax Reduction Strategies',
            'context_type' => 'tax_planning',
            'status' => 'active',
        ]);
        $this->assertTrue($user->aiConversations->contains('id', $conversation->id));

        $msg = AiMessage::create([
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'What can I deduct this quarter?',
            'token_usage' => 45,
        ]);
        $this->assertTrue($conversation->messages->contains('id', $msg->id));
        $this->assertEquals($conversation->id, $msg->conversation->id);

        // 12. USERS receives AI_INSIGHTS
        $this->assertTrue($user->aiInsights()->exists());
        $insight = $user->aiInsights()->first();
        $this->assertEquals($user->id, $insight->user->id);

        // 13. USERS receives AI_RECOMMENDATIONS
        $recommendation = AiRecommendation::create([
            'user_id' => $user->id,
            'insight_id' => $insight->id,
            'category_id' => $customCat->id,
            'title' => 'Consolidate SaaS subscriptions',
            'recommendation_type' => 'subscription_reduction',
            'potential_savings' => 1200.00,
            'priority' => 'high',
            'status' => 'pending',
        ]);
        $this->assertTrue($user->aiRecommendations->contains('id', $recommendation->id));
        $this->assertEquals($user->id, $recommendation->user->id);
        $this->assertEquals($insight->id, $recommendation->insight->id);
    }
}
