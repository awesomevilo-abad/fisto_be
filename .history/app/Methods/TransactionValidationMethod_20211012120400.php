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

 

  public static function padValidation($fields,$tag_id,$date_requested,$staus,$transaction_id){

    if(!isset($fields['document_no'])){
       return TransactionValidationMethod::result(
            404, "Document number is null", null
        );
    }

    if(GenericMethod::validateIfDocumentNoExist($fields['document_no']) > 0){
        return TransactionValidationMethod::result(
            403,"Document No. already exist in other Document Types",null
        );
    }

  }
 }
