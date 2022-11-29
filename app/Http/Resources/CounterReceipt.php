<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Methods\CounterReceiptMethod;

class CounterReceipt extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $department = [
            "id"=> $this->department_id,
            "name"=> $this->department
        ];
        $transaction = [
            "id"=>$this->id,
            "date_countered"=>$this->date_countered,
            "counter_receipt_no"=>$this->counter_receipt_no,
            
        ];
        $receipt_type = [
            "id"=>$this->receipt_type_id,
            "type"=>$this->receipt_type,
        ];

        return [
            "transaction"=>$transaction,
            "supplier"=>[
                "id"=> $this->supplier_id,
                "name"=> $this->supplier
            ],
            "remarks"=>$this->remarks,
            "counter_receipt"=>[
                "department"=>$department,
                "receipt_type"=>$receipt_type,
                "date_transaction"=>$this->date_transaction,
                "receipt_no"=>$this->receipt_no,
                "amount"=>$this->amount,
                "status"=>$this->status
            ],
        ];
    }
}
