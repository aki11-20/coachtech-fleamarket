<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Item;
use App\Models\Order;
use App\Services\StripePaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AddressRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;


class PurchaseController extends Controller
{
    private const CHECKOUT_EXPIRATION_MINUTES = 60;

    private $stripePaymentService;

    public function __construct(StripePaymentService $stripePaymentService)
    {
        $this->stripePaymentService = $stripePaymentService;
    }

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
        $user = Auth::user();
        $paymentType = $request->validated()['payment_type'];
        $shipping = session('purchase.address.'.$item_id);

        if ($shipping && !empty($shipping['postal_code']) && !empty($shipping['address'])) {
            $postal_code = $shipping['postal_code'];
            $address = $shipping['address'];
            $building = $shipping['building'] ?? null;
        } else {
            $profile = $user->profile;
            if (!$profile || !$profile->postal_code || !$profile->address) {
                return redirect()
                ->route('purchase.address', ['item_id' => $item_id])
                ->withErrors(['purchase' => '配送先が未設定です。配送先変更から住所を登録してください。']);
            }
            $postal_code = $profile->postal_code;
            $address     = $profile->address;
            $building    = $profile->building;
        }

        $order = DB::transaction(function () use (
            $item_id,
            $user,
            $paymentType,
            $postal_code,
            $address,
            $building
        ) {
            $item = Item::query()->lockForUpdate()->findOrFail($item_id);

            if ($item->user_id === $user->id) {
                throw ValidationException::withMessages([
                    'purchase' => '自分が出品した商品は購入できません',
                ]);
            }

            $hasPaidOrder = Order::query()
                ->where('item_id', $item->id)
                ->where('status', Order::STATUS_PAID)
                ->exists();

            if ($hasPaidOrder) {
                throw ValidationException::withMessages([
                    'purchase' => 'この商品はすでに購入されています',
                ]);
            }

            $hasPendingOrder = Order::query()
                ->where('item_id', $item->id)
                ->where('status', Order::STATUS_PENDING)
                ->exists();

            if ($hasPendingOrder) {
                throw ValidationException::withMessages([
                    'purchase' => 'この商品は現在購入手続き中です',
                ]);
            }

            return Order::create([
                'item_id' => $item->id,
                'user_id' => $user->id,
                'payment_type' => $paymentType,
                'status' => Order::STATUS_PENDING,
                'reserved_until' => now()->addMinutes(self::CHECKOUT_EXPIRATION_MINUTES),
                'postal_code' => $postal_code,
                'address' => $address,
                'building' => $building,
            ]);
        });

        $checkoutSession = null;

        try {
            $checkoutSession = $this->stripePaymentService->createCheckoutSession($order);

            $order->update([
                'stripe_checkout_session_id' => $checkoutSession->id,
                'reserved_until' => Carbon::createFromTimestamp(
                    (int) $checkoutSession->expires_at,
                    config('app.timezone')
                ),
            ]);

            session()->forget('purchase.address.'.$item_id);

            return redirect()->away($checkoutSession->url, 303);
        } catch (Throwable $exception) {
            if ($checkoutSession && $checkoutSession->id) {
                try {
                    $this->stripePaymentService->expireCheckoutSession($checkoutSession->id);
                } catch (Throwable $expireException) {
                    report($expireException);
                }
            }

            Order::query()
                ->whereKey($order->id)
                ->where('status', Order::STATUS_PENDING)
                ->update([
                    'status' => Order::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'reserved_until' => null,
                    'paid_at' => null,
                ]);

            report($exception);

            return redirect()
                ->route('purchase.show', ['item_id' => $item_id])
                ->withErrors(['purchase' => '決済画面を開始できませんでした。もう一度お試しください。']);
        }
    }

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!is_string($sessionId) || $sessionId === '') {
            return redirect()
                ->route('items.index')
                ->withErrors(['purchase' => '決済情報を確認できませんでした。']);
        }

        try {
            $checkoutSession = $this->stripePaymentService->retrieveCheckoutSession($sessionId);

            $order = DB::transaction(function () use ($checkoutSession) {
                $order = Order::query()
                    ->with('item')
                    ->where('stripe_checkout_session_id', $checkoutSession->id)
                    ->lockForUpdate()
                    ->first();

                if (!$order || $order->user_id !== Auth::id()) {
                    throw new RuntimeException('The Stripe Session owner could not be verified.');
                }

                $this->stripePaymentService->assertSessionMatchesOrder($checkoutSession, $order);

                if ($checkoutSession->payment_status === 'paid') {
                    $order->markAsPaid();
                }

                return $order;
            });

            $order = $order->fresh();

            if ($order->isPaid()) {
                $message = '決済が完了しました。';
            } elseif ($order->isPending()) {
                $message = '決済を受け付けました。支払い完了を確認中です。';
            } else {
                return redirect()
                    ->route('items.show', ['item_id' => $order->item_id])
                    ->withErrors(['purchase' => 'この注文はキャンセルされています。']);
            }

            return redirect()
                ->route('items.show', ['item_id' => $order->item_id])
                ->with('status', $message);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('items.index')
                ->withErrors(['purchase' => '決済状態を確認できませんでした。']);
        }
    }

    public function cancel(Request $request)
    {
        $orderId = $request->query('order');

        if (!is_numeric($orderId)) {
            return redirect()
                ->route('items.index')
                ->withErrors(['purchase' => 'キャンセル対象の注文を確認できませんでした。']);
        }

        $order = Order::query()
            ->with('item')
            ->whereKey((int) $orderId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$order || !$order->stripe_checkout_session_id) {
            return redirect()
                ->route('items.index')
                ->withErrors(['purchase' => 'キャンセル対象の注文を確認できませんでした。']);
        }

        if ($order->isPaid()) {
            return redirect()
                ->route('items.show', ['item_id' => $order->item_id])
                ->withErrors(['purchase' => '支払い済みの注文はキャンセルできません。']);
        }

        try {
            $checkoutSession = $this->stripePaymentService
                ->retrieveCheckoutSession($order->stripe_checkout_session_id);

            $this->stripePaymentService->assertSessionMatchesOrder($checkoutSession, $order);

            if ($checkoutSession->status === 'open') {
                $checkoutSession = $this->stripePaymentService
                    ->expireCheckoutSession($checkoutSession->id);
            }

            if ($checkoutSession->status === 'expired') {
                $orderStatus = DB::transaction(function () use ($order, $checkoutSession) {
                    $lockedOrder = Order::query()
                        ->with('item')
                        ->whereKey($order->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($lockedOrder->user_id !== Auth::id()) {
                        throw new RuntimeException('The order owner could not be verified.');
                    }

                    $this->stripePaymentService
                        ->assertSessionMatchesOrder($checkoutSession, $lockedOrder);

                    $lockedOrder->markAsCancelled();

                    return $lockedOrder->fresh()->status;
                });

                if ($orderStatus === Order::STATUS_PAID) {
                    return redirect()
                        ->route('items.show', ['item_id' => $order->item_id])
                        ->withErrors(['purchase' => '支払い済みの注文はキャンセルできません。']);
                }

                return redirect()
                    ->route('items.show', ['item_id' => $order->item_id])
                    ->with('status', '決済をキャンセルしました。');
            }

            return redirect()
                ->route('items.show', ['item_id' => $order->item_id])
                ->withErrors(['purchase' => '決済はキャンセルされていません。']);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('items.show', ['item_id' => $order->item_id])
                ->withErrors(['purchase' => '決済のキャンセルを確認できませんでした。']);
        }
    }
}
