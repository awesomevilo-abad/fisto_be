<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Document;
use App\Models\User;
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
      

    public function toArray($request)
    {

       return $document_types = User::with('document_types')->get();

    //    return [
    //        "id"=>$this->id,
    //        "id_prefix"=>$this->id_prefix,
    //        "id_no"=>$this->id_no,
    //        "role"=>$this->role,
    //        "position"=>$this->position,
    //        "first_name"=>$this->first_name,
    //        "middle_name"=>$this->middle_name,
    //        "last_name"=>$this->last_name,
    //        "suffix"=>$this->suffix,
    //        "department"=>$this->department,
    //        "document_types"=>
    //    ];

    }
}
;