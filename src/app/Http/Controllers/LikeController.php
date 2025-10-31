<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\DB;


class LikeController extends Controller
{
    public function store(Request $request, $item_id) {
        $item = Item::findOrFail($item_id);
        $userId = $request->user()->id;

        if ($item->user_id === $userId) {
            return redirect()->route('items,show', $item_id)
            ->withErrors(['like' => '自分の出品にはいいねできません']);
        }

        DB::table('likes')->updateOrInsert(
            ['user_id' => $userId, 'item_id' => $item->id],
            ['created_at' => now(), 'updated_at' => now()]
        );

        return redirect()->route('items.show', $item_id)
        ->with('status', 'いいねしました。');
    }
    public function destroy(Request $request, $item_id) {
        $item = Item::findOrFail($item_id);
        $userId = $request->user()->id;

        DB::table('likes')
        ->where('user_id', $userId)
        ->where('item_id', $item->id)
        ->delete();
        
        return redirect()->route('items.show', $item_id)
        ->with('status', 'いいねを解除しました。');
    }
}
