<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'vendor_id' => $this->vendor_id,
            'product_name' => $this->product_name,
            'product_price' => (float) $this->product_price,
            'quantity' => $this->quantity,
            'total' => (float) $this->total,
            'status' => $this->status,
            'can_update_status' => $this->canUpdateStatus(),
            'product' => new ProductResource($this->whenLoaded('product')),
            'vendor' => new UserResource($this->whenLoaded('vendor')),
            'order' => new OrderResource($this->whenLoaded('order')),
        ];
    }
}
