<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class ChargingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $departments =  DB::table('departments')->where('company',$this->id)->get(['id','department as name']);
        $locations =  DB::table('locations')->where('company',$this->id)->get(['id','location as name']);

        return [
            "id" => $this->id,
            "name" => $this->company,
            "departments" => $departments,
            "locations" => $locations
        ];
    }
}
