<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'product_id' => $this->product_id,
            'order_id' => $this->order_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'is_approved' => $this->is_approved,
            'user' => new UserResource($this->whenLoaded('user')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
