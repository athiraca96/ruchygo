<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image ? Storage::url($this->image) : null,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'subcategories' => SubcategoryResource::collection($this->whenLoaded('subcategories')),
            'products_count' => $this->when(isset($this->products_count), $this->products_count),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
