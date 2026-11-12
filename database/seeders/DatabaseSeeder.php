<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AiInsight;
use App\Models\Budget;
use App\Models\Category;
use App\Models\FinancialGoal;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Default Categories
        $categoriesData = [
            // Income
            ['name' => 'Salary & Wages', 'type' => 'income', 'icon' => 'bi-cash-coin', 'color' => '#10b981'],
            ['name' => 'Client Revenue / Sales', 'type' => 'income', 'icon' => 'bi-shop', 'color' => '#059669'],
            ['name' => 'Freelance / Consulting', 'type' => 'income', 'icon' => 'bi-laptop', 'color' => '#3b82f6'],
            ['name' => 'Investments & Dividends', 'type' => 'income', 'icon' => 'bi-graph-up-arrow', 'color' => '#8b5cf6'],
            ['name' => 'Refunds & Grants', 'type' => 'income', 'icon' => 'bi-arrow-counterclockwise', 'color' => '#06b6d4'],

            // Expense
            ['name' => 'Rent & Facilities', 'type' => 'expense', 'icon' => 'bi-building', 'color' => '#ef4444'],
            ['name' => 'Utilities & Internet', 'type' => 'expense', 'icon' => 'bi-lightning-charge', 'color' => '#f59e0b'],
            ['name' => 'Software & Subscriptions', 'type' => 'expense', 'icon' => 'bi-cloud', 'color' => '#6366f1'],
            ['name' => 'Payroll & Contractors', 'type' => 'expense', 'icon' => 'bi-people', 'color' => '#ec4899'],
            ['name' => 'Marketing & Advertising', 'type' => 'expense', 'icon' => 'bi-megaphone', 'color' => '#84cc16'],
            ['name' => 'Office Supplies & Equipment', 'type' => 'expense', 'icon' => 'bi-printer', 'color' => '#14b8a6'],
            ['name' => 'Food & Business Dining', 'type' => 'expense', 'icon' => 'bi-cup-hot', 'color' => '#f97316'],
            ['name' => 'Travel & Transportation', 'type' => 'expense', 'icon' => 'bi-car-front', 'color' => '#64748b'],
            ['name' => 'Taxes & Legal Fees', 'type' => 'expense', 'icon' => 'bi-receipt', 'color' => '#dc2626'],
            ['name' => 'Insurance', 'type' => 'expense', 'icon' => 'bi-shield-check', 'color' => '#0284c7'],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[$c['name']] = Category::create($c);
        }

        // 2. Create Demo User (Small Business / Freelancer)
        $user = User::create([
            'name' => 'Alex Morgan',
            'email' => 'alex@example.com',
            'password' => Hash::make('password123'),
            'role' => 'small_business',
            'business_name' => 'Apex Digital Solutions LLC',
            'currency' => 'USD',
            'phone' => '+1 (555) 234-5678',
            'ai_provider' => 'openai',
            'ai_model' => 'gpt-4o-mini',
        ]);

        // Also create a second individual account
        $individual = User::create([
            'name' => 'Sarah Connor',
            'email' => 'sarah@example.com',
            'password' => Hash::make('password123'),
            'role' => 'individual',
            'business_name' => null,
            'currency' => 'USD',
            'phone' => '+1 (555) 987-6543',
        ]);

        // 3. Create Accounts for Alex Morgan
        $bizChecking = Account::create([
            'user_id' => $user->id,
            'name' => 'Business Operating (Chase)',
            'type' => 'business_checking',
            'account_number_last4' => '4821',
            'balance' => 24500.00,
            'initial_balance' => 15000.00,
            'currency' => 'USD',
            'color' => '#2563eb',
            'description' => 'Main operational account for daily inflows and outflows',
        ]);

        $bizSavings = Account::create([
            'user_id' => $user->id,
            'name' => 'Tax & Reserve Savings',
            'type' => 'savings',
            'account_number_last4' => '9012',
            'balance' => 18200.00,
            'initial_balance' => 12000.00,
            'currency' => 'USD',
            'color' => '#10b981',
            'description' => 'Reserved funds for upcoming quarterly tax payments and 3-month runway',
        ]);

        $bizCard = Account::create([
            'user_id' => $user->id,
            'name' => 'Corporate Credit Card (Amex)',
            'type' => 'credit_card',
            'account_number_last4' => '3304',
            'balance' => -1840.50,
            'initial_balance' => 0.00,
            'currency' => 'USD',
            'color' => '#8b5cf6',
            'description' => 'Used for SaaS subscriptions, digital ads, and travel',
        ]);

        $pettyCash = Account::create([
            'user_id' => $user->id,
            'name' => 'Office Petty Cash',
            'type' => 'cash',
            'account_number_last4' => null,
            'balance' => 450.00,
            'initial_balance' => 500.00,
            'currency' => 'USD',
            'color' => '#f59e0b',
            'description' => 'On-hand cash for miscellaneous supplies and courier',
        ]);

        // Accounts for Sarah
        Account::create([
            'user_id' => $individual->id,
            'name' => 'Primary Checking',
            'type' => 'checking',
            'account_number_last4' => '1122',
            'balance' => 4850.00,
            'initial_balance' => 3000.00,
            'currency' => 'USD',
            'color' => '#2563eb',
        ]);

        // 4. Create Historical & Current Month Transactions for Alex
        $now = Carbon::now();
        $currentMonth = $now->format('Y-m');
        $lastMonth = $now->copy()->subMonth()->format('Y-m');
        $twoMonthsAgo = $now->copy()->subMonths(2)->format('Y-m');

        $sampleTransactions = [
            // Current Month Income
            [
                'account_id' => $bizChecking->id,
                'category_id' => $categories['Client Revenue / Sales']->id,
                'type' => 'income',
                'amount' => 8500.00,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(2)->format('Y-m-d'),
                'description' => 'Retainer: Nexus Health Inc Web Development',
                'payee_payer' => 'Nexus Health Inc',
                'payment_method' => 'bank_transfer',
                'reference_number' => 'ACH-88491',
            ],
            [
                'account_id' => $bizChecking->id,
                'category_id' => $categories['Client Revenue / Sales']->id,
                'type' => 'income',
                'amount' => 4200.00,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(6)->format('Y-m-d'),
                'description' => 'Milestone 2: FinTech Mobile App Design',
                'payee_payer' => 'Starlight Media',
                'payment_method' => 'bank_transfer',
                'reference_number' => 'STR-1049',
            ],
            [
                'account_id' => $bizChecking->id,
                'category_id' => $categories['Investments & Dividends']->id,
                'type' => 'income',
                'amount' => 340.00,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(10)->format('Y-m-d'),
                'description' => 'Vanguard High-Yield Savings Interest',
                'payee_payer' => 'Vanguard Group',
                'payment_method' => 'bank_transfer',
                'reference_number' => 'INT-2026',
            ],

            // Current Month Expenses
            [
                'account_id' => $bizChecking->id,
                'category_id' => $categories['Rent & Facilities']->id,
                'type' => 'expense',
                'amount' => 1800.00,
                'fee' => 0,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(1)->format('Y-m-d'),
                'description' => 'Office Space Lease (WeWork Suite 402)',
                'payee_payer' => 'WeWork Real Estate',
                'payment_method' => 'bank_transfer',
                'reference_number' => 'CHK-309',
            ],
            [
                'account_id' => $bizChecking->id,
                'category_id' => $categories['Payroll & Contractors']->id,
                'type' => 'expense',
                'amount' => 2400.00,
                'fee' => 0,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(5)->format('Y-m-d'),
                'description' => 'Bi-weekly Developer Contractor - Elena Rostova',
                'payee_payer' => 'Elena Rostova',
                'payment_method' => 'bank_transfer',
                'reference_number' => 'PAY-2026-01',
            ],
            [
                'account_id' => $bizCard->id,
                'category_id' => $categories['Software & Subscriptions']->id,
                'type' => 'expense',
                'amount' => 249.00,
                'fee' => 0,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(3)->format('Y-m-d'),
                'description' => 'AWS Cloud Infrastructure Hosting',
                'payee_payer' => 'Amazon Web Services',
                'payment_method' => 'card',
                'reference_number' => 'INV-AWS-883',
            ],
            [
                'account_id' => $bizCard->id,
                'category_id' => $categories['Software & Subscriptions']->id,
                'type' => 'expense',
                'amount' => 120.00,
                'fee' => 0,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(4)->format('Y-m-d'),
                'description' => 'GitHub Enterprise & Copilot seats',
                'payee_payer' => 'GitHub Inc',
                'payment_method' => 'card',
            ],
            [
                'account_id' => $bizCard->id,
                'category_id' => $categories['Marketing & Advertising']->id,
                'type' => 'expense',
                'amount' => 650.00,
                'fee' => 0,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(8)->format('Y-m-d'),
                'description' => 'Google Search Ads campaign',
                'payee_payer' => 'Google Ads',
                'payment_method' => 'card',
            ],
            [
                'account_id' => $bizChecking->id,
                'category_id' => $categories['Utilities & Internet']->id,
                'type' => 'expense',
                'amount' => 185.00,
                'fee' => 0,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(7)->format('Y-m-d'),
                'description' => 'Gigabit Fiber Internet & VoIP',
                'payee_payer' => 'AT&T Business',
                'payment_method' => 'bank_transfer',
            ],
            [
                'account_id' => $pettyCash->id,
                'category_id' => $categories['Food & Business Dining']->id,
                'type' => 'expense',
                'amount' => 74.50,
                'fee' => 0,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(9)->format('Y-m-d'),
                'description' => 'Team lunch brainstorm meeting',
                'payee_payer' => 'Blue Bottle Cafe',
                'payment_method' => 'cash',
            ],

            // Last Month (Historical)
            [
                'account_id' => $bizChecking->id,
                'category_id' => $categories['Client Revenue / Sales']->id,
                'type' => 'income',
                'amount' => 11200.00,
                'transaction_date' => $now->copy()->subMonth()->startOfMonth()->addDays(5)->format('Y-m-d'),
                'description' => 'Q3 Enterprise Portal Delivery',
                'payee_payer' => 'Acrobat Corp',
                'payment_method' => 'bank_transfer',
            ],
            [
                'account_id' => $bizChecking->id,
                'category_id' => $categories['Rent & Facilities']->id,
                'type' => 'expense',
                'amount' => 1800.00,
                'transaction_date' => $now->copy()->subMonth()->startOfMonth()->addDays(1)->format('Y-m-d'),
                'description' => 'Office Space Lease',
                'payee_payer' => 'WeWork Real Estate',
                'payment_method' => 'bank_transfer',
            ],
            [
                'account_id' => $bizChecking->id,
                'category_id' => $categories['Payroll & Contractors']->id,
                'type' => 'expense',
                'amount' => 4800.00,
                'transaction_date' => $now->copy()->subMonth()->startOfMonth()->addDays(15)->format('Y-m-d'),
                'description' => 'Monthly Contractors Total',
                'payee_payer' => 'Various Contractors',
                'payment_method' => 'bank_transfer',
            ],
            [
                'account_id' => $bizCard->id,
                'category_id' => $categories['Marketing & Advertising']->id,
                'type' => 'expense',
                'amount' => 850.00,
                'transaction_date' => $now->copy()->subMonth()->startOfMonth()->addDays(12)->format('Y-m-d'),
                'description' => 'LinkedIn B2B Lead Gen Campaign',
                'payee_payer' => 'LinkedIn Ads',
                'payment_method' => 'card',
            ],

            // 2 Months Ago
            [
                'account_id' => $bizChecking->id,
                'category_id' => $categories['Client Revenue / Sales']->id,
                'type' => 'income',
                'amount' => 9500.00,
                'transaction_date' => $now->copy()->subMonths(2)->startOfMonth()->addDays(10)->format('Y-m-d'),
                'description' => 'Mobile App Phase 1 Delivery',
                'payee_payer' => 'Starlight Media',
                'payment_method' => 'bank_transfer',
            ],
            [
                'account_id' => $bizChecking->id,
                'category_id' => $categories['Rent & Facilities']->id,
                'type' => 'expense',
                'amount' => 1800.00,
                'transaction_date' => $now->copy()->subMonths(2)->startOfMonth()->addDays(1)->format('Y-m-d'),
                'description' => 'Office Space Lease',
                'payee_payer' => 'WeWork Real Estate',
                'payment_method' => 'bank_transfer',
            ],
            [
                'account_id' => $bizCard->id,
                'category_id' => $categories['Software & Subscriptions']->id,
                'type' => 'expense',
                'amount' => 520.00,
                'transaction_date' => $now->copy()->subMonths(2)->startOfMonth()->addDays(8)->format('Y-m-d'),
                'description' => 'Figma, GitHub, Notion, AWS',
                'payee_payer' => 'Various Tech SaaS',
                'payment_method' => 'card',
            ],
        ];

        foreach ($sampleTransactions as $tx) {
            $tx['user_id'] = $user->id;
            $tx['status'] = 'completed';
            Transaction::create($tx);
        }

        // Recalculate balances
        $bizChecking->recalculateBalance();
        $bizSavings->recalculateBalance();
        $bizCard->recalculateBalance();
        $pettyCash->recalculateBalance();

        // 5. Create Monthly Budgets for Alex
        Budget::create([
            'user_id' => $user->id,
            'category_id' => $categories['Software & Subscriptions']->id,
            'name' => 'Monthly SaaS & Cloud Stack',
            'amount' => 500.00,
            'period' => 'monthly',
            'month_year' => $currentMonth,
            'alert_threshold' => 80,
            'notify_on_exceed' => true,
        ]);

        Budget::create([
            'user_id' => $user->id,
            'category_id' => $categories['Marketing & Advertising']->id,
            'name' => 'Digital Ads & Growth',
            'amount' => 1000.00,
            'period' => 'monthly',
            'month_year' => $currentMonth,
            'alert_threshold' => 85,
            'notify_on_exceed' => true,
        ]);

        Budget::create([
            'user_id' => $user->id,
            'category_id' => $categories['Food & Business Dining']->id,
            'name' => 'Client & Team Hospitality',
            'amount' => 300.00,
            'period' => 'monthly',
            'month_year' => $currentMonth,
            'alert_threshold' => 75,
            'notify_on_exceed' => true,
        ]);

        Budget::create([
            'user_id' => $user->id,
            'category_id' => $categories['Rent & Facilities']->id,
            'name' => 'Facility Lease & Office Operations',
            'amount' => 2000.00,
            'period' => 'monthly',
            'month_year' => $currentMonth,
            'alert_threshold' => 90,
            'notify_on_exceed' => false,
        ]);

        // 6. Create Financial Goals
        FinancialGoal::create([
            'user_id' => $user->id,
            'account_id' => $bizSavings->id,
            'name' => '6-Month Operational Runway',
            'target_amount' => 30000.00,
            'current_amount' => 18200.00,
            'target_date' => $now->copy()->addMonths(5)->format('Y-m-d'),
            'category' => 'emergency_fund',
            'notes' => 'Safety cushion covering all recurring leases and salaries.',
            'status' => 'in_progress',
        ]);

        FinancialGoal::create([
            'user_id' => $user->id,
            'account_id' => $bizChecking->id,
            'name' => 'AI Workstation & Hardware Upgrade',
            'target_amount' => 5000.00,
            'current_amount' => 3500.00,
            'target_date' => $now->copy()->addMonths(2)->format('Y-m-d'),
            'category' => 'equipment',
            'notes' => 'High-memory workstations for local LLM inference and rendering.',
            'status' => 'in_progress',
        ]);

        // 7. Create Invoices for Small Business
        $inv1 = Invoice::create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-2026-001',
            'client_name' => 'Nexus Health Inc',
            'client_email' => 'billing@nexushealth.example',
            'client_address' => '742 Evergreen Terrace, Seattle, WA 98101',
            'issue_date' => $now->copy()->startOfMonth()->format('Y-m-d'),
            'due_date' => $now->copy()->startOfMonth()->addDays(15)->format('Y-m-d'),
            'subtotal' => 8500.00,
            'tax_rate' => 0.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 8500.00,
            'currency' => 'USD',
            'status' => 'paid',
            'notes' => 'Thank you for your business. Payment received via Wire.',
        ]);

        InvoiceItem::create([
            'invoice_id' => $inv1->id,
            'description' => 'Monthly Retainer - Full Stack Cloud Architecture',
            'quantity' => 1,
            'unit_price' => 8500.00,
            'total' => 8500.00,
        ]);

        $inv2 = Invoice::create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-2026-002',
            'client_name' => 'Starlight Media Co',
            'client_email' => 'accounts@starlightmedia.example',
            'client_address' => '100 Sunset Blvd, Los Angeles, CA 90028',
            'issue_date' => $now->copy()->startOfMonth()->addDays(5)->format('Y-m-d'),
            'due_date' => $now->copy()->startOfMonth()->addDays(20)->format('Y-m-d'),
            'subtotal' => 4500.00,
            'tax_rate' => 5.00,
            'tax_amount' => 225.00,
            'discount_amount' => 0.00,
            'total_amount' => 4725.00,
            'currency' => 'USD',
            'status' => 'sent',
            'notes' => 'Net 15 payment terms. Please submit to ACH account 4821.',
        ]);

        InvoiceItem::create([
            'invoice_id' => $inv2->id,
            'description' => 'Design Sprint & Figma Interactive Prototypes',
            'quantity' => 30,
            'unit_price' => 150.00,
            'total' => 4500.00,
        ]);

        // 8. Create AI Insights & Recommendations
        AiInsight::create([
            'user_id' => $user->id,
            'type' => 'savings_opportunity',
            'title' => 'Software Subscription Creep Detected',
            'summary' => 'You have 4 active design and developer tool subscriptions totaling $369/mo. Consolidating inactive Figma editor licenses could save ~$95/mo.',
            'detailed_analysis' => 'Based on transaction patterns over the last 90 days, multiple duplicate recurring charges were found. We identified 3 seats billed under GitHub and Figma with zero activity recorded. Removing dormant seats yields annual savings of $1,140.',
            'priority' => 'medium',
            'action_suggested' => 'Audit team member seat permissions under Settings > Subscriptions.',
        ]);

        AiInsight::create([
            'user_id' => $user->id,
            'type' => 'cashflow_forecast',
            'title' => 'Positive 30-Day Cashflow Projection',
            'summary' => 'Projected net surplus of $7,450 by month end with 82% confidence. Expected invoice receivables cover all upcoming operational liabilities.',
            'detailed_analysis' => 'Incoming receivables: $4,725 (INV-2026-002) due in 9 days. Scheduled outflows: Rent ($1,800) paid, Payroll ($2,400) remaining. Net projected operational buffer remains strong at $38,000+ across all liquid accounts.',
            'priority' => 'low',
            'action_suggested' => 'Consider transferring $3,500 surplus into high-yield tax reserve.',
        ]);

        AiInsight::create([
            'user_id' => $user->id,
            'type' => 'budget_warning',
            'title' => 'Marketing & Ad Spend Pace Alert',
            'summary' => 'You have utilized 65.0% of your $1,000 Marketing budget within the first 12 days of the month.',
            'detailed_analysis' => 'At your current burn rate of $54.17/day on Google Ads, you will surpass your $1,000 threshold by day 19, reaching an estimated $1,625 by month end unless daily ad caps are dialed back.',
            'priority' => 'high',
            'action_suggested' => 'Adjust Google Ads daily budget from $80/day to $40/day to stay within limits.',
        ]);
    }
}
