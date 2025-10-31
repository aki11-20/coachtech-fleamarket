<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;

class MypageController extends Controller
{
    public function index (Request $request) {
        $tab = $request->query('tab', 'buy');
        $user = auth()->user();
        if(!$user) {
            return redirect()->route('login');
        }

        $profile = $user->profile;

        $sellingItems = collect();
        $purchasedItems = collect();

        if ($tab === 'sell') {
            $sellingItems = $user->items()
            ->with('order')
            ->latest()
            ->get();
        } else {
            $purchasedItems = $user->purchasedItems()
            ->with('order')
            ->orderBy('orders.created_at', 'desc')
            ->get();
        }

        return view('mypage.index', compact('user', 'profile', 'tab', 'sellingItems', 'purchasedItems'));
    }
}
