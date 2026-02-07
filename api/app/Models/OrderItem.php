<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'vendor_id',
        'product_name',
        'product_price',
        'quantity',
        'total',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'product_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    // Scopes
    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    // Helpers
    public function canUpdateStatus(): bool
    {
        return !in_array($this->status, ['delivered', 'cancelled']);
    }
}
