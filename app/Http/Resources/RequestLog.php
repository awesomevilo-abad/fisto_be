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
        return [
            "log_id"=>$this->id
            ,"transaction"=>[
                "id"=>$this->transaction_id
                ,"no"=>$this->transaction_no
                ,"description"=>$this->description
                ,"status"=>[
                    "date"=>$this->date_status,
                    "action"=>$this->status,
                ],
                "reason"=>[
                    "id"=>$this->reason_id,
                    "description"=>$this->reason_description,
                    "remarks"=>$this->reason_remarks,
                ]
                ,"created_at"=>$this->created_at                
                ,"updated_at"=>$this->updated_at     
            ],
        ];
    }
}
