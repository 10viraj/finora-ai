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
        // 1. Create Parent Categories
        $parentCategoriesData = [
            // Income
            ['name' => 'Salary & Wages', 'type' => 'income', 'icon' => 'bi-cash-coin', 'color' => '#10b981'],
            ['name' => 'Client Revenue & Sales', 'type' => 'income', 'icon' => 'bi-shop', 'color' => '#059669'],
            ['name' => 'Freelance & Consulting', 'type' => 'income', 'icon' => 'bi-laptop', 'color' => '#3b82f6'],
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
        foreach ($parentCategoriesData as $c) {
            $categories[$c['name']] = Category::create($c);
        }

        // Subcategories
        $subcategoriesData = [
            ['name' => 'Retainer Agreements', 'type' => 'income', 'parent_id' => $categories['Client Revenue & Sales']->id, 'icon' => 'bi-file-earmark-text', 'color' => '#059669'],
            ['name' => 'Project Milestones', 'type' => 'income', 'parent_id' => $categories['Client Revenue & Sales']->id, 'icon' => 'bi-flag', 'color' => '#059669'],
            ['name' => 'Cloud Hosting (AWS/GCP)', 'type' => 'expense', 'parent_id' => $categories['Software & Subscriptions']->id, 'icon' => 'bi-hdd-network', 'color' => '#6366f1'],
            ['name' => 'Developer Tooling (GitHub)', 'type' => 'expense', 'parent_id' => $categories['Software & Subscriptions']->id, 'icon' => 'bi-code-slash', 'color' => '#6366f1'],
            ['name' => 'Design & Collaboration (Figma)', 'type' => 'expense', 'parent_id' => $categories['Software & Subscriptions']->id, 'icon' => 'bi-palette', 'color' => '#6366f1'],
            ['name' => 'Search Ads (Google Ads)', 'type' => 'expense', 'parent_id' => $categories['Marketing & Advertising']->id, 'icon' => 'bi-google', 'color' => '#84cc16'],
            ['name' => 'Social Media (LinkedIn Ads)', 'type' => 'expense', 'parent_id' => $categories['Marketing & Advertising']->id, 'icon' => 'bi-linkedin', 'color' => '#84cc16'],
            ['name' => 'High-Speed Fiber Internet', 'type' => 'expense', 'parent_id' => $categories['Utilities & Internet']->id, 'icon' => 'bi-router', 'color' => '#f59e0b'],
            ['name' => 'Team Brainstorm Dinners', 'type' => 'expense', 'parent_id' => $categories['Food & Business Dining']->id, 'icon' => 'bi-cup-straw', 'color' => '#f97316'],
        ];

        $subcategories = [];
        foreach ($subcategoriesData as $sc) {
            $subcategories[$sc['name']] = Category::create($sc);
        }

        // 2. Create Demo User (Small Business)
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

        // Individual account
        $individual = User::create([
            'name' => 'Sarah Connor',
            'email' => 'sarah@example.com',
            'password' => Hash::make('password123'),
            'role' => 'individual',
            'business_name' => null,
            'currency' => 'USD',
            'phone' => '+1 (555) 987-6543',
        ]);

        // 3. Create Accounts for Alex
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
            'description' => 'Reserved funds for upcoming quarterly tax payments and runway',
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
            'description' => 'On-hand cash for miscellaneous supplies and couriers',
        ]);

        // Individual account
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

        // 4. Sample Transactions with Subcategories, Recurring Flags, and Payment Methods
        $now = Carbon::now();
        $sampleTransactions = [
            // Current Month
            [
                'account_id' => $bizChecking->id,
                'category_id' => $subcategories['Retainer Agreements']->id,
                'type' => 'income',
                'amount' => 8500.00,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(2)->format('Y-m-d'),
                'description' => 'Monthly Retainer: Nexus Health Architecture',
                'payee_payer' => 'Nexus Health Inc',
                'payment_method' => 'bank_transfer',
                'reference_number' => 'ACH-88491',
                'is_recurring' => true,
                'recurrence_interval' => 'monthly',
            ],
            [
                'account_id' => $bizChecking->id,
                'category_id' => $subcategories['Project Milestones']->id,
                'type' => 'income',
                'amount' => 4200.00,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(6)->format('Y-m-d'),
                'description' => 'Milestone 2 Delivery: FinTech UI/UX Sprint',
                'payee_payer' => 'Starlight Media Co',
                'payment_method' => 'bank_transfer',
                'reference_number' => 'STR-1049',
                'is_recurring' => false,
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
                'is_recurring' => true,
                'recurrence_interval' => 'monthly',
            ],
            [
                'account_id' => $bizChecking->id,
                'category_id' => $categories['Rent & Facilities']->id,
                'type' => 'expense',
                'amount' => 1800.00,
                'fee' => 0,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(1)->format('Y-m-d'),
                'description' => 'Office Space Lease (Suite 402)',
                'payee_payer' => 'WeWork Real Estate',
                'payment_method' => 'bank_transfer',
                'reference_number' => 'CHK-309',
                'is_recurring' => true,
                'recurrence_interval' => 'monthly',
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
                'is_recurring' => true,
                'recurrence_interval' => 'monthly',
            ],
            [
                'account_id' => $bizCard->id,
                'category_id' => $subcategories['Cloud Hosting (AWS/GCP)']->id,
                'type' => 'expense',
                'amount' => 249.00,
                'fee' => 0,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(3)->format('Y-m-d'),
                'description' => 'AWS Cloud Infrastructure Hosting',
                'payee_payer' => 'Amazon Web Services',
                'payment_method' => 'credit_card',
                'reference_number' => 'INV-AWS-883',
                'is_recurring' => true,
                'recurrence_interval' => 'monthly',
            ],
            [
                'account_id' => $bizCard->id,
                'category_id' => $subcategories['Developer Tooling (GitHub)']->id,
                'type' => 'expense',
                'amount' => 120.00,
                'fee' => 0,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(4)->format('Y-m-d'),
                'description' => 'GitHub Enterprise & Copilot seats',
                'payee_payer' => 'GitHub Inc',
                'payment_method' => 'credit_card',
                'is_recurring' => true,
                'recurrence_interval' => 'monthly',
            ],
            [
                'account_id' => $bizCard->id,
                'category_id' => $subcategories['Search Ads (Google Ads)']->id,
                'type' => 'expense',
                'amount' => 650.00,
                'fee' => 0,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(8)->format('Y-m-d'),
                'description' => 'Google Search Ads Lead Campaign',
                'payee_payer' => 'Google Ads',
                'payment_method' => 'credit_card',
                'is_recurring' => false,
            ],
            [
                'account_id' => $bizChecking->id,
                'category_id' => $subcategories['High-Speed Fiber Internet']->id,
                'type' => 'expense',
                'amount' => 185.00,
                'fee' => 0,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(7)->format('Y-m-d'),
                'description' => 'Dedicated Fiber Internet & Static IP',
                'payee_payer' => 'AT&T Business',
                'payment_method' => 'bank_transfer',
                'is_recurring' => true,
                'recurrence_interval' => 'monthly',
            ],
            [
                'account_id' => $pettyCash->id,
                'category_id' => $subcategories['Team Brainstorm Dinners']->id,
                'type' => 'expense',
                'amount' => 74.50,
                'fee' => 0,
                'transaction_date' => $now->copy()->startOfMonth()->addDays(9)->format('Y-m-d'),
                'description' => 'Team strategy lunch meeting',
                'payee_payer' => 'Blue Bottle Cafe',
                'payment_method' => 'cash',
                'is_recurring' => false,
            ],

            // Prior Month Historical
            [
                'account_id' => $bizChecking->id,
                'category_id' => $categories['Client Revenue & Sales']->id,
                'type' => 'income',
                'amount' => 11200.00,
                'transaction_date' => $now->copy()->subMonth()->startOfMonth()->addDays(5)->format('Y-m-d'),
                'description' => 'Enterprise System Integration Q3',
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
                'is_recurring' => true,
                'recurrence_interval' => 'monthly',
            ],
            [
                'account_id' => $bizChecking->id,
                'category_id' => $categories['Payroll & Contractors']->id,
                'type' => 'expense',
                'amount' => 4800.00,
                'transaction_date' => $now->copy()->subMonth()->startOfMonth()->addDays(15)->format('Y-m-d'),
                'description' => 'Contractor Monthly Remuneration',
                'payee_payer' => 'Various Contractors',
                'payment_method' => 'bank_transfer',
            ],
            [
                'account_id' => $bizCard->id,
                'category_id' => $subcategories['Social Media (LinkedIn Ads)']->id,
                'type' => 'expense',
                'amount' => 850.00,
                'transaction_date' => $now->copy()->subMonth()->startOfMonth()->addDays(12)->format('Y-m-d'),
                'description' => 'LinkedIn B2B Sponsored InMail',
                'payee_payer' => 'LinkedIn Ads',
                'payment_method' => 'credit_card',
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

        // 5. Budgets
        Budget::create([
            'user_id' => $user->id,
            'category_id' => $categories['Software & Subscriptions']->id,
            'name' => 'Monthly SaaS & Cloud Stack',
            'amount' => 500.00,
            'period' => 'monthly',
            'month_year' => $now->format('Y-m'),
            'alert_threshold' => 80,
            'notify_on_exceed' => true,
        ]);

        Budget::create([
            'user_id' => $user->id,
            'category_id' => $categories['Marketing & Advertising']->id,
            'name' => 'Digital Ads & Growth',
            'amount' => 1000.00,
            'period' => 'monthly',
            'month_year' => $now->format('Y-m'),
            'alert_threshold' => 85,
            'notify_on_exceed' => true,
        ]);

        // 6. Invoices
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

        // 7. AI Insights
        AiInsight::create([
            'user_id' => $user->id,
            'type' => 'savings_opportunity',
            'title' => 'Software Subscription Creep Detected',
            'summary' => 'You have 4 active design and developer tool subscriptions totaling $369/mo. Consolidating inactive Figma editor licenses could save ~$95/mo.',
            'detailed_analysis' => 'Based on transaction patterns over the last 90 days, multiple duplicate recurring charges were found. We identified 3 seats billed under GitHub and Figma with zero activity recorded. Removing dormant seats yields annual savings of $1,140.',
            'priority' => 'medium',
            'action_suggested' => 'Audit team member seat permissions under Settings > Subscriptions.',
        ]);
    }
}
