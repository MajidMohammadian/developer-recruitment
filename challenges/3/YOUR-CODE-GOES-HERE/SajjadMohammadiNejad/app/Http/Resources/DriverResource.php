<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
/**
 * Driver Resource
 */
class DriverResource extends JsonResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $data = [
            'driver' => [
                'car_plate' => $this->car_plate,
                'car_model' => $this->car_model,
                'status'    => $this->status
            ]
        ];

        return $data;
    }
}
