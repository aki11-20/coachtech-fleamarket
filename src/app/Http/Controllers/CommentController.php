<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Comment;
use App\Http\Requests\CommentRequest;

class CommentController extends Controller
{
    public function store(CommentRequest $request, $item_id)
    {
        $item = Item::findOrFail($item_id);

        if ($item->order) {
            return back()->withErrors(['body' => '売却済み商品のためコメントできません。']);
        }
        $validated = $request->validated();

        $item->comments()->create([
            'user_id' => auth()->id(),
            'body' => $validated['body'],
        ]);

        return back()->with('status', 'コメントを投稿しました。');
    }
}
