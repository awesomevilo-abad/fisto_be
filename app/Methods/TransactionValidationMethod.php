<?php

namespace App\Methods;

use App\Methods\GenericMethod;
use App\Models\Transaction;
use App\Exceptions\FistoException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionValidationMethod
 {
    public static function padValidation($fields,$date_requested,$transaction_id){
        
        $status = "Pending";
       return $documentNoValidation = TransactionValidationMethod::documentNoValidation($fields['document']['no']);
        
        if(isset($documentNoValidation)){
            return $documentNoValidation;
        }

        $validated_full_po_transaction= TransactionValidationMethod::fullPOValidation($fields['document']['payment_type'],$fields['company_id'],$fields['supplier_id'],$fields['po_group']);

        if(count($validated_full_po_transaction['duplicate_po'])>0){
            return TransactionValidationMethod::result(403,"PO No already exist",$validated_full_po_transaction['duplicate_po']);
        }

        if($fields['document_amount'] != $validated_full_po_transaction['po_total_amount']){return TransactionValidationMethod::result(400,"Document amount must be equal to total PO Amount",null);}

        $insertPODetails = TransactionValidationMethod::insertPODetails($validated_full_po_transaction['po_count'],$fields,$tag_id);

        $insertTransaction = TransactionValidationMethod::insertTransaction($fields,$transaction_id,$validated_full_po_transaction['po_total_amount'],$validated_full_po_transaction['po_total_qty'],$tag_id,$date_requested,$status);

        return TransactionValidationMethod::result(200,"Request Submitted",$insertTransaction);
    }
    
    public static function documentNoValidation($doc_no){
        if(!isset($doc_no)){
            throw new FistoException("Document number is empty.", 404, NULL, []);
        }
        
        if(TransactionValidationMethod::validateIfDocumentNoExist($doc_no) > 0){
            throw new FistoException("Document number already exist in other document types", 409, NULL, []);
        }
    }

    public static function validateIfDocumentNoExist($doc_no){
        $transactions = DB::table('transactions')
        ->where('document_no',$doc_no)
        ->whereNotNull('document_no')->first();
        if($transactions){
            return $transactions->count();
        }
        return 0;
    }

// CONTINUATION     
    
    public static function fullPOValidation($payment_type,$company_id,$supplier_id,$po_group){

        $po_count = count($po_group);
        $po_total_amount = 0;
        $po_total_qty = 0;

        for($i=0;$i<$po_count;$i++){
            $po_no = $po_group[$i]['po_no'];
            $po_amount = TransactionValidationMethod::convertStringToNumber($po_group[$i]['po_amount']);
            $po_qty = TransactionValidationMethod::convertStringToNumber($po_group[$i]['po_qty']);
            $unit_price = TransactionValidationMethod::convertStringToNumber($po_group[$i]['unit_price']);
            $po_total_amount = $po_total_amount + $po_amount;
            $po_total_qty = $po_total_qty + $po_qty;

            for($k=0;$k<count($po_group[$i]['rr_group']);$k++){
                $po_group[$i]['rr_group'][$k]['rr_no'] = TransactionValidationMethod::convertStringToNumber($po_group[$i]['rr_group'][$k]['rr_no']);
                $po_group[$i]['rr_group'][$k]['rr_qty'] = TransactionValidationMethod::convertStringToNumber($po_group[$i]['rr_group'][$k]['rr_qty']);
            }

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
