<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'subtotal' => (float) $this->subtotal,
            'platform_fee' => (float) $this->platform_fee,
            'shipping_fee' => (float) $this->shipping_fee,
            'total' => (float) $this->total,
            'status' => $this->status,
            'shipping_address' => $this->shipping_address,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'can_be_cancelled' => $this->canBeCancelled(),
            'can_be_returned' => $this->canBeReturned(),
            'user' => new UserResource($this->whenLoaded('user')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->when(isset($this->items_count), $this->items_count),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
