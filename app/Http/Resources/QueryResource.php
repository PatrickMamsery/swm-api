<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QueryResource extends JsonResource
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
            'query_date' => $this->query_date,
            'query_action' => $this->query_action,
            'description' => $this->description,
            'query_status' => $this->query_status,
        ];
    }
}
