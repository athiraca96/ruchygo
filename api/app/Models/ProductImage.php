<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image_path',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Accessors
    public function getImageUrlAttribute(): string
    {
        return Storage::url($this->image_path);
    }

    // Scopes
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_primary', 'desc')->orderBy('sort_order');
    }
}
