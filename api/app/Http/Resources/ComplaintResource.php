<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status,
            'admin_response' => $this->admin_response,
            'user' => new UserResource($this->whenLoaded('user')),
            'order' => new OrderResource($this->whenLoaded('order')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
