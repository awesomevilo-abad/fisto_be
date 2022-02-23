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

    public static function referrenceNoValidaton($payment_type,$company_id,$supplier_id,$ref_no){
        if(!isset($ref_no)){
            return TransactionValidationMethod::result(
                404, "Referrence number is null", null
            );
        }

        if(GenericMethod::validateIfRefNoExist($payment_type,$company_id,$supplier_id,$ref_no) > 0){
            return TransactionValidationMethod::result(
                403,"Referrence No. already exist in other Transactions",$ref_no
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
                , "po_total_amount" => $po_total_amount
            ]);

            $insert_po_batch = POBatch::create([
                'tag_id' => $tag_id,
                'po_no' => $po_no
                , "po_amount" => $po_amount
                , "po_qty" => $po_qty
            ]);

        }
    }

    public static function insertRefDetails($fields,$tag_id){

        $insert_ref_group = ReferrenceGroupBatches::create([
            'tag_id' => $tag_id
            , "referrence_no" => $fields['referrence_group'][0]['referrence_no']
            , "referrence_total_amount" => $fields['referrence_group'][0]['referrence_amount']
        ]);

        $insert_ref_group = ReferrenceBatch::create([
            'tag_id' => $tag_id
            , "referrence_type" => $fields['referrence_group'][0]['referrence_type']
            , "referrence_no" => $fields['referrence_group'][0]['referrence_no']
            , "referrence_amount" => $fields['referrence_group'][0]['referrence_amount']
            , "referrence_qty" => $fields['referrence_group'][0]['referrence_qty']
        ]);
    }

    public static function insertTransaction($fields,$transaction_id,$po_total_amount,$po_total_qty,$tag_id,$date_requested,$status){
        $referrence_total_amount = null;
        $referrence_total_qty = null;
        $balance_po_ref_amount = null;
        $latest_unit_price = end($fields['po_group'])['unit_price'];

        if(isset($fields['referrence_group'][0]['referrence_amount'])){
            $referrence_total_amount = $fields['referrence_group'][0]['referrence_amount'];
        }

        if((strtoupper($fields['referrence_group'][0]['referrence_type'])=="DR") AND $fields['referrence_group'][0]['referrence_amount'] == NULL ){
            $referrence_total_amount = TransactionValidationMethod::getDRAmount($latest_unit_price,$fields['referrence_group'][0]['referrence_qty']);
        }

        if(isset($fields['referrence_group'][0]['referrence_qty'])){$referrence_total_qty = $fields['referrence_group'][0]['referrence_qty'];}

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
            , "referrence_total_amount" =>$referrence_total_amount
            , "referrence_total_qty" => $referrence_total_qty

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

            , "balance_po_ref_amount" => $balance_po_ref_amount
        ]);
    }

    public static function insertPartialTransaction($fields,$transaction_id,$po_total_amount,$po_total_qty,$tag_id,$date_requested,$status,$po_no,$balance_po_ref_amount,$balance_ref_po_qty){
        $referrence_total_amount = null;
        $referrence_total_qty = null;
        $latest_unit_price = end($fields['po_group'])['unit_price'];
        $balance_po_ref_amount = $balance_po_ref_amount;
       $balance_ref_po_qty = $balance_ref_po_qty;

        if(isset($fields['referrence_group'][0]['referrence_amount'])){$referrence_total_amount = $fields['referrence_group'][0]['referrence_amount'];}
        if(isset($fields['referrence_group'][0]['referrence_qty'])){$referrence_total_qty = $fields['referrence_group'][0]['referrence_qty'];}

        if((strtoupper($fields['referrence_group'][0]['referrence_type'])=="DR") AND $fields['referrence_group'][0]['referrence_amount'] == NULL ){
            $referrence_total_amount = TransactionValidationMethod::getDRAmount($latest_unit_price,$fields['referrence_group'][0]['referrence_qty']);
        }

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
            , "referrence_total_amount" =>$referrence_total_amount
            , "referrence_total_qty" => $referrence_total_qty

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

            , "balance_po_ref_amount" => $balance_po_ref_amount
            , "balance_po_ref_qty" => $balance_ref_po_qty
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

    public static function fullRefValidation($payment_type,$company_id,$supplier_id,$po_group){

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

    public static function getAllowableQty($is_allowable,$po_qty){
        $adjusted_po_qty = $po_qty;

        if($is_allowable){
            $allowable_qty =  $po_qty/10;
            $adjusted_po_qty =$po_qty + $allowable_qty;
        }

        return $adjusted_po_qty;
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

        if(GenericMethod::validateIfDocumentNoExist($fields['document_no']) > 0){
            return TransactionValidationMethod::result(
                403,"Document No. already exist in other Document Types",null
            );
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

        if(GenericMethod::validateIfDocumentNoExist($fields['document_no']) > 0){
            return TransactionValidationMethod::result(
                403,"Document No. already exist in other Document Types",null
            );
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

    public static function getExistingPODetails(){

    }


    // ON GOING
    public static function receiptValidation($fields,$tag_id,$date_requested,$status,$transaction_id){
        $referrence_type = $fields['referrence_group'][0]['referrence_type'];
        $fields['referrence_group'][0]['referrence_amount'] = TransactionValidationMethod::convertStringToNumber($fields['referrence_group'][0]['referrence_amount']);
        $referrence_amount= $fields['referrence_group'][0]['referrence_amount'];
        $fields['referrence_group'][0]['referrence_qty'] = TransactionValidationMethod::convertStringToNumber($fields['referrence_group'][0]['referrence_qty']);
        $referrenceNoValidation = TransactionValidationMethod::referrenceNoValidaton($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$fields['referrence_group'][0]['referrence_no']);
        $latest_unit_price = end($fields['po_group'])['unit_price'];

        return $fields['po_group'];

        if(isset($referrenceNoValidation)){
            return $referrenceNoValidation;
        }

        // FULL RECEIPT TRANSACTION
        if(strtoupper($fields['payment_type'])=="FULL"){

            $validated_full_po_transaction = TransactionValidationMethod::fullPOValidation($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$fields['po_group']);
            $po_allowable_qty =  TransactionValidationMethod::getAllowableQty($fields['is_allowable'],$validated_full_po_transaction['po_qty']);

            if(count($validated_full_po_transaction['duplicate_po'])>0){
                return TransactionValidationMethod::result(403,"PO No already exist",$validated_full_po_transaction['duplicate_po']);
            }

            if(($fields['is_allowable'] == true) AND ($referrence_amount != NULL)){
                return TransactionValidationMethod::result(400,"Allowable is valid on Qty based Full transactions only",null);
            }

            if((strtoupper($referrence_type)=="DR") AND ($referrence_amount == NULL)){
                if(!(($fields['referrence_group'][0]['referrence_qty'] >= $validated_full_po_transaction['po_qty']) AND ($fields['referrence_group'][0]['referrence_qty'] <= $po_allowable_qty))){
                    return TransactionValidationMethod::result(400,"Referrence Qty must be equal to total PO Qty or Reference Qty must be greater than PO Qty and less than allowable qty if allowable is triggered",null);
                }
            }else{
                if($referrence_amount != $validated_full_po_transaction['po_amount']){
                    return TransactionValidationMethod::result(400,"Referrence Amount must be equal to total PO Amount",null);
                }
            }

            $insertRefDetails = TransactionValidationMethod::insertRefDetails($fields,$tag_id);
            $insertPODetails = TransactionValidationMethod::insertPODetails($validated_full_po_transaction['po_count'],$fields,$tag_id);
            $insertTransaction = TransactionValidationMethod::insertTransaction($fields,$transaction_id,$validated_full_po_transaction['po_total_amount'],$validated_full_po_transaction['po_total_qty'],$tag_id,$date_requested,$status);
            return TransactionValidationMethod::result(200,"Request Submitted",$insertTransaction);
        }
        // PARTIAL RECEIPT TRANSACTION
        $po_total_amount = 0;
        $po_total_qty = 0;

        foreach($fields['po_group'] as $specific_po_details){

            $po_no= $specific_po_details['po_no'];
            $po_amount =(float) str_replace(',', '', $specific_po_details['po_amount']);
            $po_qty =(float) str_replace(',', '', $specific_po_details['po_qty']);
            $po_total_amount = $po_total_amount+$po_amount;
            $po_total_qty = $po_total_qty+$po_qty;
            $all_po[] = $po_no ;

            if(count(GenericMethod::validateIfPONoExistInDifferentSupplierReceiptPartial($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no)) > 0){
                if(GenericMethod::validateIfPONoExistInDifferentSupplier($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no) > 0){
                    $existing_po[] =  $po_no ;
                 }
            }

            if(GenericMethod::validateIfPONoExistInDifferentSupplier($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no) > 0){
               $duplicate_po[] = $po_no ;
            }
        }
        // ADDITIONAL PO
        if(isset($existing_po)){
            $additional_po = array_values((array_diff($all_po,$existing_po)));
            if(isset($additional_po)){
                foreach($additional_po as $specific_additional_po){
                    if(GenericMethod::validateIfPONoExistInDifferentSupplier($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$specific_additional_po) > 0){
                        $duplicate_po_in_additional_po[] = $specific_additional_po ;
                    }
                }

                if(isset($duplicate_po_in_additional_po)){
                    return TransactionValidationMethod::result(403,"PO No already exist in different batch",$duplicate_po_in_additional_po);
                }

                $additional_po_total_amount=0;
                $additional_po_total_qty=0;
                foreach($fields['po_group'] as $specific_po_details){
                    foreach($additional_po as $specific_additional_po){
                        // echo $specific_additional_po." == ".$specific_po_details['po_no'];
                        if($specific_additional_po == $specific_po_details['po_no']){
                            $additional_po_total_amount= $additional_po_total_amount + TransactionValidationMethod::convertStringToNumber($specific_po_details['po_amount']);
                            $additional_po_total_qty= $additional_po_total_qty + TransactionValidationMethod::convertStringToNumber($specific_po_details['po_amount']);
                        }
                    }
                }

                $balance_ref_po_amount = GenericMethod::getBalanceAmountOfRefPO($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],end($duplicate_po),$fields['document_amount']);
                $additional_po_amount_plus_balance =  $additional_po_total_amount + $balance_ref_po_amount[0]->balance_po_ref_amount;

                if($additional_po_amount_plus_balance == 0 ){
                    return TransactionValidationMethod::result(400,"Transaction Closed",array(["total_po_amount"=>$additional_po_amount_plus_balance]));
                }

                if($additional_po_amount_plus_balance < $referrence_amount ){
                    return TransactionValidationMethod::result(400,"Referrence Amount is greater than Total PO Amount, Additional PO Required",array(["referrence_amount"=>$referrence_amount, "total_po_amount"=>$additional_po_amount_plus_balance]));
                }
                
                
                if((strtoupper($referrence_type)=="DR") AND ($referrence_amount == NULL)){
                    $referrence_amount = TransactionValidationMethod::getDRAmount($latest_unit_price,$fields['referrence_group'][0]['referrence_qty']);
                }


                $balance_po_ref_amount =  $additional_po_amount_plus_balance - $referrence_amount;

                $insertRefDetails = TransactionValidationMethod::insertRefDetails($fields,$tag_id);
                $insertPODetails = TransactionValidationMethod::insertPODetails(count($fields['po_group']),$fields,$tag_id);
                $insertPartialTransaction = TransactionValidationMethod::insertPartialTransaction($fields,$transaction_id,$po_total_amount,$po_total_qty,$tag_id,$date_requested,$status,$po_no,$balance_po_ref_amount);
                return TransactionValidationMethod::result(200,"Request Submitted",$insertPartialTransaction);

            }
        }

        // DUPLICATE PO
        if(isset($duplicate_po)){
            return TransactionValidationMethod::result(403,"PO No already exist",$duplicate_po);
        }

        $balance_ref_po_qty = null;
        $balance_po_ref_amount = null;

        if((strtoupper($referrence_type)=="DR") AND ($referrence_amount == NULL)){
            if($po_total_qty < $fields['referrence_group'][0]['referrence_qty'] ){
                return TransactionValidationMethod::result(400,"Referrence Qty is greater than Total PO Qty, Additional PO Required",array(["referrence_qty"=>$fields['referrence_group'][0]['referrence_qty'], "total_po_qty"=>$po_total_qty]));
            }
            
            $balance_ref_po_qty = GenericMethod::getBalanceQtyOfRefPO($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no,$fields['referrence_group'][0]['referrence_qty']);
           
            if(count($balance_ref_po_qty)==0){
                $balance_ref_po_qty = (float)$po_total_qty - (float)$fields['referrence_group'][0]['referrence_qty'];
            }else{
                $balance_ref_po_qty = $po_total_qty;
            }

        }else{
            if($po_total_amount < $referrence_amount ){
                return TransactionValidationMethod::result(400,"Referrence Amount is greater than Total PO Amount, Additional PO Required",array(["referrence_amount"=>$referrence_amount, "total_po_amount"=>$po_total_amount]));
            }

            $balance_ref_po_amount = GenericMethod::getBalanceAmountOfRefPO($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no,$fields['document_amount']);
           
            if(count($balance_ref_po_amount)==0){
                $balance_po_ref_amount = (float)$po_total_amount - (float)$referrence_amount;
            }else{
                $balance_po_ref_amount = $po_total_amount;
            }
        }
       

        $insertRefDetails = TransactionValidationMethod::insertRefDetails($fields,$tag_id);
        $insertPODetails = TransactionValidationMethod::insertPODetails(count($fields['po_group']),$fields,$tag_id);
        $insertPartialTransaction = TransactionValidationMethod::insertPartialTransaction($fields,$transaction_id,$po_total_amount,$po_total_qty,$tag_id,$date_requested,$status,$po_no,$balance_po_ref_amount,$balance_ref_po_qty);
        return TransactionValidationMethod::result(200,"Request Submitted",$insertPartialTransaction);
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
