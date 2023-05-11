<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MeterReadingResource extends JsonResource
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
            'id' => $this->id,
            'meter_id' => $this->meter_id,
            'customer' => $this->meter->customer->name,
            'flow_rate' => $this->flow_rate,
            'total_volume' => $this->total_volume,
            'reading_date' => $this->meter_reading_date,
            'reading_status' => $this->meter_reading_status,
            'reading_image' => $this->meter_reading_image,
            'reading_comment' => $this->meter_reading_comment,
        ];
    }
}
