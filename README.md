# FinTrack AI - Intelligent Financial Management & Advisory

An enterprise-grade web application built with **Laravel 12**, **Bootstrap 5**, and **Chart.js** designed to help individuals and small businesses track financial activities, understand spending patterns, manage budgets, and receive intelligent recommendations powered by an OpenAI-compatible API.

---

## 🛠️ Technology Stack

| Layer | Technology |
|---|---|
| **Backend** | Laravel 12 (PHP 8.2+) |
| **Frontend** | Blade, HTML5, CSS3, JavaScript |
| **UI Framework** | Bootstrap 5 + Bootstrap Icons |
| **Database** | MySQL (with SQLite local zero-config fallback) |
| **Authentication** | Session-based Custom Auth & Profile Switcher |
| **Charts & Visualizations** | Chart.js 4 (Bar, Line, Doughnut) |
| **AI Integration** | OpenAI-compatible API (`gpt-4o-mini`, Gemini, local LLM) with built-in heuristic fallback engine |
| **PDF Reports** | Barryvdh DomPDF |
| **Excel Exports** | Laravel Excel (Maatwebsite/Excel + PhpSpreadsheet) |
| **Background Jobs** | Laravel Queue (Database driver) |
| **API Authentication** | Laravel Sanctum (Bearer Token) |
| **Testing** | PHPUnit 11 (11 tests, 30 assertions, 100% passing) |
| **Version Control** | Git + GitHub ready |

---

## 🚀 Key Modules & Capabilities

### Module 1 – Dashboard
- **Total Income & Expenses**: Month-to-date and all-time aggregate tracking.
- **Current Net Liquid Balance**: Consolidated across active checking, savings, credit cards, and cash accounts.
- **Monthly Savings & Savings Rate (%)**: Real-time savings retention indicator.
- **Income vs Expense Cashflow Trend Chart**: 6-month historical trend powered by Chart.js.
- **Spend by Category Chart**: Interactive doughnut visualization showing category distribution.
- **Recent Transactions Feed**: Live itemized transactions with category badges and account tags.
- **Budget Utilization Gauges**: Progress bars with color-coded safety thresholds (<80% normal, 80-99% warning, 100%+ exceeded).
- **Financial Overview & Quick Actions**: 1-click transaction entry modal accessible anywhere in the application.

### Module 2 – Transactions & Multi-Account Management
- Incomes, Expenses, and Transfers between accounts.
- Automated balance recalculation on both source and destination accounts.
- Filtering by date range, account, category, type, and keyword search.

### Module 3 – Budgets & Spending Caps
- Monthly budgets per category.
- Visual threshold alerts before overspending.
- Remaining pool tracking.

### Module 4 – Client Invoicing & Billing (Small Business Pro)
- Professional client invoice creation with line items, tax percentage, and discount calculation.
- One-click DomPDF invoice generation and download.
- Lifecycle tracking: Draft &rarr; Sent &rarr; Paid &rarr; Overdue.

### Module 5 – Reports & Exports
- **DomPDF Monthly Financial Statements**: Executive summary suitable for accountants and tax filing.
- **Excel (.xlsx) & CSV Exports**: Itemized export of transactions via Laravel Excel.

### Module 6 – AI Financial Advisor & Copilot
- **Live AI Financial Audit**: Audits transaction velocity, detects SaaS subscription creep, calculates runway, and suggests savings opportunities.
- **Interactive AI Advisor**: Real-time natural language financial Q&A (e.g. *"How many months of cash runway do I have based on my burn rate?"*).
- **OpenAI-Compatible**: Configure your own API key, model (`gpt-4o-mini`, `gemini-2.5-flash`), and endpoint in Settings.

### Module 7 – REST API & Laravel Sanctum
- Token-based API access for external apps or mobile integrations:
  - `POST /api/v1/login`
  - `GET /api/v1/dashboard/summary`
  - `GET /api/v1/transactions`
  - `POST /api/v1/transactions`

---

## ⚡ Quick Start & Setup

### 1. Requirements
- PHP 8.2+ with `pdo_mysql`, `pdo_sqlite`, `zip`, `mbstring`, `curl`
- Composer 2+
- Node.js 18+ & NPM

### 2. Installation
```bash
# Clone the repository
git clone <repository-url>
cd fintrack-ai

# Install PHP dependencies
composer install

# Install NPM dependencies and build assets
npm install
npm run build

# Environment Setup
cp .env.example .env
php artisan key:generate

# Run Database Migrations & Seed Demo Data
php artisan migrate --seed
```

### 3. Demo Credentials
The seeder pre-populates realistic data for instant demonstration:

| Profile | Email | Password | Role |
|---|---|---|---|
| **Alex Morgan** | `alex@example.com` | `password123` | Small Business Pro (Apex Digital Solutions LLC) |
| **Sarah Connor** | `sarah@example.com` | `password123` | Individual Tracker |

*(Both accounts feature 1-click auto-fill buttons on the login page)*

### 4. Running the Application
```bash
# Start local PHP server
php artisan serve

# (Optional) Start queue worker for background reports & AI audits
php artisan queue:work
```
Open [http://localhost:8000](http://localhost:8000) in your browser.

---

## 🧪 Running Automated Tests
```bash
php vendor/phpunit/phpunit/phpunit
```
All 11 tests across authentication, dashboard KPIs, transactions, and Sanctum API pass with 100% success.
