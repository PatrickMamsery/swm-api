<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'payment_id' => $this['payment_id'],
            'meter_id' => $this['meter_id'],
            'amount' => $this['amount'],
            'payment_method' => $this['payment_method'],
            'reference_number' => $this['reference_number'],
            'units' => $this['units'],
            'volume' => $this['volume'],
        ];
    }
}
