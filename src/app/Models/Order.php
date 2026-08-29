<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'item_id',
        'user_id',
        'payment_type',
        'status',
        'stripe_checkout_session_id',
        'reserved_until',
        'paid_at',
        'cancelled_at',
        'postal_code',
        'address',
        'building',
    ];

    protected $casts = [
        'reserved_until' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PAID]);
    }

    public function markAsPaid(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->forceFill([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'reserved_until' => null,
            'cancelled_at' => null,
        ])->save();

        return true;
    }

    public function markAsCancelled(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->forceFill([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'reserved_until' => null,
            'paid_at' => null,
        ])->save();

        return true;
    }

    public function keepPendingUntil($expiresAt): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->forceFill([
            'reserved_until' => $expiresAt,
            'paid_at' => null,
            'cancelled_at' => null,
        ])->save();

        return true;
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
    public function item() {
        return $this->belongsTo(Item::class);
    }
}
