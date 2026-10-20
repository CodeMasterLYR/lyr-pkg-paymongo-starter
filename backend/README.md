# lyr-pkg-paymongo-starter — Backend (Laravel 11 + Sanctum)

> Laravel REST API powering PayMongo GCash, Maya, QR Ph, and Card checkout sessions.

## Requirements
- PHP 8.2+
- Composer 2+
- SQLite (default) or MySQL

## Quick Start

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# SQLite (default — no DB server needed)
touch database/database.sqlite
php artisan migrate

php artisan serve  # runs on http://localhost:8000
```

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/auth/register` | Public | Register user, returns token |
| POST | `/api/auth/login` | Public | Login, returns token |
| POST | `/api/auth/logout` | Bearer | Revoke current token |
| GET  | `/api/auth/me` | Bearer | Get authenticated user |
| POST | `/api/paymongo/checkout` | Bearer | Create checkout session |
| GET  | `/api/paymongo/checkout/{id}` | Bearer | Get session status |
| GET  | `/api/paymongo/payments` | Bearer | List recent payments |
| POST | `/api/webhooks/paymongo` | Signature | Receive PayMongo events |

## PayMongo Configuration
Set your test keys in `.env`:
```dotenv
PAYMONGO_PUBLIC_KEY=pk_test_...
PAYMONGO_SECRET_KEY=sk_test_...
PAYMONGO_WEBHOOK_SECRET=whsk_...
```
Get keys from: https://dashboard.paymongo.com > Developers

## Key Files
| File | Role |
|------|------|
| `app/Services/PayMongoService.php` | All PayMongo API calls |
| `app/Http/Controllers/Api/PayMongoController.php` | Checkout + webhook handlers |
| `app/Http/Controllers/Api/AuthController.php` | Register / login / logout |
| `routes/api.php` | All API route definitions |
