<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'discount_price' => $this->discount_price ? (float) $this->discount_price : null,
            'effective_price' => $this->getEffectivePrice(),
            'discount_percentage' => $this->getDiscountPercentage(),
            'stock_quantity' => $this->stock_quantity,
            'unit' => $this->unit,
            'is_active' => $this->is_active,
            'is_approved' => $this->is_approved,
            'admin_remarks' => $this->when($request->user()?->role === 'vendor' || $request->user()?->role === 'admin', $this->admin_remarks),
            'average_rating' => $this->getAverageRating(),
            'reviews_count' => $this->when(isset($this->reviews_count), $this->reviews_count),
            'vendor' => new UserResource($this->whenLoaded('vendor')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'subcategory' => new SubcategoryResource($this->whenLoaded('subcategory')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'primary_image' => $this->when(!$this->relationLoaded('images'), fn() => $this->primaryImage() ? new ProductImageResource($this->primaryImage()) : null),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
