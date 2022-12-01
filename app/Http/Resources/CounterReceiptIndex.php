<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Methods\CounterReceiptMethod;
use App\Models\CounterReceipt as CounterReceiptModel;
use App\Models\Transaction;

class CounterReceiptIndex extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $transaction =  Transaction::where('supplier_id',$this->supplier_id)
        ->where('company_id',$this->company_id)
        ->where('referrence_no',"2133");
        
        if($transaction->exists()){
           $counter_receipt_status =  $this->stateChange($transaction->get()->first()->state);
        }
        else{
            $counter_receipt_status = "Unprocessed";
        }


        return
            [
                "id"=> $this->id,
                "date_countered"=> $this->date_countered,
                "date_transaction"=> $this->date_transaction,
                "counter_receipt_no"=> $this->counter_receipt_no,
                "receipt_type_id"=>$this->receipt_type_id,
                "receipt_type"=> $this->receipt_type,
                "receipt_no"=> $this->receipt_no,
                "supplier_id"=> $this->supplier_id,
                "supplier"=> $this->supplier,
                "department_id"=> $this->department_id,
                "department"=> $this->department,
                "amount"=> $this->amount,
                "status"=> $this->status,
                "state"=> $this->state,
                "counter_receipt_status"=> $counter_receipt_status
            ];
            
    }

    public function stateChange($state){

        switch($state){
            case "tag":
                    $state = "Tagged";
            break;
            case "request":
            case "pending":
                    $state = "Pending";
            break;
            case "hold":
                    $state = "Held";
            break;
            case "transmit":
                    $state = "Transmitted";
            break;
            case "receive-approver":
                    $state = "Received";
            break;
            case "receive-requestor":
                    $state = "Received";
            break;
            
            default:
                if(str_ends_with($state,"e")){
                    $state = ucfirst($state.'d');
                }else if(str_ends_with($state,"g")){
                    $state = ucfirst($state);
                }else{
                    $state = ucfirst($state.'ed');
                }
        }

        return $state;
    }
}
