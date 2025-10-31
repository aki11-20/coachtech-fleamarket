<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;

class PaymentController extends Controller
{
    public function success(Order $order) {
        return redirect()->route('items.index')->with('status', '購入手続きを完了してください');
    }

    public function cancel(Order $order) {
        return redirect()->route('purchase.show', ['item_id' => $order->item_id])
        ->withErrors(['purchase' => '決済をキャンセルしました']);
    }
}
