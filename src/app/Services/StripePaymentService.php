<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use RuntimeException;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

class StripePaymentService
{
    private $client;

    public function createCheckoutSession(Order $order): CheckoutSession
    {
        $order->loadMissing('item');

        $paymentMethodTypes = $order->payment_type === 'convenience'
            ? ['konbini']
            : ['card'];

        if (!$order->reserved_until) {
            throw new RuntimeException('Checkout expiration is not configured.');
        }

        $parameters = [
            'mode' => 'payment',
            'payment_method_types' => $paymentMethodTypes,
            'expires_at' => $order->reserved_until->timestamp,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => [
                        'name' => $order->item->product_name,
                    ],
                    'unit_amount' => (int) $order->item->price,
                ],
                'quantity' => 1,
            ]],
            'success_url' => route('payments.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payments.cancel', ['order' => $order->id]),
            'metadata' => [
                'order_id' => (string) $order->id,
                'item_id' => (string) $order->item_id,
                'user_id' => (string) $order->user_id,
                'payment_type' => $order->payment_type,
            ],
        ];

        if ($order->payment_type === 'convenience') {
            $parameters['payment_method_options'] = [
                'konbini' => [
                    'expires_after_days' => 3,
                ],
            ];
        }

        return $this->client()->checkout->sessions->create($parameters, [
            'idempotency_key' => 'checkout-order-' . $order->id,
        ]);
    }

    public function retrieveCheckoutSession(string $sessionId): CheckoutSession
    {
        return $this->client()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['payment_intent'],
        ]);
    }

    public function expireCheckoutSession(string $sessionId): CheckoutSession
    {
        return $this->client()->checkout->sessions->expire($sessionId, []);
    }

    public function constructWebhookEvent(string $payload, string $signature): Event
    {
        $webhookSecret = config('services.stripe.webhook_secret');

        if (!is_string($webhookSecret) || $webhookSecret === '') {
            throw new RuntimeException('Stripe webhook secret is not configured.');
        }

        return Webhook::constructEvent($payload, $signature, $webhookSecret);
    }

    public function getVoucherExpiresAt(CheckoutSession $session): ?Carbon
    {
        $paymentIntent = $session->payment_intent;

        if (is_string($paymentIntent)) {
            $paymentIntent = $this->client()->paymentIntents->retrieve($paymentIntent, []);
        }

        if (!$paymentIntent) {
            return null;
        }

        $expiresAt = $paymentIntent->next_action->konbini_display_details->expires_at ?? null;

        return $expiresAt
            ? Carbon::createFromTimestamp((int) $expiresAt, config('app.timezone'))
            : null;
    }

    public function assertSessionMatchesOrder(CheckoutSession $session, Order $order): void
    {
        $order->loadMissing('item');

        $metadata = $session->metadata;

        $matches = (string) $session->id === (string) $order->stripe_checkout_session_id
            && (string) ($metadata->order_id ?? '') === (string) $order->id
            && (string) ($metadata->item_id ?? '') === (string) $order->item_id
            && (string) ($metadata->user_id ?? '') === (string) $order->user_id
            && (string) ($metadata->payment_type ?? '') === (string) $order->payment_type
            && (int) $session->amount_total === (int) $order->item->price
            && strtolower((string) $session->currency) === 'jpy';

        if (!$matches) {
            throw new UnexpectedValueException('Stripe Checkout Session does not match the order.');
        }
    }

    private function client(): StripeClient
    {
        if ($this->client instanceof StripeClient) {
            return $this->client;
        }

        $secret = config('services.stripe.secret');

        if (!is_string($secret) || $secret === '') {
            throw new RuntimeException('Stripe secret is not configured.');
        }

        return $this->client = new StripeClient($secret);
    }
}
