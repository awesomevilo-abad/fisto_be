<?php

namespace App\Methods;

use Illuminate\Validation\ValidationException;
use App\Models\CounterReceipt;
use App\Methods\GenericMethod;

class CounterReceiptMethod{

    
    public static function generate_cr_no(){
        $id = CounterReceipt::select('counter_receipt_no')->max('counter_receipt_no');
        $id = (!$id)?1:$id+1;
        return $id;
    }

    public static function duplicate_counter($fields,$counter_receipt_no=0){
        $supplier_id = $fields['supplier']['id'];
        $counter_receipt = $fields['counter_receipt'];
        $error_summary = [];

        foreach($counter_receipt as $k=>$receipt){
            $receipt_no = $receipt['receipt_no'];

            $is_duplicate = CounterReceipt::where('supplier_id',$supplier_id)
            ->where('receipt_no',$receipt_no)
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

    public static function is_duplicate_receipt($supplier_id,$receipt_no, $counter_receipt_no=0){
        $is_duplicate = CounterReceipt::where('supplier_id',$supplier_id)
        ->where('receipt_no',$receipt_no)
        ->when($counter_receipt_no, function ($query) use ($counter_receipt_no){
            $query->where('counter_receipt_no','<>',$counter_receipt_no);
        })
        ->exists();

        if($is_duplicate){
            $errorMessage = GenericMethod::resultLaravelFormat('counter_receipt.receipt_no',["Receipt number already exist."]);
            return GenericMethod::resultResponse('invalid','',$errorMessage);  
        }
    }

    public static function create_counter($fields){
        $counter_receipt = $fields['counter_receipt'];
        $date_countered = date('Y-m-d');
        $counter_receipt_no = CounterReceiptMethod::generate_cr_no();

        foreach($counter_receipt as $receipt){
            $counter_receipt = CounterReceipt::create([
                "date_countered"=>$date_countered,
                "counter_receipt_no"=>$counter_receipt_no,
                "supplier_id"=>$fields['supplier']['id'],
                "supplier"=>$fields['supplier']['name'],
                "department_id"=>$receipt['department']['id'],
                "department"=>$receipt['department']['name'],
                "receipt_type"=>$receipt['receipt_type'],
                "receipt_no"=>$receipt['receipt_no'],
                "date_transaction"=>$receipt['date_transaction'],
                "amount"=>$receipt['amount'],
                "status"=>"Pending",
            ]);

        }

        return $counter_receipt;
    }

}