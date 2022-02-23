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

  public static function padValidation($fields,$tag_id,$date_requested,$staus,$transaction_id){

    $documentNoValidation = TransactionValidationMethod::documentNoValidation($fields['document_no']);

    if(isset($documentNoValidation)){return $documentNoValidation;}

    $po_validation_count = count($fields['po_group']);
    $po_validation_total_amount = 0;
    $po_validation_total_qty = 0;
    $rr_validation_total_qty = 0;

    for($i=0;$i<$po_valida){

    }

  }
 }
