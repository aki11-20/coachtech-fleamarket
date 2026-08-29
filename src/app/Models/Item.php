<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'product_name',
        'brand_name',
        'description',
        'price',
        'condition',
        'image',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
    public function likes() {
        return $this->belongsToMany(User::class, 'likes', 'item_id', 'user_id')->withTimestamps();
    }
    public function isLikedBy($user): bool {
        if (!$user) return false;
        return $this->likes()->where('user_id', $user->id)->exists();
    }
    public function comments() {
        return $this->hasMany(Comment::class, 'item_id')->latest();
    }
    public function categories() {
        return $this->belongsToMany(Category::class, 'category_item','item_id', 'category_id')->withTimestamps();
    }
    public function order() {
        return $this->hasOne(Order::class, 'item_id')->active();
    }
}
