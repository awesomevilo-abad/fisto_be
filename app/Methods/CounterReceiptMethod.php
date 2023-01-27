<?php

namespace App\Methods;

use Illuminate\Validation\ValidationException;
use App\Models\CounterReceipt;
use App\Models\Monitoring;
use App\Models\Transaction;
use App\Methods\GenericMethod;

class CounterReceiptMethod{

    public static function get_counter_details($id){
        $notice_count = CounterReceipt::findorFail($id);
        return $notice_count;
    }

    public static function add_notice_count($notice_count){
        $notice_count = $notice_count+1;
        return $notice_count;
    }
    
    public static function generate_cr_no(){
        $id = CounterReceipt::orderByDesc('id')->value('counter_receipt_no');
        $id = (!$id)?1:$id+1;
        return $id;
    }

    public static function get_trasanction_id($receipt_no,$supplier_id,$department_id){
        $transaction = Transaction::where('referrence_no',$receipt_no)
         ->where('supplier_id',$supplier_id)
         ->where('department_id',$department_id)
         ->first();
 
         if($transaction){
             return $transaction->id;
         }
    }

    public static function get_counter_receipt_id($receipt_no,$supplier_id,$department_id){
        $transaction = CounterReceipt::where('receipt_no',$receipt_no)
        ->where('supplier_id',$supplier_id)
        ->where('department_id',$department_id)
        ->first();

        if($transaction){
            return $transaction;
        }
    }
  
    public static function multiple_counter($counter){
        $error_type="duplicate";
        $receipt_nos = array_column($counter->counter_receipt,"receipt_no");
        $errors = [];

        foreach($receipt_nos as $k=>$v){
            $receipt_no = $v;
            foreach($receipt_nos as $j => $u){
                if($k == $j){
                    unset($receipt_nos[$j]);
                    $removal_of_receipt = $receipt_nos;
                    foreach($removal_of_receipt as $l => $w){
                        if(($receipt_no == $removal_of_receipt[$l])){
                            $error_details =[
                                "error_type"=>$error_type,
                                "line"=>($k+1) .' & '.($l+1),
                                "description"=>"Receipt number has a duplicate in counter receipt."
                            ];
                            array_push($errors, $error_details);
                        }
                    }
                }
            }
        }
        return $errors;
    }

    public static function duplicate_counter($fields,$counter_receipt_no=0){
        $supplier_id = $fields['supplier']['id'];
        $counter_receipt = $fields['counter_receipt'];
        $error_summary = [];

        foreach($counter_receipt as $k=>$receipt){
            $receipt_no = $receipt['receipt_no'];

            $is_duplicate = CounterReceipt::where('supplier_id',$supplier_id)
            ->where('receipt_no',$receipt_no)
            ->where('state','!=','counter-void')
            ->when($counter_receipt_no, function ($query) use ($counter_receipt_no){
                $query->where('counter_receipt_no','<>',$counter_receipt_no);
            })
            ->exists();

            if($is_duplicate){
                $duplicate_counter =[
                    "error_type"=>"invalid",
                    "line"=>$k+1,
                    "description"=>"Receipt number already use in supplier"
                ];
                array_push($error_summary, $duplicate_counter);
            }
        }

        return $error_summary;
    }

    public static function is_duplicate_receipt($supplier_id,$receipt_no, $transaction_id){

         $counter_receipt_no = CounterReceipt::where('id',$transaction_id)
        ->select('counter_receipt_no')->get();
        
        if(!$counter_receipt_no){
            $counter_receipt_no = 0;
        }else{
            $counter_receipt_no = $counter_receipt_no->first()->counter_receipt_no;
        }

        $is_duplicate = CounterReceipt::where('supplier_id',$supplier_id)
        ->where('receipt_no',$receipt_no)
        ->where('state','!=','counter-void')
        ->when($counter_receipt_no, function ($query) use ($counter_receipt_no){
            $query->where('counter_receipt_no','<>',$counter_receipt_no);
        })
        ->exists();

        if($is_duplicate){
            $errorMessage = GenericMethod::resultLaravelFormat('counter_receipt.receipt_no',["Receipt number already exist."]);
            return GenericMethod::resultResponse('invalid','',$errorMessage);  
        }
    }

