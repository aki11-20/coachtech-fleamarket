<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;
use App\Models\Order;

class StripeController extends Controller
{
    public function checkout(Order $order) {
        if ($order->item->order && $order->item->order->id !== $order->id) {
            return back()->withErrors(['purchase' => 'この商品はすでに購入されています']);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $amount = (int) $order->item->price;

        $session = CheckoutSession::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => [
                        'name' => $order->item->product_name,
                    ],
                    'unit_amount' => $amount,
                ],
                'quantity' => 1,
            ]],
            'success_url' => route('payments.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payments.cancel'),
            ]);
            return redirect($session->url);
    }

    public function success(Request $request) {
        return redirect()->route('items.index')->with('status', '決済が完了しました');
    }

    public function cancel() {
        return back()->withErrors(['purchase' => '決済をキャンセルしました']);
    }
}
