<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Item;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AddressRequest;
use Stripe\StripeClient;
use Illuminate\Http\Request;


class PurchaseController extends Controller
{
    public function show ($item_id) {
        $item = Item::with('order')->findOrFail($item_id);
        if($item->user_id === Auth::id()) {
            return redirect()
            ->route('items.show', ['item_id' => $item->id])
            ->withErrors(['purchase' => '自分が出品した商品は購入できません']);
        }
        if($item->order) {
            return redirect()
            ->route('items.show', ['item_id' => $item->id])
            ->withErrors(['purchase' => 'この商品はすでに購入されています']);
        }

        $profile = Auth::user()->profile;
        $shipping = session('purchase.address.'.$item_id);
        return view('purchase.show', compact('item', 'profile', 'shipping'));
    }
    public function address ($item_id) {
        $item = Item::findOrFail($item_id);
        $profile = Auth::user()->profile;
        $shipping = session('purchase.address.'.$item_id);
        return view('purchase.address', compact('item', 'profile', 'shipping'));
    }
    public function updateAddress ($item_id, AddressRequest $request) {
        session([
            'purchase.address.'.$item_id => [
                'postal_code' => $request->postal_code,
                'address' => $request->address,
                'building' => $request->building,
            ],
        ]);

        return redirect()
        ->route('purchase.show', ['item_id' => $item_id])
        ->with('status', '配送先住所を変更しました');
    }

    public function updatePayment($item_id, Request $request) {
        $request->validate([
            'payment_type' => 'required|in:card,convenience',
        ]);

        session(['purchase.payment_type.' . $item_id => $request->payment_type]);

        return redirect()
        ->route('purchase.show', ['item_id' => $item_id])
        ->with('status', '支払い方法を更新しました');
    }

    public function store (PurchaseRequest $request, $item_id) {
        $item = Item::with('order')->findOrFail($item_id);
        if($item->user_id === Auth::id()) {
            return back()->withErrors(['purchase' => '自分が出品した商品は購入できません']);
        }
        if($item->order) {
            return back()->withErrors(['purchase' => 'この商品はすでに購入されています']);
        }
       
        $paymentType = session('purchase.payment_type.' . $item_id) ?? $request->payment_type;
        
        $shipping = session('purchase.address.'.$item_id);

        if ($shipping && !empty($shipping['postal_code']) && !empty($shipping['address'])) {
            $postal_code = $shipping['postal_code'];
            $address = $shipping['address'];
            $building = $shipping['building'] ?? null;
        } else {
            $profile = Auth::user()->profile;
            if (!$profile || !$profile->postal_code || !$profile->address) {
                return redirect()
                ->route('purchase.address', ['item_id' => $item_id])
                ->withErrors(['purchase' => '配送先が未設定です。配送先変更から住所を登録してください。']);
            }
            $postal_code = $profile->postal_code;
            $address     = $profile->address;
            $building    = $profile->building;
        }
        $order = Order::create([
            'item_id' => $item->id,
            'user_id' => Auth::id(),
            'payment_type' => $paymentType,
            'postal_code' => $postal_code,
            'address' => $address,
            'building' => $building,
        ]);

        session()->forget('purchase.address.'.$item_id);

        $stripe = new StripeClient(config('services.stripe.secret'));
        $paymentMethodTypes = $paymentType === 'convenience' ? ['konbini'] : ['card'];

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => $paymentMethodTypes,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => ['name' => $item->product_name],
                    'unit_amount' => (int)$item->price,
                ],
                'quantity' => 1,
            ]],
            'success_url' => route('payments.success', ['order' => $order->id]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payments.cancel', ['order' => $order->id]),
            'metadata' => [
                'order_id' => (string)$order->id,
                'item_id' => (string)$item->id,
                'user_id' => (string)Auth::id(),
                'payment_type' => $paymentType,
            ],
        ]);

        return redirect()
        ->away($session->url, 303);
    }
}
