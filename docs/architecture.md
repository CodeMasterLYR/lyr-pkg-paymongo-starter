# Architecture Guide — `lyr-pkg-paymongo-starter`

> **Maintained by:** Jerald Jose (CodeMasterLYR)
> **License:** MIT Open Source
> **Last Updated:** October 2026

---

## 1. Purpose

A full-stack open-source starter kit for integrating **PayMongo** (GCash, Maya, QR Ph, Card, BillEase) into PHP/Laravel applications. It demonstrates secure, production-ready patterns including session-based authentication, session hijacking prevention, CSRF protection, and a working Next.js frontend playground.

Designed for:
- Developers learning PayMongo API integration end-to-end
- Teams who want a clean starting point for payment features
- Open-source contributors wanting to study secure session architecture

---

## 2. Getting Started & Learning Path

Follow this order when studying or taking over this project:

1. [ ] Read this file (`docs/architecture.md`) completely.
2. [ ] Read [`docs/paymongo-api-guide.md`](paymongo-api-guide.md) — PayMongo account setup & key types.
3. [ ] Clone the repo. Set up the **backend** first (see `backend/README.md`).
4. [ ] Copy `backend/.env.example` → `backend/.env`, add your PayMongo test keys.
5. [ ] Run `php artisan migrate` and `php artisan serve`.
6. [ ] Set up the **frontend** (see `frontend/README.md`), run `npm run dev`.
7. [ ] Register on `localhost:3000`, create a checkout session, complete a test payment.
8. [ ] Read through the key files listed in Section 4 below.

---

## 3. System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  Browser (localhost:3000)                                   │
│  Next.js 14 — App Router + TypeScript                       │
│                                                             │
│  ┌─────────────────────┐   ┌────────────────────────────┐  │
│  │  Auth Form          │   │  Checkout Form             │  │
│  │  Register / Login   │   │  Line Items + Description  │  │
│  └────────┬────────────┘   └────────────┬───────────────┘  │
│           │ credentials:include          │                  │
│           │ X-XSRF-TOKEN (CSRF)         │                  │
└───────────┼─────────────────────────────┼──────────────────┘
            │ HTTP + Session Cookie        │
            ▼                             ▼
