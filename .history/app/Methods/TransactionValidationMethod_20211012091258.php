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

 public static function transaction_result($code,$message,$data){
    return [
        "code" => $code,
        "message" => $message,
        "data" => null,
    ];
 }

  public static function padValidation($fields,$tag_id,$date_requested,$staus,$transaction_id){

    if(!isset($fields['document_no'])){

    }

  }
 }
