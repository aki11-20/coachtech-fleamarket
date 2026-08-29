<?php

namespace Tests\Concerns;

use App\Models\Order;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Event;

trait InteractsWithStripeFakes
{
    protected function makeCheckoutSession(Order $order, array $overrides = []): CheckoutSession
    {
        $attributes = [
            'id' => $order->stripe_checkout_session_id ?: 'cs_test_order_' . $order->id,
            'object' => 'checkout.session',
            'status' => 'open',
            'payment_status' => 'unpaid',
            'amount_total' => (int) $order->item->price,
            'currency' => 'jpy',
            'expires_at' => now()->addHour()->timestamp,
            'url' => 'https://checkout.stripe.test/session/' . $order->id,
            'metadata' => [
                'order_id' => (string) $order->id,
                'item_id' => (string) $order->item_id,
                'user_id' => (string) $order->user_id,
                'payment_type' => $order->payment_type,
            ],
        ];

        return CheckoutSession::constructFrom(array_replace($attributes, $overrides));
    }

    protected function makeStripeEvent(string $type, CheckoutSession $session): Event
    {
        return Event::constructFrom([
            'id' => 'evt_test_' . str_replace('.', '_', $type),
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => $session],
        ]);
    }
}
