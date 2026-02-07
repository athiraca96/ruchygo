<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'vendor_id',
        'category_id',
        'subcategory_id',
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'stock_quantity',
        'unit',
        'is_active',
        'is_approved',
        'admin_remarks',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_approved' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name) . '-' . Str::random(6);
            }
        });
    }

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeAvailable($query)
    {
        return $query->active()->approved()->where('stock_quantity', '>', 0);
    }

    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    // Helpers
    public function getEffectivePrice(): float
    {
        return $this->discount_price ?? $this->price;
    }

    public function hasDiscount(): bool
    {
        return $this->discount_price !== null && $this->discount_price < $this->price;
    }

    public function getDiscountPercentage(): ?float
    {
        if (!$this->hasDiscount()) {
            return null;
        }
        return round((($this->price - $this->discount_price) / $this->price) * 100, 2);
    }

    public function getAverageRating(): ?float
    {
        $avg = $this->reviews()->where('is_approved', true)->avg('rating');
        return $avg ? round($avg, 1) : null;
    }

    public function primaryImage(): ?ProductImage
    {
        return $this->images()->where('is_primary', true)->first()
            ?? $this->images()->orderBy('sort_order')->first();
    }
}
