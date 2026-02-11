# PMS - Property Management System

A multi-tenant SaaS application for property managers built with Laravel 12, Livewire 4, and Flux UI.

## Features

### Core Modules
- 🏠 **Properties** - Manage buildings with owner information
- 🔑 **Units** - Track apartments/units with rent amounts
- 👥 **Renters** - Tenant profiles and contact information
- 📋 **Leases** - Contracts with dates, amounts, and documents
- 💰 **Rent Payments** - Payment tracking and history
- 🔧 **Maintenance** - Request management by priority
- 📊 **Reports** - Revenue and property performance analytics
- 🔔 **Notifications** - Real-time notification system

### Multi-Tenant Architecture
- Separate databases per tenant
- Subdomain-based tenant identification
- Central admin for managing organizations

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12 |
| Frontend | Livewire 4 + Flux UI |
| Database | PostgreSQL |
| Multi-tenancy | stancl/tenancy 3.9 |
| Permissions | Spatie Laravel Permission |
| Payments | Paystack |

## Requirements

- PHP 8.2+
- PostgreSQL 14+
- Node.js 18+
- Composer 2.x

## Installation

### 1. Clone and Install Dependencies

```bash
git clone https://github.com/your-org/pms.git
cd pms
composer install
npm install
```

### 2. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure Database

Edit `.env` with your PostgreSQL credentials:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pms
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Tenant template database
TENANT_DB_CONNECTION=pgsql
```

### 4. Configure Multi-Tenancy

```env
# Comma-separated central domains
CENTRAL_DOMAINS=localhost,your-domain.com

# App URL for tenant subdomain routing
APP_URL=http://localhost
```

### 5. Run Migrations

```bash
# Central database
php artisan migrate

# Seed initial data (plans, admin user)
php artisan db:seed
```

### 6. Build Assets

```bash
npm run build
```

### 7. Start Development Server

```bash
composer run dev
# Or manually:
php artisan serve
npm run dev
```

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_URL` | Base application URL | `http://localhost` |
| `CENTRAL_DOMAINS` | Comma-separated central domains | `localhost` |
| `DB_CONNECTION` | Database driver | `pgsql` |
| `PAYSTACK_PUBLIC_KEY` | Paystack public API key | - |
| `PAYSTACK_SECRET_KEY` | Paystack secret API key | - |
| `QUEUE_CONNECTION` | Queue driver | `database` |

## Commands

```bash
# Run tests
php artisan test

# Create new tenant
php artisan tenants:create {subdomain}

# Run tenant migrations
php artisan tenants:migrate

# Clear caches
php artisan optimize:clear
```

## Project Structure

```
app/
├── Models/           # Eloquent models (17 total)
├── Livewire/         # Livewire components
├── Traits/           # WithDataTable, HasPlanLimits
└── Providers/        # Service providers

resources/views/
├── pages/
│   ├── central/      # Central admin pages
│   └── tenant/       # Tenant application pages
├── components/       # Reusable Blade components
└── layouts/          # App layouts

routes/
├── web.php           # Central routes
└── tenant.php        # Tenant routes
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/LeadApprovalTest.php

# Run with coverage
php artisan test --coverage
```

## Deployment

### Production Checklist

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Configure HTTPS and update `APP_URL`
3. Set up queue worker: `php artisan queue:work`
4. Configure cron for scheduler: `* * * * * php artisan schedule:run`
5. Run `php artisan optimize` for caching

### Server Requirements

- PHP extensions: `pgsql`, `mbstring`, `xml`, `curl`, `zip`
- PostgreSQL with `uuid-ossp` extension
- Supervisor for queue workers

## License

MIT License - see [LICENSE](LICENSE) for details.
