<?php

namespace App\Methods;

use App\Methods\GenericMethod;
use App\Models\Transaction;
use App\Exceptions\FistoException;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionValidationMethod
 {
    public static function padValidation($fields,$date_requested,$transaction_id){
        
       return TransactionValidationMethod::fullPOValidation($fields['document']['payment_type'],$fields['document']['company']['id'],$fields['document']['supplier']['id'],$fields['po_group']);

        // if(count($validated_full_po_transaction['duplicate_po'])>0){
        //     return TransactionValidationMethod::result(403,"PO No already exist",$validated_full_po_transaction['duplicate_po']);
        // }

        // if($fields['document_amount'] != $validated_full_po_transaction['po_total_amount']){return TransactionValidationMethod::result(400,"Document amount must be equal to total PO Amount",null);}

        // $insertPODetails = TransactionValidationMethod::insertPODetails($validated_full_po_transaction['po_count'],$fields,$tag_id);

        // $insertTransaction = TransactionValidationMethod::insertTransaction($fields,$transaction_id,$validated_full_po_transaction['po_total_amount'],$validated_full_po_transaction['po_total_qty'],$tag_id,$date_requested,$status);

        // return TransactionValidationMethod::result(200,"Request Submitted",$insertTransaction);
    }
    


    public static function validateIfDocumentNoExist($doc_no){
        return  DB::table('transactions')
        ->where('document_no',$doc_no)
        ->where('transactions.state','!=','void')
        ->whereNotNull('document_no')->exists();
   
    }


    public static function validateIfDocumentNoExistUpdate($doc_no,$id,$transaction_id=null){
        $transactions = DB::table('transactions')
        ->when($transaction_id,function ($query) use($transaction_id){
            $query->where('transaction_id','<>',$transaction_id);
        }, function ($query) use ($id){
            $query->where('id','<>',$id);
        })
        ->where('document_no',$doc_no)
        ->whereNotNull('document_no')->count();
        return $transactions;

    }
    
// CONTINUATION     
    
    public static function fullPOValidation($payment_type,$company_id,$supplier_id,$po_group){

        $po_count = count($po_group);
        $po_total_amount = 0;

        for($i=0;$i<$po_count;$i++){
            $po_no = $po_group[$i]['no'];
            $po_amount = TransactionValidationMethod::convertStringToNumber($po_group[$i]['po_amount']);
            $po_total_amount = $po_total_amount + $po_amount;

            if (GenericMethod::validateIfPONoExist($payment_type,$company_id,$supplier_id,$po_no) > 0){
                $duplicate_po[] =  $po_no;
            }
        }

        $result["duplicate_po"]=[];

        if(isset($duplicate_po)){
            $result["duplicate_po"]=$duplicate_po;
        }

        $result["po_count"]=$po_count;
        $result["po_no"]=$po_no;
        $result["po_amount"]=$po_amount;
        $result["po_qty"]=$po_qty;
        $result["po_total_amount"]=$po_total_amount;
        $result["po_total_qty"]=$po_total_qty;
        $result["unit_price"]=$unit_price;

        return $result;
    }
}
