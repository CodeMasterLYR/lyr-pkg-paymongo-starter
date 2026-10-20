<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PayMongoController extends Controller
{
    public function __construct(protected PayMongoService $payMongo) {}

    // ------------------------------------------------------------------
    // POST /api/checkout
    // Create a PayMongo Checkout Session
    // ------------------------------------------------------------------
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items'             => 'required|array|min:1',
            'items.*.name'      => 'required|string|max:255',
            'items.*.amount'    => 'required|integer|min:2000', // min PHP 20.00 (in centavos)
            'items.*.quantity'  => 'required|integer|min:1',
            'description'       => 'nullable|string|max:255',
        ]);

        $lineItems = array_map(fn ($item) => [
            'name'      => $item['name'],
            'amount'    => $item['amount'],
            'currency'  => 'PHP',
            'quantity'  => $item['quantity'],
        ], $validated['items']);

        $options = [];
        if (! empty($validated['description'])) {
            $options['description'] = $validated['description'];
        }

        $session = $this->payMongo->createCheckoutSession($lineItems, $options);

        return response()->json([
            'checkout_url' => $session['attributes']['checkout_url'],
            'session_id'   => $session['id'],
            'expires_at'   => $session['attributes']['expires_at'] ?? null,
        ]);
    }

    // ------------------------------------------------------------------
    // GET /api/checkout/{id}
    // Retrieve a single Checkout Session status
    // ------------------------------------------------------------------
    public function getSession(string $id): JsonResponse
    {
        $session = $this->payMongo->getCheckoutSession($id);

        return response()->json([
            'session_id'     => $session['id'],
            'status'         => $session['attributes']['payment_intent']['attributes']['status'] ?? 'unknown',
            'checkout_url'   => $session['attributes']['checkout_url'],
            'payment_method' => $session['attributes']['payment_method_used'] ?? null,
        ]);
    }

    // ------------------------------------------------------------------
    // GET /api/payments
    // List recent payments from PayMongo
    // ------------------------------------------------------------------
    public function listPayments(Request $request): JsonResponse
    {
        $limit    = (int) $request->query('limit', 10);
        $payments = $this->payMongo->listPayments(min($limit, 50));

        $mapped = collect($payments)->map(fn ($p) => [
            'id'             => $p['id'],
            'amount'         => $p['attributes']['amount'],
            'currency'       => $p['attributes']['currency'],
            'status'         => $p['attributes']['status'],
            'description'    => $p['attributes']['description'] ?? null,
            'payment_method' => $p['attributes']['source']['type'] ?? null,
            'paid_at'        => $p['attributes']['paid_at'] ?? null,
        ]);

        return response()->json(['data' => $mapped]);
    }

    // ------------------------------------------------------------------
    // POST /api/webhooks/paymongo  (No auth — verified by signature)
    // Handle incoming PayMongo webhook events
    // ------------------------------------------------------------------
    public function webhook(Request $request): JsonResponse
    {
        $signature = $request->header('Paymongo-Signature');

        if (! $signature || ! $this->payMongo->verifyWebhookSignature($request->getContent(), $signature)) {
            Log::warning('PayMongo webhook: invalid signature.', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $event   = $request->json('data.attributes.type');
        $payload = $request->json('data.attributes.data');

        Log::info("PayMongo webhook received: {$event}", ['payload' => $payload]);

        match ($event) {
            'payment.paid'                       => $this->handlePaymentPaid($payload),
            'payment.failed'                     => $this->handlePaymentFailed($payload),
            'checkout_session.payment.paid'      => $this->handleCheckoutPaid($payload),
            default                              => Log::info("PayMongo: unhandled event [{$event}]"),
        };

        return response()->json(['message' => 'Webhook received.']);
    }

    // ------------------------------------------------------------------
    // Private webhook event handlers
    // ------------------------------------------------------------------

    private function handlePaymentPaid(array $data): void
    {
        Log::info('Payment paid', [
            'id'     => $data['id'] ?? null,
            'amount' => $data['attributes']['amount'] ?? null,
        ]);
        // TODO: Mark order as paid in your database
    }

    private function handlePaymentFailed(array $data): void
    {
        Log::warning('Payment failed', [
            'id'            => $data['id'] ?? null,
            'failure_code'  => $data['attributes']['failed_code'] ?? null,
            'failure_msg'   => $data['attributes']['failed_message'] ?? null,
        ]);
        // TODO: Notify user or retry logic
    }

    private function handleCheckoutPaid(array $data): void
    {
        Log::info('Checkout session payment paid', ['session' => $data['id'] ?? null]);
        // TODO: Fulfill the order
    }
}
