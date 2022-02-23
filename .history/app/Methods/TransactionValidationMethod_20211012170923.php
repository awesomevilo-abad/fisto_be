<?php

namespace App\Methods;

use App\Methods\GenericMethod;

use App\Models\Transaction;
use App\Models\POBatch;
use App\Models\RRBatch;
use App\Models\ReferrenceBatch;
use App\Models\ReferrenceGroupBatches;
use App\Models\POGroupBatches;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionValidationMethod
 {

 public static function result($code,$message,$data){
    return [
        "code" => $code,
        "message" => $message,
        "data" => $data,
    ];
 }

 public static function documentNoValidation($doc_no){
    if(!isset($doc_no)){
        return TransactionValidationMethod::result(
             404, "Document number is null", null
         );
     }

     if(GenericMethod::validateIfDocumentNoExist($doc_no) > 0){
         return TransactionValidationMethod::result(
             403,"Document No. already exist in other Document Types",null
         );
     }
 }

 public static function convertStringToNumber($stringNumber){
    return $number =(float) str_replace(',', '', $stringNumber);
 }

  public static function padValidation($fields,$tag_id,$date_requested,$staus,$transaction_id){

    $documentNoValidation = TransactionValidationMethod::documentNoValidation($fields['document_no']);

    if(isset($documentNoValidation)){
        return $documentNoValidation;
    }

    $po_validation_count = count($fields['po_group']);
    $po_total_amount = 0;
    $po_total_qty = 0;

    for($i=0;$i<$po_validation_count;$i++){
        $po_no = $fields['po_group'][$i]['po_no'];
        $po_amount = TransactionValidationMethod::convertStringToNumber($fields['po_group'][$i]['po_amount']);
        $po_qty = TransactionValidationMethod::convertStringToNumber($fields['po_group'][$i]['po_qty']);
        $po_total_amount = $po_total_amount + $po_amount;
        $po_total_qty = $po_total_amount + $po_qty;

        if (GenericMethod::validateIfPONoExist($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no) > 0){
            $duplicate_po_array[] =  $po_no;
        }
    }

    if(isset($duplicate_po_array)){return TransactionValidationMethod::result(403,"PO No. already exist in the company and supplier",$duplicate_po_array);}

    if($fields['document_amount'] != $po_total_amount){return TransactionValidationMethod::result(400,"Document amount must be equal to total PO Amount",null);}

    return $i;

}
 }
