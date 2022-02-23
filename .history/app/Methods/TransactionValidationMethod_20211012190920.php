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

 public static function insertPODetails($po_count,$fields,$tag_id){

    $po_total_amount = 0;
    $po_total_qty = 0;

    for($i=0;$i<$po_count;$i++){
        $po_no = $fields['po_group'][$i]['po_no'];
        $po_amount = TransactionValidationMethod::convertStringToNumber($fields['po_group'][$i]['po_amount']);
        $po_qty = TransactionValidationMethod::convertStringToNumber($fields['po_group'][$i]['po_qty']);
        $po_total_amount = $po_total_amount + $po_amount;
        $po_total_qty = $po_total_amount + $po_qty;

        $insert_po_group = POGroupBatches::create([
            'tag_id' => $tag_id
            , "po_no" => $po_no
        ]);

        $insert_po_batch = POBatch::create([
            'tag_id' => $tag_id,
            'po_no' => $po_no
            , "po_amount" => $po_amount
            , "po_qty" => $po_qty
        ]);

    }
 }

 public static function insertTransaction($fields,$transaction_id,$po_total_amount,$po_total_qty,$tag_id,$date_requested,$status){
    $new_transaction = Transaction::create([
        'transaction_id' => $transaction_id
        , "users_id" => $fields['users_id']
        , "id_prefix" => $fields['id_prefix']
        , "id_no" => $fields['id_no']
        , "first_name" => $fields['first_name']
        , "middle_name" => $fields['middle_name']
        , "last_name" => $fields['last_name']
        , "suffix" => $fields['suffix']
        , "department" => $fields['department']
        , "document_id" => $fields['document_id']
        , "document_type" => $fields['document_type']
        , "payment_type" => $fields['payment_type']
        , "category_id" => $fields['category_id']
        , "category" => $fields['category']
        , "company_id" => $fields['company_id']
        , "company" => $fields['company']
        , "document_no" => $fields['document_no']
        , "supplier_id" => $fields['supplier_id']
        , "supplier" => $fields['supplier']
        , "document_date" => $fields['document_date']
        , "document_amount" => $fields['document_amount']
        , "remarks" => $fields['remarks']
        , "po_total_amount" => $po_total_amount
        , "po_total_qty" => $po_total_qty

        , "tag_id" => $tag_id
        , "tagging_tag_id" => 0
        , "date_requested" => $date_requested
        , "status" => $status,
    ]);
 }


public static function padValidation($fields,$tag_id,$date_requested,$status,$transaction_id){

    $documentNoValidation = TransactionValidationMethod::documentNoValidation($fields['document_no']);

    if(isset($documentNoValidation)){
        return $documentNoValidation;
    }

    $po_count = count($fields['po_group']);
    $po_total_amount = 0;
    $po_total_qty = 0;

    for($i=0;$i<$po_count;$i++){
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

    $insertPODetails = TransactionValidationMethod::insertPODetails($po_count,$fields,$tag_id);

    $insertTransaction = TransactionValidationMethod::insertTransaction($fields,$transaction_id,$po_total_amount,$po_total_qty,$tag_id,$date_requested,$status);

    return TransactionValidationMethod::result(200,"Request Submitted",$insertTransaction);
}



public static function prmValidation($fields,$tag_id,$date_requested,$status,$transaction_id){

    $documentNoValidation = TransactionValidationMethod::documentNoValidation($fields['document_no']);


    $fields = $fields ?? null;
    
    // if(isset($documentNoValidation)){
    //     return $documentNoValidation;
    // }

    $po_total_amount = null;
    $po_total_qty = null;

    $insertTransaction = TransactionValidationMethod::insertTransaction($fields,$transaction_id,$po_total_amount,$po_total_qty,$tag_id,$date_requested,$status);

    return TransactionValidationMethod::result(200,"Request Submitted",$insertTransaction);
  }
 }
