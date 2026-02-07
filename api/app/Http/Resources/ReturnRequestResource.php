<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'type' => $this->type,
            'reason' => $this->reason,
            'status' => $this->status,
            'admin_remarks' => $this->admin_remarks,
            'user' => new UserResource($this->whenLoaded('user')),
            'order' => new OrderResource($this->whenLoaded('order')),
            'order_item' => new OrderItemResource($this->whenLoaded('orderItem')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
