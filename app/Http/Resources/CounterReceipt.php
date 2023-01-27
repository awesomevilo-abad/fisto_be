<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Methods\CounterReceiptMethod;
use App\Models\CounterReceipt as CounterReceiptModel;

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
        $counter_receipt_group = [];
        $counter_receipt = [];
        $counter_receipts = CounterReceiptModel::where('counter_receipt_no',$this->counter_receipt_no)
        ->where('state', '!=', 'counter-void')
        ->get();
        
        foreach ($counter_receipts as $receipt){
            
            $department = [
                "id"=> $receipt->department_id,
                "name"=> $receipt->department
            ];
            
            $receipt_type = [
                "id"=>$receipt->receipt_type_id,
                "type"=>$receipt->receipt_type,
            ];

            $counter_receipt = [
                "department"=>$department,
                "receipt_type"=>$receipt_type,
                "date_transaction"=>$receipt->date_transaction,
                "receipt_no"=>$receipt->receipt_no,
                "amount"=>$receipt->amount,
                "status"=>$receipt->status,
                "state"=>$receipt->state
            ];

            array_push($counter_receipt_group,$counter_receipt);
        }

        $transaction = [
            "id"=>$this->id,
            "date_countered"=>$this->date_countered,
            "counter_receipt_no"=>$this->counter_receipt_no,
            
        ];

        return [
            "transaction"=>$transaction,
            "reason"=>[
                "id"=>$this->reason_id,
                "description"=>$this->reason,
                "remarks"=>$this->reason_remarks,
            ],
            "supplier"=>[
                "id"=> $this->supplier_id,
                "name"=> $this->supplier
            ],
            "remarks"=>$this->remarks,
            "counter_receipt"=>$counter_receipt_group,
        ];
    }
}
