<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Profile extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'nickname',
        'image',
        'postal_code',
        'address',
        'building',
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function getImageUrlAttribute(): ?string {
        if (!$this->image) return null;
        $path = ltrim($this->image, '/');
        if(Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }
        return Storage::url($path);
    }
}
