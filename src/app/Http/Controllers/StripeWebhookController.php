<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Stripe\Exception\SignatureVerificationException;
use Throwable;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    private const HANDLED_EVENTS = [
        'checkout.session.completed',
        'checkout.session.async_payment_succeeded',
        'checkout.session.async_payment_failed',
        'checkout.session.expired',
    ];

    private $stripePaymentService;

    public function __construct(StripePaymentService $stripePaymentService)
    {
        $this->stripePaymentService = $stripePaymentService;
    }

    public function handle(Request $request)
    {
        try {
            $event = $this->stripePaymentService->constructWebhookEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature')
            );
        } catch (UnexpectedValueException $exception) {
            return response()->json(['message' => 'Invalid payload.'], 400);
        } catch (SignatureVerificationException $exception) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Webhook configuration error.'], 500);
        }

        if (!in_array($event->type, self::HANDLED_EVENTS, true)) {
            return response()->json(['received' => true]);
        }

        try {
            $checkoutSession = $event->data->object;
            $voucherExpiresAt = null;

            if (
                $event->type === 'checkout.session.completed'
                && $checkoutSession->payment_status === 'unpaid'
            ) {
                $checkoutSession = $this->stripePaymentService
                    ->retrieveCheckoutSession($checkoutSession->id);
                $voucherExpiresAt = $this->stripePaymentService
                    ->getVoucherExpiresAt($checkoutSession);

                if (!$voucherExpiresAt) {
                    throw new RuntimeException('Konbini voucher expiration could not be retrieved.');
                }
            }

            DB::transaction(function () use ($event, $checkoutSession, $voucherExpiresAt) {
                $orderId = $checkoutSession->metadata->order_id ?? null;

                if (!is_numeric($orderId)) {
                    throw new UnexpectedValueException('Order metadata is invalid.');
                }

                $order = Order::query()
                    ->with('item')
                    ->whereKey((int) $orderId)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new UnexpectedValueException('The order could not be found.');
                }

                $this->stripePaymentService->assertSessionMatchesOrder($checkoutSession, $order);

                switch ($event->type) {
                    case 'checkout.session.completed':
                        if ($checkoutSession->payment_status === 'paid') {
                            $order->markAsPaid();
                        } elseif ($checkoutSession->payment_status === 'unpaid') {
                            $order->keepPendingUntil($voucherExpiresAt);
                        }
                        break;

                    case 'checkout.session.async_payment_succeeded':
                        $order->markAsPaid();
                        break;

                    case 'checkout.session.async_payment_failed':
                    case 'checkout.session.expired':
                        $order->markAsCancelled();
                        break;
                }
            });

            return response()->json(['received' => true]);
        } catch (UnexpectedValueException $exception) {
            report($exception);

            return response()->json(['message' => 'Webhook data does not match the order.'], 400);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Webhook processing failed.'], 500);
        }
    }
}
