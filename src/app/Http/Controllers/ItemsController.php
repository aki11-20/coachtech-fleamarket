<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ExhibitionRequest;

class ItemsController extends Controller
{
    public function index (Request $request) {
        $tab = $request->query('tab', 'recommend');
        $keyword = $request->query('keyword');

        $queryBuilder = Item::query()
        ->withCount(['likes', 'comments'])
        ->with('order');

        if (!empty($keyword)) {
            $queryBuilder->where('product_name', 'like', "%{$keyword}%");
        }

        if ($tab === 'mylist') {
            if (auth()->check()) {
                $queryBuilder->whereHas('likes', function($subQuery) {
                    $subQuery->where('user_id', auth()->id());
            });
            } else {
                $items = collect();
                return view('index', compact('items', 'tab', 'keyword'));
            }
        } else {
            if (auth()->check()) {
                $queryBuilder
                ->where('user_id', '!=', auth()->id());
            }
            $queryBuilder
            ->latest();
        }

        $items = $queryBuilder
        ->get();
        
        return view('index', compact('tab', 'items', 'keyword'));
    }
    public function show ($item_id) {
        $item = Item::with(['categories', 'comments.user.profile', 'order'])
        ->withCount(['likes', 'comments'])
        ->findOrFail($item_id);

        $isLiked = auth()->check() ? $item->likes()->where('user_id', auth()->id())->exists() : false;
        return view('items.show', compact('item', 'isLiked'));
    }
    public function create () {
        $categories = Category::orderBy('name')->get();
        return view('items.sell', compact('categories'));
    }
    public function store (ExhibitionRequest $request) {
        $path = $request->file('image')->store('public/items');
        $publicPath = str_replace('public/', 'storage/', $path);

        $item = Item::create([
            'user_id' => Auth::id(),
            'product_name' => $request->product_name,
            'brand_name' => $request->brand_name,
            'description' => $request->description,
            'price' => $request->price,
            'condition' => $request->condition,
            'image' => $publicPath,
        ]);

        $item->categories()->sync($request->input('categories'));

        return redirect()
        ->route('items.show', ['item_id' => $item->id])
        ->with('status', '出品が完了しました');
    }
}
