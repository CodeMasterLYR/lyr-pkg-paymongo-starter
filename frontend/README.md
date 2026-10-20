# lyr-pkg-paymongo-starter — Frontend (Next.js 14)

> PayMongo payment playground built with Next.js 14 App Router + TypeScript.
> Part of the [`lyr-pkg-paymongo-starter`](../README.md) open-source starter kit.

---

## What This Is

A minimal, developer-friendly frontend playground for testing PayMongo API integrations end-to-end — register, log in, create checkout sessions (GCash, Maya, QR Ph, Card), and view payment history.

Authentication uses **session-based cookies** (no localStorage tokens) with CSRF protection via Sanctum.

---

## Requirements

- Node.js 18+
- Laravel backend running at `http://localhost:8000` (see [`../backend`](../backend/README.md))

---

## Quick Start

```bash
cd frontend
npm install
cp .env.local.example .env.local
npm run dev
```

Open [http://localhost:3000](http://localhost:3000) in your browser.

---

## Environment Variables

```dotenv
# .env.local
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_BACKEND_URL=http://localhost:8000
```

---

## Project Structure

```
frontend/
├── src/
│   ├── app/
│   │   ├── layout.tsx           # Root layout + nav + global CSS
│   │   ├── globals.css          # Design system (dark theme)
│   │   ├── page.tsx             # Main playground — auth + checkout + payments
│   │   ├── success/
│   │   │   └── page.tsx         # Shown after successful PayMongo payment
│   │   └── cancel/
│   │       └── page.tsx         # Shown when user cancels checkout
│   └── lib/
│       └── api.ts               # Typed session-based API client
├── .env.local.example
└── README.md
```

---

## Key File: `src/lib/api.ts`

All backend communication goes through this typed API client. It handles:
- `credentials: "include"` on every request (sends session cookie)
- Fetching `GET /sanctum/csrf-cookie` before mutations
- Reading and sending `X-XSRF-TOKEN` header automatically
- Unified error parsing from Laravel validation responses

---

## Pages

| Route | Description |
|-------|-------------|
| `/` | Auth form (login/register) → checkout form + payment history |
| `/success` | PayMongo redirects here after a successful payment |
| `/cancel` | PayMongo redirects here if user cancels |

---

## Session Security (No localStorage)

This frontend intentionally stores **zero credentials** in `localStorage` or `sessionStorage`.

| Mechanism | What it does |
|-----------|-------------|
| `HttpOnly` cookie | Set by Laravel — JS cannot read it at all |
| `SameSite=Lax` | Blocks CSRF from third-party sites |
| `X-XSRF-TOKEN` | CSRF header auto-fetched from `/sanctum/csrf-cookie` |

---

## Contributing

Pull requests are welcome! See [`../docs/architecture.md`](../docs/architecture.md) for the full design overview.

---

*MIT Licensed • Maintained by Jerald Jose (CodeMasterLYR)*
