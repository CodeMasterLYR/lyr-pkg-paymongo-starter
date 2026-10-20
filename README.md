# lyr-pkg-paymongo-starter

> **Open-Source Learning Blueprint & Starter Kit**
> **Maintained by:** Jerald Jose (CodeMasterLYR)

A full-stack, production-ready PayMongo payment integration starter built with **Laravel 11** (API backend) and **Next.js 14** (frontend playground). Supports GCash, Maya, QR Ph, Card, and BillEase.

---

## Features

- **Session-based authentication** — HttpOnly cookies, no localStorage tokens
- **Session hijacking prevention** — IP + User-Agent binding, auto-invalidate on mismatch
- **CSRF protection** — Sanctum SPA stateful auth with XSRF-TOKEN double-submit
- **Session regeneration** on login (prevents session fixation)
- **PayMongo Checkout API** — hosted checkout (GCash, Maya, QR Ph, Card, BillEase)
- **Webhook handler** — HMAC signature verification for PayMongo events
- **Payments dashboard** — view recent payment history

---

## Project Structure

```
lyr-pkg-paymongo-starter/
├── backend/                        # Laravel 11 REST API + Sanctum
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   │   ├── AuthController.php       # Register, login, logout, me
│   │   │   │   └── PayMongoController.php   # Checkout, payments, webhook
│   │   │   └── Middleware/
│   │   │       └── PreventSessionHijacking.php  # IP + UA binding guard
│   │   └── Services/
│   │       └── PayMongoService.php          # All PayMongo API calls
│   ├── config/
│   │   ├── session.php                      # Hardened session config
│   │   ├── cors.php                         # CORS for Next.js frontend
│   │   └── services.php                     # PayMongo key config
│   ├── routes/api.php                       # All API route definitions
│   └── .env.example
│
├── frontend/                       # Next.js 14 playground (App Router + TypeScript)
│   ├── src/
│   │   ├── app/
│   │   │   ├── page.tsx             # Main playground (auth + checkout + payments)
│   │   │   ├── success/page.tsx     # PayMongo success redirect
│   │   │   └── cancel/page.tsx      # PayMongo cancel redirect
│   │   └── lib/api.ts              # Session-based typed API client
│   └── .env.local.example
│
└── docs/
    ├── architecture.md              # Architecture & learning guide
    └── paymongo-api-guide.md        # PayMongo account setup & integration options
```

---

## Quick Start

### 1. Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# SQLite (default — no DB server needed for local dev)
touch database/database.sqlite
php artisan migrate

php artisan serve   # http://localhost:8000
```

Set your PayMongo test keys in `backend/.env`:

```dotenv
PAYMONGO_PUBLIC_KEY=pk_test_...
PAYMONGO_SECRET_KEY=sk_test_...
PAYMONGO_WEBHOOK_SECRET=whsk_...
```

### 2. Frontend (Next.js)

```bash
cd frontend
npm install
cp .env.local.example .env.local
npm run dev  # http://localhost:3000
```

---

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/auth/register` | Public | Register, starts session |
| POST | `/api/auth/login` | Public | Login, starts session |
| POST | `/api/auth/logout` | Session | Destroys session |
| GET  | `/api/auth/me` | Session | Current user |
| POST | `/api/paymongo/checkout` | Session | Create checkout session |
| GET  | `/api/paymongo/checkout/{id}` | Session | Get session status |
| GET  | `/api/paymongo/payments` | Session | List recent payments |
| POST | `/api/webhooks/paymongo` | Signature | PayMongo webhook events |

---

## Security Architecture

| Protection | Implementation |
|-----------|----------------|
| HttpOnly Cookie | JS cannot read the session cookie — blocks XSS theft |
| SameSite=Lax | Prevents CSRF from third-party origins |
| XSRF-TOKEN | Double-submit CSRF protection via Sanctum |
| Session Regeneration | New session ID on every login — blocks fixation |
| IP + UA Binding | `PreventSessionHijacking` middleware — stolen cookie = 401 |
| Session Encryption | All session data encrypted with `APP_KEY` |

---

## Documentation

| Guide | Description |
|-------|-------------|
| [`docs/architecture.md`](docs/architecture.md) | Architecture overview and learning path |
| [`docs/paymongo-api-guide.md`](docs/paymongo-api-guide.md) | PayMongo account setup, keys, integration options |
| [`backend/README.md`](backend/README.md) | Backend setup and API reference |
| [`frontend/README.md`](frontend/README.md) | Frontend setup and playground guide |

---

## Contributing

1. Read `docs/architecture.md` first.
2. Pull requests and community improvements are warmly welcome!
3. Never commit `.env` files or production credentials.

---

*MIT Licensed • Maintained by Jerald Jose (CodeMasterLYR)*
