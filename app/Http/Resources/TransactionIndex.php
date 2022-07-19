<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\POBatch;

class TransactionIndex extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $is_latest = 0;
        if(!empty($this->po_details)){
          if($this->po_details->last()!= null){
               $po_no = $this->po_details->last()->po_no;

               $transactions_ids = POBatch::with('transaction_ids')
               ->where('p_o_batches.po_no',$po_no)
               ->select(['request_id','po_no'])
               ->get();

            $transaction_obj = $transactions_ids->pluck(['transaction_ids']);
            $transaction_obj = $transaction_obj->filter();

               if ($this->id == $transaction_obj->last()->id){
                   $is_latest = 1;
               }
                
            $transactions_details = [
                "id"=> $this->id,
                "is_latest_transaction"=> $is_latest,
                "users_id"=>  $this->users_id,
                "request_id"=> $this->request_id,
                "supplier_id"=> $this->supplier_id,
                "document_id"=> $this->document_id,
                "transaction_id"=> $this->transaction_id,
                "document_type"=> $this->document_type,
                "payment_type"=> $this->payment_type,
                "supplier"=> $this->supplier,
                "remarks"=> $this->remarks,
                "date_requested"=> $this->date_requested,
                "company"=> $this->company,
                "department"=> $this->department,
                "location"=> $this->location,
                "document_no"=> $this->document_no,
                "document_amount"=> $this->document_amount,
                "referrence_no"=> $this->referrence_no,
                "referrence_amount"=> $this->referrence_amount,
                "status"=> $this->status,
                "users"=> $this->users,
                "po_details"=> $this->po_details
            ];
           }
           
           if( strtoupper($this->payment_type) == "FULL"){
                $is_latest=1;
           }

           $transactions_details = [
            "id"=> $this->id,
            "is_latest_transaction"=> $is_latest,
            "users_id"=>  $this->users_id,
            "request_id"=> $this->request_id,
            "supplier_id"=> $this->supplier_id,
            "document_id"=> $this->document_id,
            "transaction_id"=> $this->transaction_id,
            "document_type"=> $this->document_type,
            "payment_type"=> $this->payment_type,
            "supplier"=> $this->supplier,
            "remarks"=> $this->remarks,
            "date_requested"=> $this->date_requested,
            "company"=> $this->company,
            "department"=> $this->department,
            "location"=> $this->location,
            "document_no"=> $this->document_no,
            "document_amount"=> $this->document_amount,
            "referrence_no"=> $this->referrence_no,
            "referrence_amount"=> $this->referrence_amount,
            "status"=> $this->status,
            "users"=> $this->users,
            "po_details"=> $this->po_details
        ];
       }
       
       return $transactions_details;
    }
}
