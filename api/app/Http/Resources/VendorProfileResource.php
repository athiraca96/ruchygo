<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VendorProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_name' => $this->business_name,
            'business_address' => $this->business_address,
            'gst_number' => $this->gst_number,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_ifsc_code' => $this->bank_ifsc_code,
            'id_proof_document' => $this->id_proof_document ? Storage::url($this->id_proof_document) : null,
            'status' => $this->status,
            'admin_remarks' => $this->admin_remarks,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
