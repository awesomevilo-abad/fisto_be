<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RequestLog extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $fullname = $this->transaction->first_name.' '.$this->transaction->middle_name.' '.$this->transaction->last_name;
        return [
            "id"=>$this->id
            ,"type"=>$this->type
            ,"date"=>$this->date   
            ,"transaction"=>[
                "id"=>$this->transaction_id
                ,"no"=>$this->transaction->transaction_no
                ,"description"=>$this->transaction->description
                ,"user"=>[
                    "id"=>$this->transaction->users_id
                    ,"name"=>$fullname
                ]
                ,"reason"=>[
                    "id"=>$this->transaction->reason_id,
                    "description"=>$this->transaction->reason,
                    "remarks"=>$this->transaction->reason_remarks,
                ], 
            ]
        ];
    }
}
