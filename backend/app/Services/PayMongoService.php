<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class PayMongoService
{
    protected string $baseUrl = 'https://api.paymongo.com/v1';
    protected string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.paymongo.secret_key');
    }

    /**
     * Create a PayMongo Checkout Session.
     *
     * @param  array  $lineItems   e.g. [['name'=>'Item','amount'=>50000,'currency'=>'PHP','quantity'=>1]]
     * @param  array  $options     Optional overrides (description, success_url, cancel_url, etc.)
     * @return array
     */
    public function createCheckoutSession(array $lineItems, array $options = []): array
    {
        $payload = [
            'data' => [
                'attributes' => array_merge([
                    'line_items'           => $lineItems,
                    'payment_method_types' => ['gcash', 'paymaya', 'card', 'qrph', 'billease'],
                    'success_url'          => config('services.paymongo.success_url'),
                    'cancel_url'           => config('services.paymongo.cancel_url'),
                    'description'          => 'LYR PayMongo Test Order',
                ], $options),
            ],
        ];

        $response = $this->request('POST', '/checkout_sessions', $payload);

        return $response->json('data');
    }

    /**
     * Retrieve a single Checkout Session by ID.
     */
    public function getCheckoutSession(string $id): array
    {
        return $this->request('GET', "/checkout_sessions/{$id}")->json('data');
    }

    /**
     * List payments (most recent first).
     */
    public function listPayments(int $limit = 10): array
    {
        return $this->request('GET', "/payments?limit={$limit}")->json('data') ?? [];
    }

    /**
     * Retrieve a single Payment by ID.
     */
    public function getPayment(string $id): array
    {
        return $this->request('GET', "/payments/{$id}")->json('data');
    }

    /**
     * Verify incoming webhook signature from PayMongo.
     *
     * @param  string  $rawPayload   Raw request body string
     * @param  string  $signatureHeader  Value of Paymongo-Signature header
     * @return bool
     */
    public function verifyWebhookSignature(string $rawPayload, string $signatureHeader): bool
    {
        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = explode('=', $part, 2);
            $parts[$key] = $value;
        }

        if (empty($parts['t']) || empty($parts['li'])) {
            return false;
        }

        $secret   = config('services.paymongo.webhook_secret');
        $computed = hash_hmac('sha256', "{$parts['t']}.{$rawPayload}", $secret);

        return hash_equals($computed, $parts['li']);
    }

    // ------------------------------------------------------------------
    // Internal HTTP helper
    // ------------------------------------------------------------------

    protected function request(string $method, string $path, array $body = []): Response
    {
        $request = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->contentType('application/json');

        return match (strtoupper($method)) {
            'GET'  => $request->get("{$this->baseUrl}{$path}"),
            'POST' => $request->post("{$this->baseUrl}{$path}", $body),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }
}