    public static function create_counter($fields,$counter_receipt_no=0){
        $counter_receipt = $fields['counter_receipt'];
        $date_countered = date('Y-m-d');

        if($counter_receipt_no){
            $counter_receipt_no = $counter_receipt_no;
            CounterReceipt::where('counter_receipt_no',$counter_receipt_no)->delete();
        }else{
            $counter_receipt_no = CounterReceiptMethod::generate_cr_no();
        }

        foreach($counter_receipt as $receipt){
            $counter_receipt = CounterReceipt::create([
                "date_countered"        =>          $date_countered,
                "counter_receipt_no"    =>          $counter_receipt_no,
                
                "supplier_id"           =>          $fields['supplier']['id'],
                "supplier"              =>          $fields['supplier']['name'],
                "remarks"               =>          $fields['remarks'],

                "receipt_type_id"       =>          $receipt['receipt_type']['id'],
                "receipt_type"          =>          $receipt['receipt_type']['type'],
                "receipt_no"            =>          $receipt['receipt_no'],
                "amount"                =>          $receipt['amount'],
                "date_transaction"      =>          $receipt['date_transaction'],
                "department_id"         =>          $receipt['department']['id'],
                "department"            =>          $receipt['department']['name'],
                
                "state"                 =>          "pending",
                "status"                =>          "Pending"
            ]);
        }

        return $counter_receipt;
    }

    public static function update_flow_counter($fields, $id){

        if($fields['process'] == "counter"){
            if($fields['subprocess'] == "void"){
                $status= 'Voided';
                $state= 'counter-void';
            }
            $update_flow_status = CounterReceiptMethod::update_flow_status($fields,$status,$state,$id);
        }else if($fields['process'] == "monitoring"){
            if($fields['subprocess'] == "receive"){
                $status= 'Received';
                $state= 'monitoring-receive';
            }else if($fields['subprocess'] == "return"){
                $status= 'Returned';
                $state= 'monitoring-return';
            }else if($fields['subprocess'] == "unreturn"){
                $status= 'Unreturned';
                $state= 'monitoring-unreturn';
            }
            $add_counter_log = CounterReceiptMethod::add_counter_log($fields,$status,$state,$id);
            $update_flow_status = CounterReceiptMethod::update_flow_status($fields,$status,$state,$id);
        }

        return $update_flow_status;
    }

    public static function update_for_counter_memo($id,$receiver,$notice_count,$latest_notice){
        $update_counter_receipt = CounterReceipt::where('id',$id)
            ->update([
                "receiver"=>$receiver,
                "notice_count"=>$notice_count,
                "latest_notice"=>$latest_notice
            ]);
    }

    public static function update_flow_status($fields,$status,$state,$id){
        $is_with_reason = $fields['reason']['id'];
        $reason_id = NULL;
        $reason = NULL;
        $reason_remarks = NULL;

        if($is_with_reason){
            $reason_id =$fields['reason']['id'];
            $reason =$fields['reason']['description'];
            $reason_remarks =$fields['reason']['remarks'];
        }

       $is_received_in_monitoring = Monitoring::where('counter_receipt_id',$id)->exists();
        
       if($is_received_in_monitoring){
            $monitoring_id = Monitoring::where('counter_receipt_id',$id)->latest()->get()->first()->id;
            $monitoring_receipt_log = Monitoring::where('id',$monitoring_id)
            ->update([
                "status"=>$status,
                "state"=>$state,
                "reason_id"=>$reason_id,
                "reason"=>$reason,
                "reason_remarks"=>$reason_remarks
            ]);
       }
       $counter_receipt_log = CounterReceipt::where('id',$id)
       ->update([
           "status"=>$status,
           "state"=>$state,
           "reason_id"=>$reason_id,
           "reason"=>$reason,
           "reason_remarks"=>$reason_remarks
        ]);
        
           return $counter_receipt_log;

    }

    public static function add_counter_log($fields,$status,$state,$id){
        $counter_receipt =CounterReceipt::where('id',$id)->get([
            'date_countered',
            'date_transaction',
            'counter_receipt_no',
            'receipt_type',
            'receipt_no',
            'supplier_id',
            'supplier',
            'department_id',
            'department',
            'amount',
            'status',
            'state',
            'receiver',
            'remarks',
        ]);
         $counter_receipt = $counter_receipt->first();

        $log = Monitoring::create([
            "counter_receipt_id"=>$id
            ,"date_countered"=>$counter_receipt['date_countered']
            ,"date_transaction"=>$counter_receipt['date_transaction']
            ,"counter_receipt_no"=>$counter_receipt['counter_receipt_no']
            ,"receipt_type"=>$counter_receipt['receipt_type']
            ,"receipt_no"=>$counter_receipt['receipt_no']
            ,"supplier_id"=>$counter_receipt['supplier_id']
            ,"supplier"=>$counter_receipt['supplier']
            ,"department_id"=>$counter_receipt['department_id']
            ,"department"=>$counter_receipt['department']
            ,"amount"=>$counter_receipt['amount']
            ,"status"=>$status
            ,"state"=>$state
            ,"receiver"=>$counter_receipt['receiver']
            ,"remarks"=>$counter_receipt['remarks']
        ]);
        return $log;

    }
}