┌─────────────────────────────────────────────────────────────┐
│  Laravel 11 API (localhost:8000)                            │
│                                                             │
│  Middleware Stack (applied to every request):               │
│  1. ForceJsonResponse      — Accept: application/json       │
│  2. HandleCors             — allows localhost:3000          │
│  3. VerifyCsrfToken        — validates X-XSRF-TOKEN         │
│  4. StartSession           — reads/writes session cookie    │
│  5. Authenticate (Sanctum) — validates session user         │
│  6. PreventSessionHijacking— IP + UA fingerprint check      │
│                                                             │
│  ┌──────────────────┐    ┌────────────────────────────┐    │
│  │ AuthController   │    │ PayMongoController         │    │
│  │ /api/auth/*      │    │ /api/paymongo/*            │    │
│  └──────────────────┘    └────────────┬───────────────┘    │
│                                       │                     │
│                          ┌────────────▼───────────────┐    │
│                          │ PayMongoService             │    │
│                          │ HTTP calls to PayMongo API  │    │
│                          └────────────────────────────┘    │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ Webhook: POST /api/webhooks/paymongo                 │  │
│  │ HMAC-SHA256 signature verified before processing     │  │
│  └──────────────────────────────────────────────────────┘  │
└──────────────────────────────┬──────────────────────────────┘
                               │ HTTPS
                               ▼
                    ┌──────────────────────┐
                    │  PayMongo API        │
                    │  api.paymongo.com    │
                    │  Hosted Checkout UI  │
                    └──────────────────────┘
```

---

## 4. Key Files Reference

### Backend (`backend/`)

| File | Role |
|------|------|
| `app/Services/PayMongoService.php` | All PayMongo HTTP calls — checkout sessions, payments list, webhook HMAC verifier |
| `app/Http/Controllers/Api/PayMongoController.php` | `checkout`, `getSession`, `listPayments`, `webhook` endpoints |
| `app/Http/Controllers/Api/AuthController.php` | `register`, `login`, `logout`, `me` — session-based, no tokens |
| `app/Http/Middleware/ForceJsonResponse.php` | Prepended globally — sets `Accept: application/json` on all requests |
| `app/Http/Middleware/PreventSessionHijacking.php` | Binds session to IP + User-Agent hash — invalidates on mismatch |
| `routes/api.php` | All route definitions with `auth:sanctum` middleware groups |
| `routes/web.php` | Fallback returns JSON 404 — no HTML ever rendered |
| `config/session.php` | Hardened: HttpOnly, SameSite=Lax, encrypted, 120 min lifetime |
| `config/cors.php` | Allows `localhost:3000` with `supports_credentials: true` |
| `bootstrap/app.php` | Registers middleware, API routes, and global JSON exception rendering |

### Frontend (`frontend/src/`)

| File | Role |
|------|------|
| `lib/api.ts` | Typed session-based API client — `credentials: include`, auto CSRF fetch |
| `app/page.tsx` | Main playground — auth form, checkout form, payments table, session info |
| `app/success/page.tsx` | Shown after PayMongo redirects back on successful payment |
| `app/cancel/page.tsx` | Shown when user cancels the PayMongo hosted checkout |
| `app/globals.css` | Full dark-theme design system — cards, inputs, badges, table, nav |

---

## 5. Security Architecture

| Layer | Mechanism | What it Prevents |
|-------|-----------|-----------------|
| `ForceJsonResponse` | Sets `Accept: application/json` globally | HTML error pages leaking stack traces |
| `HttpOnly` session cookie | Cookie inaccessible to JavaScript | XSS-based session theft |
| `SameSite=Lax` | Browser blocks cross-site cookie sending | CSRF from third-party origins |
| `VerifyCsrfToken` | Validates `X-XSRF-TOKEN` double-submit | Cross-site request forgery |
| `session()->regenerate()` on login | Issues a new session ID at login | Session fixation attacks |
| `PreventSessionHijacking` | Binds session to SHA-256(User-Agent) + IP | Stolen cookie replay from another device |
| Session encryption (`'encrypt' => true`) | AES-256 encryption of session file | Offline session data extraction |
| PayMongo webhook HMAC | `hash_hmac('sha256', ...)` + `hash_equals()` | Fake webhook event injection |

---

## 6. Authentication Flow (Session-Based)

```
Client (Next.js)                    Server (Laravel)
─────────────────                   ────────────────
1. POST /api/auth/login
   Body: {email, password}
   + X-XSRF-TOKEN header
                         ──────►   Validate credentials
                                   Auth::attempt()
                                   session()->regenerate()   ← new session ID
                         ◄──────   Set-Cookie: *_session (HttpOnly, SameSite=Lax)
                                   200 {user}

2. Any subsequent request
   Cookie: *_session (auto-sent)
   X-XSRF-TOKEN: <token>
                         ──────►   StartSession → load user
                                   PreventSessionHijacking → verify IP + UA
                                   auth:sanctum → confirm authenticated
                         ◄──────   200 response data

3. POST /api/auth/logout
                         ──────►   Auth::logout()
                                   session()->invalidate()   ← wipe session data
                                   session()->regenerateToken() ← new CSRF token
                         ◄──────   200 {message: "Logged out"}
                                   Cookie cleared server-side
```

---

## 7. PayMongo Checkout Flow

```
Frontend             Backend (Laravel)          PayMongo API
────────             ─────────────────          ────────────
POST /api/paymongo/checkout
{items, description}
                ──► PayMongoController
                    PayMongoService
                    POST /v1/checkout_sessions ──► Returns checkout_url
                ◄── {checkout_url, session_id}

window.open(checkout_url)  ──────────────────────► Hosted Checkout UI
                                                    (GCash / Maya / Card)
                                                    Payment completes
                                               ◄─── Redirect to success_url
                                                    Webhook fires to backend

POST /api/webhooks/paymongo ◄────────────── PayMongo sends event
Verify HMAC signature
Log event, fulfill order
```

---

## 8. Community & Maintainer

| Field | Value |
|-------|-------|
| Author | Jerald Jose (CodeMasterLYR) |
| GitHub | [@CodeMasterLYR](https://github.com/CodeMasterLYR) |
| License | MIT |
| Stack | Laravel 11, Sanctum, Next.js 14, TypeScript |

*Contributions, issues, and pull requests are welcome.*

---

## 9. Mobile API Integration (KMP Ktor / Android / iOS)

### How Mobile Auth Differs from Web Auth

| Concern | Web (Next.js) | Mobile (KMP Ktor / Android) |
|---------|--------------|----------------------------|
| Auth mechanism | Session cookie | Sanctum Bearer token |
| CSRF required | Yes (X-XSRF-TOKEN) | No |
| Token storage | None (HttpOnly cookie) | `EncryptedSharedPreferences` (Android) / Keychain (iOS) |
| App identified by | Session origin | `X-App-Key` header |
| Route prefix | `/api/auth/*`, `/api/paymongo/*` | `/api/mobile/auth/*`, `/api/mobile/paymongo/*` |

---

### Mobile Route Table

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/mobile/auth/register` | X-App-Key | Register, returns Bearer token |
| POST | `/api/mobile/auth/login` | X-App-Key | Login, returns Bearer token |
| POST | `/api/mobile/auth/logout` | X-App-Key + Bearer | Revoke current device token |
| POST | `/api/mobile/auth/logout-all` | X-App-Key + Bearer | Revoke all device tokens |
| GET  | `/api/mobile/auth/me` | X-App-Key + Bearer | Current user + device name |
| POST | `/api/mobile/paymongo/checkout` | X-App-Key + Bearer | Create checkout session |
| GET  | `/api/mobile/paymongo/checkout/{id}` | X-App-Key + Bearer | Session status |
| GET  | `/api/mobile/paymongo/payments` | X-App-Key + Bearer | List payments |

---

### Ktor (KMP) Integration Example

```kotlin
// HttpClient setup in Ktor (commonMain)
val client = HttpClient {
    install(ContentNegotiation) { json() }
    defaultRequest {
        header("X-App-Key", BuildConfig.MOBILE_APP_KEY)
        header("Accept", "application/json")
    }
}

// Login
@Serializable
data class LoginRequest(val email: String, val password: String, val device_name: String)

@Serializable
data class LoginResponse(val token: String, val token_type: String, val user: UserDto)

suspend fun login(email: String, password: String): LoginResponse {
    return client.post("$API_BASE/mobile/auth/login") {
        contentType(ContentType.Application.Json)
        setBody(LoginRequest(email, password, "My Android Device"))
    }.body()
}

// Authenticated request (attach Bearer token)
suspend fun getPayments(token: String): List<Payment> {
    return client.get("$API_BASE/mobile/paymongo/payments") {
        header("Authorization", "Bearer $token")
    }.body<PaymentsResponse>().data
}
```

---

### Android EncryptedSharedPreferences (Secure Token Storage)

```kotlin
// Store token securely — never in plain SharedPreferences
val masterKey = MasterKey.Builder(context)
    .setKeyScheme(MasterKey.KeyScheme.AES256_GCM)
    .build()

val prefs = EncryptedSharedPreferences.create(
    context, "secure_prefs", masterKey,
    EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
    EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM
)

prefs.edit().putString("bearer_token", token).apply()

// Read token
val token = prefs.getString("bearer_token", null)
```

---

### Security Notes for Mobile Clients

| Practice | Reason |
|----------|--------|
| Store token in `EncryptedSharedPreferences` / Keychain | Plain SharedPreferences is readable on rooted devices |
| Pass `device_name` on login | Server revokes old token for same device — prevents accumulation |
| Use `logout-all` on password change | Invalidates all other device sessions instantly |
| Keep `MOBILE_APP_KEY` in `BuildConfig` (not source code) | Inject via CI/CD environment variables |
| Rotate `MOBILE_APP_KEY` if compromised | Server-side — no app update needed for web clients |
