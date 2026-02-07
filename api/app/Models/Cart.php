<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // Helpers
    public function getTotalAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->product->getEffectivePrice() * $item->quantity;
        });
    }

    public function getItemCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    public function clear(): void
    {
        $this->items()->delete();
    }
}
