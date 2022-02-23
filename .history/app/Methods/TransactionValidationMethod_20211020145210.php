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

    public static function referrenceNoValidaton($ref_no){
        if(!isset($ref_no)){
            return TransactionValidationMethod::result(
                404, "Referrence number is null", null
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
            , "status" => $status

            , "utilities_category" => $fields['utilities_category']
            , "utilities_account_no" => $fields['utilities_account_no']
            , "utilities_consumption" => $fields['utilities_consumption']
            , "utilities_uom" => $fields['utilities_uom']
            , "utilities_receipt_no" => $fields['utilities_receipt_no']
            , "utilities_from" => $fields['utilities_from']
            , "utilities_to" => $fields['utilities_to']

            , "payroll_client" => $fields['payroll_client']
            , "payroll_category" => $fields['payroll_category']
            , "payroll_type" => $fields['payroll_type']
            , "payroll_from" => $fields['payroll_from']
            , "payroll_to" => $fields['payroll_to']

            , "pcf_date" => $fields['pcf_date']
            , "pcf_letter" => $fields['pcf_letter']
        ]);
    }

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

    public static function fullPOValidationAndCreateTransaction($fields,$tag_id,$transaction_id,$date_requested,$status){
        $validated_full_po_transaction= TransactionValidationMethod::fullPOValidation($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$fields['po_group']);

        if(count($validated_full_po_transaction['duplicate_po'])>0){
            return TransactionValidationMethod::result(403,"PO No already exist",$validated_full_po_transaction['duplicate_po']);
        }

        if($fields['document_amount'] != $validated_full_po_transaction['po_total_amount']){return TransactionValidationMethod::result(400,"Document amount must be equal to total PO Amount",null);}

        $insertPODetails = TransactionValidationMethod::insertPODetails($validated_full_po_transaction['po_count'],$fields,$tag_id);

        $insertTransaction = TransactionValidationMethod::insertTransaction($fields,$transaction_id,$validated_full_po_transaction['po_total_amount'],$validated_full_po_transaction['po_total_qty'],$tag_id,$date_requested,$status);

        return TransactionValidationMethod::result(200,"Request Submitted",$insertTransaction);
    }

    public static function getDRAmount($unit_price, $dr_qty){
        $unit_price = TransactionValidationMethod::convertStringToNumber($unit_price);
        $dr_qty = TransactionValidationMethod::convertStringToNumber($dr_qty);
        return $unit_price * $dr_qty;
    }






    public static function padValidation($fields,$tag_id,$date_requested,$status,$transaction_id){

        $fields['document_amount'] = TransactionValidationMethod::convertStringToNumber($fields['document_amount']);
        $documentNoValidation = TransactionValidationMethod::documentNoValidation($fields['document_no']);

        if(isset($documentNoValidation)){
            return $documentNoValidation;
        }
        return TransactionValidationMethod::fullPOValidationAndCreateTransaction($fields,$tag_id,$transaction_id,$date_requested,$status);

    }

    public static function prmValidation($fields,$tag_id,$date_requested,$status,$transaction_id){

        $fields['document_amount'] = TransactionValidationMethod::convertStringToNumber($fields['document_amount']);
        $documentNoValidation = TransactionValidationMethod::documentNoValidation($fields['document_no']);

        if(isset($documentNoValidation)){
            return $documentNoValidation;
        }

        $po_total_amount = null;
        $po_total_qty = null;

        $insertTransaction = TransactionValidationMethod::insertTransaction($fields,$transaction_id,$po_total_amount,$po_total_qty,$tag_id,$date_requested,$status);

        return TransactionValidationMethod::result(200,"Request Submitted",$insertTransaction);
    }

    public static function utilitiesValidation($fields,$tag_id,$date_requested,$status,$transaction_id){

        $fields['document_amount'] = TransactionValidationMethod::convertStringToNumber($fields['document_amount']);
        $documentNoValidation = TransactionValidationMethod::documentNoValidation($fields['document_no']);

        if(isset($documentNoValidation)){
            return $documentNoValidation;
        }

        if (GenericMethod::validateIfUtilityExist($fields['payment_type'],$fields['company_id'],
        $fields['supplier_id'],$fields['utilities_from']
        ,$fields['utilities_to'],$fields['utilities_category'],$fields['utilities_account_no']
        ,$fields['utilities_consumption'],$fields['utilities_uom']
        ,$fields['utilities_receipt_no']) > 0){
            return TransactionValidationMethod::result(403,"Utility transaction already exist",null);
         }

         $po_total_amount = null;
         $po_total_qty = null;

         $insertTransaction = TransactionValidationMethod::insertTransaction($fields,$transaction_id,$po_total_amount,$po_total_qty,$tag_id,$date_requested,$status);
         return TransactionValidationMethod::result(200,"Request Submitted",$insertTransaction);
    }

    public static function payrollValidation($fields,$tag_id,$date_requested,$status,$transaction_id){

        $fields['document_amount'] = TransactionValidationMethod::convertStringToNumber($fields['document_amount']);
        $documentNoValidation = TransactionValidationMethod::documentNoValidation($fields['document_no']);

        if(isset($documentNoValidation)){
            return $documentNoValidation;
        }

        if (GenericMethod::validateIfPayrollExist($fields['payment_type'],$fields['company_id'],
            $fields['supplier_id'],$fields['payroll_from']
            ,$fields['payroll_to'],$fields['payroll_client']
            ,$fields['payroll_category'],$fields['payroll_type']) > 0){
                return $response = [
                    "code" => 403,
                    "message" => "Payroll transaction already exist",
                        "data" => null,
                ];
            }

        $po_total_amount = null;
        $po_total_qty = null;

        $insertTransaction = TransactionValidationMethod::insertTransaction($fields,$transaction_id,$po_total_amount,$po_total_qty,$tag_id,$date_requested,$status);
        return TransactionValidationMethod::result(200,"Request Submitted",$insertTransaction);

    }

    public static function pcfValidation($fields,$tag_id,$date_requested,$status,$transaction_id){

        $fields['document_amount'] = TransactionValidationMethod::convertStringToNumber($fields['document_amount']);
        $documentNoValidation = TransactionValidationMethod::documentNoValidation($fields['document_no']);

        if(isset($documentNoValidation)){
            return $documentNoValidation;
        }

        if (GenericMethod::validateIfPCFExist($fields['payment_type'],$fields['company_id'],
        $fields['supplier_id'],$fields['pcf_date'],$fields['pcf_letter']) > 0){
            return TransactionValidationMethod::result(403,"PCF transaction already exist",null);
         }

        $po_total_amount = null;
        $po_total_qty = null;

        $insertTransaction = TransactionValidationMethod::insertTransaction($fields,$transaction_id,$po_total_amount,$po_total_qty,$tag_id,$date_requested,$status);

        return TransactionValidationMethod::result(200,"Request Submitted",$insertTransaction);
    }


    // ON GOING
    public static function receiptValidation($fields,$tag_id,$date_requested,$status,$transaction_id){

        $referrence_type = $fields['referrence_group'][0]['referrence_type'];

        $fields['referrence_group'][0]['referrence_amount'] = TransactionValidationMethod::convertStringToNumber($fields['referrence_group'][0]['referrence_amount']);
        $fields['referrence_group'][0]['referrence_qty'] = TransactionValidationMethod::convertStringToNumber($fields['referrence_group'][0]['referrence_qty']);

        $referrenceNoValidation = TransactionValidationMethod::referrenceNoValidaton($fields['referrence_group'][0]['referrence_no']);

        if(isset($referrenceNoValidation)){
            return $referrenceNoValidation;
        }

        if(strtoupper($fields['payment_type'])=="FULL"){

            $validated_full_po_transaction = TransactionValidationMethod::fullPOValidation($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$fields['po_group']);

            if(count($validated_full_po_transaction['duplicate_po'])>0){
                return TransactionValidationMethod::result(403,"PO No already exist",$validated_full_po_transaction['duplicate_po']);
            }

            return $fields['referrence_group'][0]['referrence_qty'];

            if(strtoupper($referrence_type)=="DR"){
                $doc_amount = TransactionValidationMethod::getDRAmount();

            }



        }
            return "Partial";
    }


    // PENDING
    public static function contractorsBillingValidation($fields,$tag_id,$date_requested,$status,$transaction_id){
    }

    public static function padWithCIPValidation($fields,$tag_id,$date_requested,$status,$transaction_id){
    }

    public static function prmWithCIPValidation($fields,$tag_id,$date_requested,$status,$transaction_id){
    }

    public static function receiptWithCIPValidation($fields,$tag_id,$date_requested,$status,$transaction_id){
    }

}
