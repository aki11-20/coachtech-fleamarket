<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function profile () {
        return $this->hasOne(Profile::class);
    }

    public function orders () {
        return $this->hasMany(Order::class);
    }

    public function items() {
        return $this->hasMany(Item::class, 'user_id');
    }

    public function likedItems () {
        return $this->belongsToMany(Item::class, 'likes', 'user_id', 'item_id')
        ->withTimestamps()
        ->withPivot('created_at');
    }

    public function comments () {
        return $this->hasMany(Comment::class);
    }

    public function purchasedItems() {
        return $this->belongsToMany(Item::class, 'orders', 'user_id', 'item_id')
        ->withTimestamps()
        ->withPivot(['payment_type', 'postal_code', 'address', 'building']);
    }
}
