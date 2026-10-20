# PayMongo Account Setup & API Integration Guide

This guide walks you through registering for a **PayMongo** account, obtaining your API keys, and choosing the right integration approach for your project.

---

## 1. How to Register & Get Your API Keys

You **do not need a registered business** to start building or testing. Anyone can sign up for a free sandbox developer account instantly.

### Step 1: Create an Account
1. Go to [https://dashboard.paymongo.com/signup](https://dashboard.paymongo.com/signup).
2. Sign up using your email and create a password.
3. Confirm your email via the verification link sent to your inbox.

### Step 2: Access Your API Keys
1. Log into [dashboard.paymongo.com](https://dashboard.paymongo.com).
2. In the sidebar, click **Developers** (or go to **Settings** > **Developers**).
3. Under the **TEST** section, you will find:
   - **Secret key** — starts with `sk_test_...` → used **server-side only** (never expose publicly)
   - **Public key** — starts with `pk_test_...` → used for **client-side / frontend** integrations

> **Security Reminder:** Never commit your secret keys to git. Always keep them in `.env`.

### Step 3: Test Mode vs. Live Mode

| Mode | Key Prefix | Purpose | Requirement |
|------|-----------|---------|-------------|
| **Test (Sandbox)** | `pk_test_...`, `sk_test_...` | Development, mocked payments | None — instant access upon sign up |
| **Live (Production)** | `pk_live_...`, `sk_live_...` | Real GCash, Maya, QR Ph, Cards | Business KYC and account activation required |

---

## 2. Going Live — Business Activation (Philippines)

When ready to accept real payments, switch your dashboard to **Live** mode and complete the KYC activation form.

### Required Documents

**Individual / Sole Proprietor:**
- DTI Certificate of Business Name Registration
- Valid Government-issued Photo ID (Passport, UMID, Driver's License)
- Bank Account details (under the owner's or registered business name)

**Corporation / Partnership:**
- SEC Certificate of Registration & Articles of Incorporation
- BIR Form 2303 (Certificate of Registration)
- Corporate Bank Account details
- Valid IDs of Authorized Signatories or a Corporate Secretary Certificate

> Approval typically takes **2 to 5 business days** after complete submission.

---

## 3. Integration Options

Choose the approach that fits your project:

```
Do you need a fully custom payment UI inside your app?
            /               \
          Yes                No
          /                   \
[Payment Intents API]   Do you want a hosted checkout page?
                               /           \
                             Yes            No
                             /               \
                    [Checkout API]      [Payment Links]
```

### Option A — Checkout API (Recommended for Most Projects)

**Best for:** Laravel web apps, e-commerce portals, digital services.

**How it works:**
1. Your backend (Laravel) calls `POST /v1/checkout_sessions` with line items and payment method types.
2. PayMongo returns a `checkout_url`.
3. You redirect the user to that URL — PayMongo handles GCash, Maya, QR Ph, and Card input securely.
4. After payment, PayMongo redirects back to your `success_url` or `cancel_url`.

**Why it is great:**
- Zero PCI-DSS compliance burden on your side.
- Handles 3D-Secure, OTP verification, and card expiry automatically.
- No custom frontend UI required.

**Example Request (Laravel):**
```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken(config('services.paymongo.secret_key'))
    ->post('https://api.paymongo.com/v1/checkout_sessions', [
        'data' => [
            'attributes' => [
                'line_items' => [[
                    'currency' => 'PHP',
                    'amount'   => 50000,  // in centavos (PHP 500.00)
                    'name'     => 'Sample Product',
                    'quantity' => 1,
                ]],
                'payment_method_types' => ['gcash', 'paymaya', 'card', 'qrph'],
                'success_url' => route('payment.success'),
                'cancel_url'  => route('payment.cancel'),
                'description' => 'Order #12345',
            ],
        ],
    ]);

$checkoutUrl = $response->json('data.attributes.checkout_url');
return redirect($checkoutUrl);
```

---

### Option B — Payment Intents API (Custom Native UI)

**Best for:** Mobile apps (Android/Kotlin), headless checkouts, fully custom UI.

**How it works:**
1. Backend creates a `PaymentIntent` via `POST /v1/payment_intents`.
2. Frontend attaches a `PaymentMethod` (card details or e-wallet token) via `POST /v1/payment_intents/{id}/attach`.
3. Handle 3D-Secure redirects if required.

> Use this only if you are building your own fully custom payment input fields.

---

### Option C — Payment Links (No-code)

**Best for:** Manual billing, chat commerce, SMS/email payment requests.

**How it works:** Generate a payment link from the PayMongo dashboard or via a simple `POST /v1/links` API call, then send it to your customer directly.

---

## 4. Webhooks (Recommended for Production)

Webhooks notify your server in real time when a payment is completed, failed, or refunded — without the customer needing to stay on the page.

**Common events to listen for:**

| Event | Trigger |
|-------|--------|
| `payment.paid` | A payment was successfully completed |
| `payment.failed` | A payment attempt failed |
| `checkout_session.payment.paid` | A Checkout Session payment succeeded |
| `source.chargeable` | A GCash/Maya source is ready to be charged (older Sources API) |

**Register a webhook endpoint in the PayMongo dashboard:**
> Dashboard → Developers → Webhooks → Add Endpoint → enter your `https://yourapp.com/webhooks/paymongo`

**Verify webhook signature in Laravel:**
```php
$signature = $request->header('Paymongo-Signature');
$payload   = $request->getContent();
$secret    = config('services.paymongo.webhook_secret');

[$t, $te, $li] = array_column(
    array_map(fn($s) => explode('=', $s), explode(',', $signature)),
    1
);

$computedHmac = hash_hmac('sha256', "{$t}.{$payload}", $secret);

if (!hash_equals($computedHmac, $li)) {
    abort(401, 'Invalid webhook signature.');
}
```

---

## 5. Environment Configuration (`.env`)

```dotenv
PAYMONGO_PUBLIC_KEY=pk_test_your_public_key_here
PAYMONGO_SECRET_KEY=sk_test_your_secret_key_here
PAYMONGO_WEBHOOK_SECRET=whsk_your_webhook_secret_here
```

Add this to your `config/services.php`:
```php
'paymongo' => [
    'public_key'     => env('PAYMONGO_PUBLIC_KEY'),
    'secret_key'     => env('PAYMONGO_SECRET_KEY'),
    'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
],
```

---

## 6. Official Resources

| Resource | Link |
|----------|------|
| PayMongo API Reference | https://developers.paymongo.com/reference |
| Developer Documentation | https://developers.paymongo.com/docs |
| Testing (Mock GCash, Cards) | https://developers.paymongo.com/docs/testing |
| E-Commerce Plugin Integrations | https://docs.paymongo.com/docs/e-commerce-integrations |
| PayMongo Dashboard | https://dashboard.paymongo.com |
