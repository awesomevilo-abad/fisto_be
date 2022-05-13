<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Department;
use App\Models\Location;
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
        $locations =  DB::table('departments')->get();

        return $departments;
        return $departments->mapToGroups(function ($item,$v) use ($locations) {
            return $item;
            // return [


            //     $item->location=
            //     Location::withTrashed()
            //     ->with('departments')
            //     ->whereHas ('departments',function($q)use($search){$q->where('departments.department_id', $);})
            //     ->get(['id','location as name']),
            // ];
        });

        return [
            "id" => $this->id,
            "name" => $this->company,
            "departments" => $departments,
        ];
    }
}
