<?php

namespace App\Methods;

use App\Exceptions\FistoException;
use App\Exceptions\FistoLaravelException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
// For Pagination with Collection
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

use App\Models\User;
use App\Models\VoucherAccountTitle;
use App\Models\POBatch;
use App\Models\TransactionClient;
use App\Models\ReferrenceBatch;
use App\Models\Transaction;
use App\Models\PayrollClient;
use App\Models\RequestorLogs;

use App\Models\UserDocumentCategory;
use Illuminate\Routing\Route;

class GenericMethod{


    ##########################################################################################################
    #########################################      REUSABLE FUNCTION    ######################################
    ##########################################################################################################

        public static function isTransactionExistInFlow($model,$transaction_id,$status){
            if($model::where('transaction_id',$transaction_id)->where('status',$status)->exists()){
                return GenericMethod::resultResponse('exist-flow','received',[]);
            }
        }

        public static function tagTransaction($model,$transaction_id,$remarks,$date_now,$reason_id,$reason_remarks,$status,$distributed_to=[] ){
            $distributed_id =null;
            $distributed_name =null;
            if(!empty($distributed_to)){
                $distributed_id=$distributed_to['id'];
                $distributed_name=$distributed_to['name'];
            }
            $model::Create([
                "transaction_id"=>$transaction_id,
                "description"=>$remarks,
                "status"=>$status,
                "date_status"=>$date_now,
                "reason_id"=>$reason_id,
                "remarks"=>$reason_remarks,
                "distributed_id"=>$distributed_id,
                "distributed_name"=>$distributed_name
            ]);
        }
        
        public static function voucherTransaction(
            $model,$transaction_id,$tag_no,$reason_remarks,$date_now,
            $reason_id,$status,$receipt_type,$percentage_tax,$witholding_tax,$net_amount,
            $voucher_no,$approver,$account_titles ){

            $approver_id =null;
            $approver_name =null;
            if(!empty($approver_id)){
                $approver_id=$approver_id['id'];
                $approver_name=$approver_name['name'];
            }
            


           $voucher_transaction= $model::Create([
                "transaction_id"=>$transaction_id,
                "tag_id"=>$tag_no,
                "receipt_type"=>$receipt_type,
                "percentage_tax"=>$percentage_tax,
                "witholding_tax"=>$witholding_tax,
                "net_amount"=>$net_amount,
                "approver_id"=>$approver_id,
                "approver_name"=>$approver_name,
                "status"=>$status,
                "date_status"=>$date_now,
                "reason_id"=>$reason_id,
                "remarks"=>$reason_remarks,
            ]);
            
            if(isset($account_titles)){
                if(count($account_titles) > 0){
                    $associate_id = $voucher_transaction->id;
                    return GenericMethod::addAccountTitleEntry($associate_id,NULL,$account_titles);
                } 
                
            }


        }

        
        public static function addAccountTitleEntry($associate_id,$treasury_id,$account_titles){
            
            foreach( $account_titles as $specific_account_title){
                $specific_account_title['account_title']['id'];
                $entry = $specific_account_title['entry'];
                $account_title_id = $specific_account_title['account_title']['id'];
                $account_title_name = $specific_account_title['account_title']['name'];
                $amount = $specific_account_title['amount'];
                $remarks = $specific_account_title['remarks'];
                
                VoucherAccountTitle::Create([
                    "associate_id"=>$associate_id
                    ,"treasury_id"=>$treasury_id
                    ,"entry"=>$entry
                    ,"account_title_id"=>$account_title_id
                    ,"account_title_name"=>$account_title_name
                    ,"amount"=>$amount
                    ,"remarks"=>$remarks
                ]);
                
            }
            

            
        }

        public static function approveTransaction(){
            
        }

        
        
        public static function validateWith1PesoDifference($affeced_field,$type,$transaction_amount,$po_total_amount){
            if(
                !(((abs($transaction_amount - $po_total_amount) ) >= 0.00) 
                && 
                ((abs($transaction_amount - $po_total_amount) ) <= 1.00)) 
            ){
                return $errorMessage = GenericMethod::resultLaravelFormat($affeced_field,[$type." amount (".$transaction_amount.") and total PO amount (".$po_total_amount.")  are not equal."]);
            }
        }

        public static function getTransactionChanges($request_id,$transaction,$id){
            $current_transaction = Transaction::with('po_details')->findOrFail($id);
            $original_transaction = Transaction::with('po_details')->findOrFail($id);

            $capex_no = (isset($transaction['document']['capex_no'])?$transaction['document']['capex_no']:NULL);
            $document_no = (isset($transaction['document']['no'])?$transaction['document']['no']:NULL);
            $document_date = (isset($transaction['document']['date'])?$transaction['document']['date']:NULL);
            $category_id = (isset($transaction['document']['category']['id'])?$transaction['document']['category']['id']:NULL);
            $category_name = (isset($transaction['document']['category']['name'])?$transaction['document']['category']['name']:NULL);
            $document_from = (isset($transaction['document']['from'])?$transaction['document']['from']:NULL);
            $document_to = (isset($transaction['document']['to'])?$transaction['document']['to']:NULL);
            $amount = (isset($transaction['document']['amount'])?$transaction['document']['amount']:$transaction['document']['reference']['amount']);
           
// ---------------------------------------------------------------------------------------------------------------------------------
            // Utilities
            $receipt_no = (isset($transaction['document']['utility']['receipt_no'])?$transaction['document']['utility']['receipt_no']:NULL);
            $consumption = (isset($transaction['document']['utility']['consumption'])?$transaction['document']['utility']['consumption']:NULL);
            $location_id = (isset($transaction['document']['utility']['location']['id'])?$transaction['document']['utility']['location']['id']:NULL);
            $location_name = (isset($transaction['document']['utility']['location']['name'])?$transaction['document']['utility']['location']['name']:NULL);
            $utility_category_id = (isset($transaction['document']['utility']['category']['id'])?$transaction['document']['utility']['category']['id']:NULL);
            $utility_category_name = (isset($transaction['document']['utility']['category']['name'])?$transaction['document']['utility']['category']['name']:NULL);
            $account_no_id = (isset($transaction['document']['utility']['account_no']['id'])?$transaction['document']['utility']['account_no']['id']:NULL);
            $account_no = (isset($transaction['document']['utility']['account_no']['no'])?$transaction['document']['utility']['account_no']['no']:NULL);
            
            $current_transaction->utilities_from = $document_from;
            $current_transaction->utilities_to = $document_to;
            $current_transaction->utilities_receipt_no = $receipt_no;
            $current_transaction->utilities_consumption = $consumption;
            $current_transaction->utilities_location_id = $location_id;
            $current_transaction->utilities_location = $location_name;
            $current_transaction->utilities_category_id = $utility_category_id;
            $current_transaction->utilities_category = $utility_category_name;
            $current_transaction->utilities_account_no_id = $account_no_id;
            $current_transaction->utilities_account_no = $account_no;
// ---------------------------------------------------------------------------------------------------------------------------------------
            // Payroll
            $payroll_type = (isset($transaction['document']['payroll']['type'])?$transaction['document']['payroll']['type']:NULL);
            $payroll_category_id = (isset($transaction['document']['payroll']['category']['id'])?$transaction['document']['payroll']['category']['id']:NULL);
            $payroll_category_name = (isset($transaction['document']['payroll']['category']['name'])?$transaction['document']['payroll']['category']['name']:NULL);
            $clients = (isset($transaction['document']['payroll']['clients'])?$transaction['document']['payroll']['clients']:NULL);
           
            $current_transaction->payroll_from = $document_from;
            $current_transaction->payroll_to = $document_to;
            $current_transaction->payroll_type = $payroll_type;
            $current_transaction->payroll_category_id = $payroll_category_id;
            $current_transaction->payroll_category = $payroll_category_name;
            $current_transaction->payroll_client = $clients;

// ---------------------------------------------------------------------------------------------------------------------------------------
            // PCF
            $pcf_name = (isset($transaction['document']['pcf_batch']['name'])?$transaction['document']['pcf_batch']['name']:NULL);
            $pcf_date = (isset($transaction['document']['pcf_batch']['date'])?$transaction['document']['pcf_batch']['date']:NULL);
            $pcf_letter = (isset($transaction['document']['pcf_batch']['letter'])?$transaction['document']['pcf_batch']['letter']:NULL);
           
            $current_transaction->pcf_name = $pcf_name;
            $current_transaction->pcf_date = $pcf_date;
            $current_transaction->pcf_letter = $pcf_letter;
// ---------------------------------------------------------------------------------------------------------------------------------------
  
// ---------------------------------------------------------------------------------------------------------------------------------------
            // Receipt
            $reference_id = (isset($transaction['document']['reference']['id'])?$transaction['document']['reference']['id']:NULL);
            $reference_type = (isset($transaction['document']['reference']['type'])?$transaction['document']['reference']['type']:NULL);
            $reference_no = (isset($transaction['document']['reference']['no'])?$transaction['document']['reference']['no']:NULL);
            $reference_amount = (isset($transaction['document']['reference']['amount'])?$transaction['document']['reference']['amount']:NULL);
           
            $current_transaction->referrence_id = $reference_id;
            $current_transaction->referrence_type = $reference_type;
            $current_transaction->referrence_no = $reference_no;
            $current_transaction->referrence_amount = $reference_amount;
            
// ---------------------------------------------------------------------------------------------------------------------------------------
           
            // Contractor's Billing
            $current_transaction->capex_no = $capex_no;

            $current_transaction->users_id = $transaction['requestor']['id'];
            $current_transaction->id_prefix = $transaction['requestor']['id_prefix'];
            $current_transaction->id_no = $transaction['requestor']['id_no'];
            $current_transaction->first_name = $transaction['requestor']['first_name'];
            $current_transaction->middle_name = $transaction['requestor']['middle_name'];
            $current_transaction->last_name = $transaction['requestor']['last_name'];
            $current_transaction->suffix = $transaction['requestor']['suffix'];
            $current_transaction->department_details = $transaction['requestor']['department'];
            


            $current_transaction->document_no = $document_no;
            $current_transaction->document_date = $document_date;
            $current_transaction->category_id = $category_id;
            $current_transaction->category = $category_name;
            $current_transaction->document_id = $transaction['document']['id'];
            $current_transaction->document_type = $transaction['document']['name'];
            $current_transaction->company_id = $transaction['document']['company']['id'];
            $current_transaction->company = $transaction['document']['company']['name'];
            $current_transaction->department_id = $transaction['document']['department']['id'];
            $current_transaction->department = $transaction['document']['department']['name'];
            $current_transaction->location_id = $transaction['document']['location']['id'];
            $current_transaction->location = $transaction['document']['location']['name'];
            $current_transaction->supplier_id = $transaction['document']['supplier']['id'];
            $current_transaction->supplier = $transaction['document']['supplier']['name'];
            $current_transaction->payment_type = $transaction['document']['payment_type'];
            $current_transaction->document_amount = $amount;
            $current_transaction->remarks = $transaction['document']['remarks'];
            
            if(isset($transaction['po_group'])){
                $original_po_no = $original_transaction->po_details->pluck('po_no'); //628,629
                $input_po_no = GenericMethod::arrayPluck($transaction->po_group,'no'); //628

                $existing=[];
                $additional=[];
                $for_remove=[];
                foreach($original_po_no as $k=>$v){
                    if(in_array($v,$input_po_no)){
                        if($original_transaction->po_details[$k]->id == null){
                             array_push($additional,$v);
                        }

                    //    echo $original_transaction->po_details[$k]->id.'_';
                    //    echo $original_transaction->po_details[$k]->po_no.'_';
                    //    echo $v.'_||||';
                        // array_push($existing,$v);

                    }else{
                        array_push($for_remove,$v);
                    }
                }

            }
            // return $additional;
            
            $newPO = [];
            $modifiedPO = [];
        //     $po_changes = [];
        //     $old = [];
        //     foreach($current_transaction->po_details as $k=>$v){
        //         foreach($transaction['po_group'] as $l=>$w){
        //            if($current_transaction->po_details[$k]->request_id == $transaction['po_group'][$l]['request_id']){
        //                if(
        //                    ($current_transaction->po_details[$k]->po_no != $transaction['po_group'][$l]['no'])||
        //                    ($current_transaction->po_details[$k]->po_amount != $transaction['po_group'][$l]['amount'])||
        //                    ($current_transaction->po_details[$k]->rr_group) != ($transaction['po_group'][$l]['rr_no'])
                       
        //                ){
        //                    $modifiedPO[$k] = [
        //                        "old"=>[
        //                            "po_no"=>$current_transaction->po_details[$k]->po_no,
        //                            "po_amount"=>$current_transaction->po_details[$k]->po_amount,
        //                            "rr_group"=>$current_transaction->po_details[$k]->rr_group
        //                        ]
        //                        ,
        //                        "new"=>[
        //                            "po_no"=>$transaction['po_group'][$l]['no'],
        //                            "po_amount"=>$transaction['po_group'][$l]['amount'],
        //                            "rr_group"=>($transaction['po_group'][$l]['rr_no'])
        //                        ]
        //                    ];
        //                }

        //            }else{
        //                $newPO[$l] = [
        //                    "new"=>[
        //                        "po_no"=>$transaction['po_group'][$l]['no'],
        //                        "po_amount"=>$transaction['po_group'][$l]['amount'],
        //                        "rr_group"=>($transaction['po_group'][$l]['rr_no'])
        //                    ]
        //                ];
        //            }
                   
        //        }
        //    }

            // $newPO = array_values($newPO);
            // $modifiedPO = array_values($modifiedPO);
            
            // $newPO = array_unique($newPO, 'new');
            $po_changes = [
                "modified"=>$modifiedPO
                ,"additional"=>$newPO
                
            ];
            $modifiedFields = array_keys($current_transaction->getDirty());
            $oldTransaction = collect();
            foreach($modifiedFields as $field){
                $oldTransaction->PUT("$field",$original_transaction->$field);
            }

            return $changes = [
                "old"=>
                $oldTransaction
                ,
                "new"=>$current_transaction->getDirty(),    
                "po_details"=>$po_changes
            ];
        }

        public static function arrayPluck($array,$key){
            return array_map(function($object) use($key){
                return $object[$key];
            }, $array);
        }

        public static function insertTransaction($transaction_id,$po_total_amount=0,
        $request_id,$date_requested,$fields,$balance_po_ref_amount=0){
            $status = 'create';
            // return $date_requested;
            if($fields['document']['id'] == 6){
                $new_transaction = Transaction::create([
                    'transaction_id' => $transaction_id
                    , "users_id" => $fields['requestor']['id']
                    , "id_prefix" => $fields['requestor']['id_prefix']
                    , "id_no" => $fields['requestor']['id_no']
                    , "first_name" => $fields['requestor']['first_name']
                    , "middle_name" => $fields['requestor']['middle_name']
                    , "last_name" => $fields['requestor']['last_name']
                    , "suffix" => $fields['requestor']['suffix']
                    , "department_details" => $fields['requestor']['department']
        
                    , "document_id" => $fields['document']['id']
                    , "company_id" => $fields['document']['company']['id']
                    , "company" => $fields['document']['company']['name']
                    , "department_id" => $fields['document']['department']['id']
                    , "department" => $fields['document']['department']['name']
                    , "location_id" => $fields['document']['location']['id']
                    , "location" => $fields['document']['location']['name']
                    , "supplier_id" => $fields['document']['supplier']['id']
                    , "supplier" => $fields['document']['supplier']['name']
                    , "payment_type" => $fields['document']['payment_type']
                    , "document_amount" => $fields['document']['amount']
                    , "remarks" => $fields['document']['remarks']
                    , "document_type" => $fields['document']['name']
        
                    ,"utilities_from" => $fields['document']['from']
                    , "utilities_to" => $fields['document']['to']

                    , "utilities_receipt_no" => $fields['document']['utility']['receipt_no']
                    , "utilities_consumption" => $fields['document']['utility']['consumption']
                    , "utilities_location_id" => $fields['document']['utility']['location']['id']
                    , "utilities_location" => $fields['document']['utility']['location']['name']
                    , "utilities_category_id" => $fields['document']['utility']['category']['id']
                    , "utilities_category" => $fields['document']['utility']['category']['name']
                    , "utilities_account_no_id" => $fields['document']['utility']['account_no']['id']
                    , "utilities_account_no" => $fields['document']['utility']['account_no']['no']

                    , "po_total_amount" => $po_total_amount
        
                    , "request_id" => $request_id
                    , "tagging_tag_id" => 0
                    , "date_requested" => $date_requested
                    , "status" => "Pending"
                ]);
            }else if($fields['document']['id'] == 8){
                $new_transaction = Transaction::create([
                    'transaction_id' => $transaction_id
                    , "users_id" => $fields['requestor']['id']
                    , "id_prefix" => $fields['requestor']['id_prefix']
                    , "id_no" => $fields['requestor']['id_no']
                    , "first_name" => $fields['requestor']['first_name']
                    , "middle_name" => $fields['requestor']['middle_name']
                    , "last_name" => $fields['requestor']['last_name']
                    , "suffix" => $fields['requestor']['suffix']
                    , "department_details" => $fields['requestor']['department']
        
                    , "document_id" => $fields['document']['id']
                    , "company_id" => $fields['document']['company']['id']
                    , "company" => $fields['document']['company']['name']
                    , "department_id" => $fields['document']['department']['id']
                    , "department" => $fields['document']['department']['name']
                    , "location_id" => $fields['document']['location']['id']
                    , "location" => $fields['document']['location']['name']
                    , "supplier_id" => $fields['document']['supplier']['id']
                    , "supplier" => $fields['document']['supplier']['name']
                    , "payment_type" => $fields['document']['payment_type']
                    , "document_date" => $fields['document']['date']
                    , "document_amount" => $fields['document']['amount']
                    , "remarks" => $fields['document']['remarks']
                    , "document_type" => $fields['document']['name']
        
                    , "pcf_name" => $fields['document']['pcf_batch']['name']
                    , "pcf_date" => $fields['document']['pcf_batch']['date']
                    , "pcf_letter" => $fields['document']['pcf_batch']['letter']
                    , "request_id" => $request_id
                    , "tagging_tag_id" => 0
                    , "date_requested" => $date_requested
                ]);
            }else if($fields['document']['id'] == 7){
                $new_transaction = Transaction::create([
                    'transaction_id' => $transaction_id
                    , "users_id" => $fields['requestor']['id']
                    , "id_prefix" => $fields['requestor']['id_prefix']
                    , "id_no" => $fields['requestor']['id_no']
                    , "first_name" => $fields['requestor']['first_name']
                    , "middle_name" => $fields['requestor']['middle_name']
                    , "last_name" => $fields['requestor']['last_name']
                    , "suffix" => $fields['requestor']['suffix']
                    , "department_details" => $fields['requestor']['department']
        
                    , "document_id" => $fields['document']['id']
                    , "company_id" => $fields['document']['company']['id']
                    , "company" => $fields['document']['company']['name']
                    , "department_id" => $fields['document']['department']['id']
                    , "department" => $fields['document']['department']['name']
                    , "location_id" => $fields['document']['location']['id']
                    , "location" => $fields['document']['location']['name']
                    , "supplier_id" => $fields['document']['supplier']['id']
                    , "supplier" => $fields['document']['supplier']['name']
                    , "payment_type" => $fields['document']['payment_type']
                    , "document_amount" => $fields['document']['amount']
                    , "remarks" => $fields['document']['remarks']
                    , "document_type" => $fields['document']['name']
        
                    , "payroll_from" => $fields['document']['from']
                    , "payroll_to" => $fields['document']['to']
                    , "payroll_category_id" => $fields['document']['payroll']['category']['id']
                    , "payroll_category" => $fields['document']['payroll']['category']['name']
                    , "payroll_type" => $fields['document']['payroll']['type']
                    , "payroll_client" => $fields['document']['payroll']['clients']
                    , "request_id" => $request_id
                    , "tagging_tag_id" => 0
                    , "date_requested" => $date_requested
                ]);
            }else if($fields['document']['id'] == 4){
                $new_transaction = Transaction::create([
                    'transaction_id' => $transaction_id
                    , "users_id" => $fields['requestor']['id']
                    , "id_prefix" => $fields['requestor']['id_prefix']
                    , "id_no" => $fields['requestor']['id_no']
                    , "first_name" => $fields['requestor']['first_name']
                    , "middle_name" => $fields['requestor']['middle_name']
                    , "last_name" => $fields['requestor']['last_name']
                    , "suffix" => $fields['requestor']['suffix']
                    , "department_details" => $fields['requestor']['department']
        
                    , "document_id" => $fields['document']['id']
                    , "category_id" => $fields['document']['category']['id']
                    , "category" => $fields['document']['category']['name']
                    , "company_id" => $fields['document']['company']['id']
                    , "company" => $fields['document']['company']['name']
                    , "department_id" => $fields['document']['department']['id']
                    , "department" => $fields['document']['department']['name']
                    , "location_id" => $fields['document']['location']['id']
                    , "location" => $fields['document']['location']['name']
                    , "supplier_id" => $fields['document']['supplier']['id']
                    , "supplier" => $fields['document']['supplier']['name']
                    , "payment_type" => $fields['document']['payment_type']
                    , "document_date" => $fields['document']['date']
                    , "remarks" => $fields['document']['remarks']
                    , "document_type" => $fields['document']['name']
        
                    , "po_total_amount" => $po_total_amount    
                    , "balance_po_ref_amount" => $balance_po_ref_amount   
                    
                    , "referrence_type" => $fields['document']['reference']['type']
                    , "referrence_no" => $fields['document']['reference']['no']
                    , "referrence_amount" => $fields['document']['reference']['amount']
                    , "referrence_id" => $fields['document']['reference']['id'] 

                    , "request_id" => $request_id
                    , "tagging_tag_id" => 0
                    , "date_requested" => $date_requested
                    , "status" => "Pending"

                    
                ]);
            }else if($fields['document']['id'] == 5){
                
                $new_transaction = Transaction::create([
                    'transaction_id' => $transaction_id
                    , "users_id" => $fields['requestor']['id']
                    , "id_prefix" => $fields['requestor']['id_prefix']
                    , "id_no" => $fields['requestor']['id_no']
                    , "first_name" => $fields['requestor']['first_name']
                    , "middle_name" => $fields['requestor']['middle_name']
                    , "last_name" => $fields['requestor']['last_name']
                    , "suffix" => $fields['requestor']['suffix']
                    , "department_details" => $fields['requestor']['department']
        
                    , "document_id" => $fields['document']['id']
                    , "capex_no" => $fields['document']['capex_no']
                    , "category_id" => $fields['document']['category']['id']
                    , "category" => $fields['document']['category']['name']
                    , "company_id" => $fields['document']['company']['id']
                    , "company" => $fields['document']['company']['name']
                    , "department_id" => $fields['document']['department']['id']
                    , "department" => $fields['document']['department']['name']
                    , "location_id" => $fields['document']['location']['id']
                    , "location" => $fields['document']['location']['name']
                    , "supplier_id" => $fields['document']['supplier']['id']
                    , "supplier" => $fields['document']['supplier']['name']
                    , "payment_type" => $fields['document']['payment_type']
                    , "document_no" => $fields['document']['no']
                    , "document_date" => $fields['document']['date']
                    , "document_amount" => $fields['document']['amount']
                    , "remarks" => $fields['document']['remarks']
                    , "document_type" => $fields['document']['name']
        
                    , "po_total_amount" => $po_total_amount
        
                    , "request_id" => $request_id
                    , "tagging_tag_id" => 0
                    , "date_requested" => $date_requested
                    , "status" => "Pending"
                ]);
            }
            else{
                $new_transaction = Transaction::create([
                    'transaction_id' => $transaction_id
                    , "users_id" => $fields['requestor']['id']
                    , "id_prefix" => $fields['requestor']['id_prefix']
                    , "id_no" => $fields['requestor']['id_no']
                    , "first_name" => $fields['requestor']['first_name']
                    , "middle_name" => $fields['requestor']['middle_name']
                    , "last_name" => $fields['requestor']['last_name']
                    , "suffix" => $fields['requestor']['suffix']
                    , "department_details" => $fields['requestor']['department']
        
                    , "document_id" => $fields['document']['id']
                    , "category_id" => $fields['document']['category']['id']
                    , "category" => $fields['document']['category']['name']
                    , "company_id" => $fields['document']['company']['id']
                    , "company" => $fields['document']['company']['name']
                    , "department_id" => $fields['document']['department']['id']
                    , "department" => $fields['document']['department']['name']
                    , "location_id" => $fields['document']['location']['id']
                    , "location" => $fields['document']['location']['name']
                    , "supplier_id" => $fields['document']['supplier']['id']
                    , "supplier" => $fields['document']['supplier']['name']
                    , "payment_type" => $fields['document']['payment_type']
                    , "document_no" => $fields['document']['no']
                    , "document_date" => $fields['document']['date']
                    , "document_amount" => $fields['document']['amount']
                    , "remarks" => $fields['document']['remarks']
                    , "document_type" => $fields['document']['name']
        
                    , "po_total_amount" => $po_total_amount
        
                    , "request_id" => $request_id
                    , "tagging_tag_id" => 0
                    , "date_requested" => $date_requested
                    , "status" => "Pending"
                ]);
            }

            GenericMethod::insertRequestorLogs($new_transaction->id,$transaction_id,$date_requested,$fields['document']['remarks'],$fields['requestor']['id'],$status,NULL,NULL,NULL);

            return $new_transaction;
            // if($new_transaction->count()>1){
            //     return GenericMethod::resultLaravelFormat('po_group.no',"The PO number has already been taken.: ".$validateTransactionCount->pluck('po_no')->implode(','));
            // }


        }

        public static function updateTransaction($transaction_id,$po_total_amount=0,
        $request_id,$date_requested,$fields,$balance_po_ref_amount=0,$changes){

            $currentTransaction = Transaction::with('po_details')->where('id',$transaction_id)->first();
            $currentTransaction->isClean();
            $status = 'update';

            $capex_no = (isset($fields['document']['capex_no'])?$fields['document']['capex_no']:NULL);
            $document_no = (isset($fields['document']['no'])?$fields['document']['no']:NULL);
            $document_date = (isset($fields['document']['date'])?$fields['document']['date']:NULL);
            $category_id = (isset($fields['document']['category']['id'])?$fields['document']['category']['id']:NULL);
            $category_name = (isset($fields['document']['category']['name'])?$fields['document']['category']['name']:NULL);
            $document_from = (isset($fields['document']['from'])?$fields['document']['from']:NULL);
            $document_to = (isset($fields['document']['to'])?$fields['document']['to']:NULL);
            $amount = (isset($fields['document']['amount'])?$fields['document']['amount']:$fields['document']['reference']['amount']);
           
            // Utilities
            $receipt_no = (isset($fields['document']['utility']['receipt_no'])?$fields['document']['utility']['receipt_no']:NULL);
            $consumption = (isset($fields['document']['utility']['consumption'])?$fields['document']['utility']['consumption']:NULL);
            $location_id = (isset($fields['document']['utility']['location']['id'])?$fields['document']['utility']['location']['id']:NULL);
            $location_name = (isset($fields['document']['utility']['location']['name'])?$fields['document']['utility']['location']['name']:NULL);
            $utility_category_id = (isset($fields['document']['utility']['category']['id'])?$fields['document']['utility']['category']['id']:NULL);
            $utility_category_name = (isset($fields['document']['utility']['category']['name'])?$fields['document']['utility']['category']['name']:NULL);
            $account_no_id = (isset($fields['document']['utility']['account_no']['id'])?$fields['document']['utility']['account_no']['id']:NULL);
            $account_no = (isset($fields['document']['utility']['account_no']['no'])?$fields['document']['utility']['account_no']['no']:NULL);

            // Payroll
            $payroll_type = (isset($fields['document']['payroll']['type'])?$fields['document']['payroll']['type']:NULL);
            $payroll_category_id = (isset($fields['document']['payroll']['category']['id'])?$fields['document']['payroll']['category']['id']:NULL);
            $payroll_category_name = (isset($fields['document']['payroll']['category']['name'])?$fields['document']['payroll']['category']['name']:NULL);
            $clients = (isset($fields['document']['payroll']['clients'])?$fields['document']['payroll']['clients']:NULL);
            
            // PCF
            $pcf_name = (isset($fields['document']['pcf_batch']['name'])?$fields['document']['pcf_batch']['name']:NULL);
            $pcf_date = (isset($fields['document']['pcf_batch']['date'])?$fields['document']['pcf_batch']['date']:NULL);
            $pcf_letter = (isset($fields['document']['pcf_batch']['letter'])?$fields['document']['pcf_batch']['letter']:NULL);

            // Receipt
            $reference_id = (isset($fields['document']['reference']['id'])?$fields['document']['reference']['id']:NULL);
            $reference_type = (isset($fields['document']['reference']['type'])?$fields['document']['reference']['type']:NULL);
            $reference_no = (isset($fields['document']['reference']['no'])?$fields['document']['reference']['no']:NULL);
            $reference_amount = (isset($fields['document']['reference']['amount'])?$fields['document']['reference']['amount']:NULL);
            $balance_po_ref_amount = (isset($balance_po_ref_amount)?$balance_po_ref_amount:NULL);

            $currentTransaction->transaction_id = $fields['transaction']['no'];
            $currentTransaction->users_id = $fields['requestor']['id'];
            $currentTransaction->id_prefix = $fields['requestor']['id_prefix'];
            $currentTransaction->id_no = $fields['requestor']['id_no'];
            $currentTransaction->first_name = $fields['requestor']['first_name'];
            $currentTransaction->middle_name = $fields['requestor']['middle_name'];
            $currentTransaction->last_name = $fields['requestor']['last_name'];
            $currentTransaction->suffix = $fields['requestor']['suffix'];
            $currentTransaction->department_details = $fields['requestor']['department'];

            $currentTransaction->document_no = $document_no;
            $currentTransaction->document_date = $document_date;
            $currentTransaction->category_id = $category_id;
            $currentTransaction->category = $category_name;
            $currentTransaction->document_id = $fields['document']['id'];
            $currentTransaction->document_type = $fields['document']['name'];
            $currentTransaction->company_id = $fields['document']['company']['id'];
            $currentTransaction->company = $fields['document']['company']['name'];
            $currentTransaction->department_id = $fields['document']['department']['id'];
            $currentTransaction->department = $fields['document']['department']['name'];
            $currentTransaction->location_id = $fields['document']['location']['id'];
            $currentTransaction->location = $fields['document']['location']['name'];
            $currentTransaction->supplier_id = $fields['document']['supplier']['id'];
            $currentTransaction->supplier = $fields['document']['supplier']['name'];
            $currentTransaction->payment_type = $fields['document']['payment_type'];
            $currentTransaction->document_amount = $amount;
            $currentTransaction->remarks = $fields['document']['remarks'];
            $currentTransaction->po_total_amount= $po_total_amount;
            $currentTransaction->request_id= $request_id;
            $currentTransaction->status= "Pending";
            $currentTransaction->state= "pending";
            $currentTransaction->reason_id= NULL;
            $currentTransaction->reason= NULL;
            $currentTransaction->reason_remarks= NULL;

            // Contractor's Billing
            $currentTransaction->capex_no = $capex_no;

            // Utility
            $currentTransaction->utilities_from = $document_from;
            $currentTransaction->utilities_to = $document_to;
            $currentTransaction->utilities_receipt_no = $receipt_no;
            $currentTransaction->utilities_consumption = $consumption;
            $currentTransaction->utilities_location_id = $location_id;
            $currentTransaction->utilities_location = $location_name;
            $currentTransaction->utilities_category_id = $utility_category_id;
            $currentTransaction->utilities_category = $utility_category_name;
            $currentTransaction->utilities_account_no_id = $account_no_id;
            $currentTransaction->utilities_account_no = $account_no;
            
            // Payroll
            $currentTransaction->payroll_from = $document_from;
            $currentTransaction->payroll_to = $document_to;
            $currentTransaction->payroll_type = $payroll_type;
            $currentTransaction->payroll_category_id = $payroll_category_id;
            $currentTransaction->payroll_category = $payroll_category_name;
            $currentTransaction->payroll_client = $clients;

            // PCF
            $currentTransaction->pcf_name = $pcf_name;
            $currentTransaction->pcf_date = $pcf_date;
            $currentTransaction->pcf_letter = $pcf_letter;

            // Receipt
            $currentTransaction->referrence_id = $reference_id;
            $currentTransaction->referrence_type = $reference_type;
            $currentTransaction->referrence_no = $reference_no;
            $currentTransaction->referrence_amount = $reference_amount;
            $currentTransaction->balance_po_ref_amount = $balance_po_ref_amount;
           

        // if(count($currentTransaction->getDirty()) == 0){
        //     return "Nothing Has Changed";
        //   }else{
                $currentTransaction->save();
                GenericMethod::insertRequestorLogs($transaction_id,$fields['transaction']['no'],$date_requested,$fields['document']['remarks'],$fields['requestor']['id'],$status,NULL,NULL,NULL,$changes);
                return $currentTransaction;
        //   }
        }
        
        public function validateIfNothingChangeThenSave($model,$modelName,$is_tagged_array_modified=0){
            // return $model->isClean().'&&'.$is_tagged_array_modified;
            if($model->isClean() && $is_tagged_array_modified == 0){
              return GenericMethod::resultResponse('nothing-has-changed',$modelName,[]);
            }else{
                $model->save();
                return GenericMethod::resultResponse('update',$modelName,[]);
            }
          }

        public static function insertRequestorLogs($transaction_id,$transaction_no,$date_requested,$remarks,$user_id,$status,$reason_id,$reason_description,$reason_remarks,$changes=[]){
            RequestorLogs::create([
                "transaction_id"=>$transaction_id
                ,"transaction_no"=>$transaction_no
                ,"description"=>$remarks
                ,"status"=>$status
                ,"date_status"=>$date_requested
                ,"user_id"=>$user_id
                ,"reason_id"=>$reason_id
                ,"reason_description"=>$reason_description
                ,"reason_remarks"=>$reason_remarks
                ,"changes"=>$changes
            ]);

        }

        public static function insertClient($request_id,$clients){
            $client_count = count($clients);
            for($i=0;$i<$client_count;$i++){
                $id = $clients[$i]['id'];
                $name = $clients[$i]['name'];
                $insert_po_batch = TransactionClient::create([
                    'request_id' => $request_id,
                    'client_id' => $id,
                    'client_name' => $name
                ]);
            }
        }

        
        public static function insertPO($request_id,$po_group,$po_total_amount,$payment_type){
            $po_count = count($po_group);
            for($i=0;$i<$po_count;$i++){
                
                $is_add= NULL;
                $is_editable= 1;
                $previous_balance= $po_group[$i]['amount'];
                if($payment_type === 'PARTIAL'){
                    $is_add = $po_group[$i]['is_add'];
                    $is_editable = $po_group[$i]['is_editable'];
                    $previous_balance = $po_group[$i]['previous_balance'];
                }

                $po_no = $po_group[$i]['no'];
                $po_amount = $po_group[$i]['amount'];
                $rr_group = $po_group[$i]['rr_no'];
               
                $insert_po_batch = POBatch::create([
                    'request_id' => $request_id,
                    'is_add' => $is_add,
                    'is_editable' => $is_editable,
                    'previous_balance' => $previous_balance,
                    'po_no' => $po_no,
                    'po_amount' => $po_amount,
                    'rr_group' => $rr_group,
                    'po_total_amount' => $po_total_amount
                ]);
            }
        }

        public static function updatePO($request_id,$po_group,$po_total_amount,$payment_type,$id){
            $po_count = count($po_group);
            
            POBatch::where('request_id',$request_id)->delete();
            for($i=0;$i<$po_count;$i++){
                $is_add= NULL;
                $is_editable= 1;
                $previous_balance= $po_group[$i]['amount'];
                if($payment_type === 'PARTIAL'){
                    $is_add = $po_group[$i]['is_add'];
                    $is_editable = $po_group[$i]['is_editable'];
                    $previous_balance = $po_group[$i]['previous_balance'];
                }

                $po_no = $po_group[$i]['no'];
                $po_amount = $po_group[$i]['amount'];
                $rr_group = $po_group[$i]['rr_no'];
               
                $insert_po_batch = POBatch::create([
                    'request_id' => $request_id,
                    'is_add' => $is_add,
                    'is_editable' => $is_editable,
                    'previous_balance' => $previous_balance,
                    'po_no' => $po_no,
                    'po_amount' => $po_amount,
                    'rr_group' => $rr_group,
                    'po_total_amount' => $po_total_amount
                ]);
            }
        }

        public static function updateClients($request_id,$client_groups,$id){
            $client_group_count = count($client_groups);
            TransactionClient::where('request_id',$request_id)->delete();
            for($i=0;$i<$client_group_count;$i++){
                $insert_po_batch = TransactionClient::create([
                    'request_id' => $request_id,
                    'client_id' => $client_groups[$i]['id'],
                    'client_name' => $client_groups[$i]['name'],
                ]);
            }
        }

        public static function insertRef($request_id,$reference)
        {
                $insert_reference_batch = ReferrenceBatch::create([
                    'request_id' => $request_id,
                    'referrence_type' => $reference['document']['reference']['type'],
                    'referrence_no' => $reference['document']['no'],
                    'referrence_amount' => $reference['document']['amount']
                ]);

        }

        public static function paginateme($items, $perPage, $page = null, $options = [])
        {
            if(!isset($perPage)){
                $perPage = 10;
            }
            $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
            $items = $items instanceof Collection ? $items : Collection::make($items);
            return new LengthAwarePaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page, $options);
        }

        public static function updateTransactionStatus($transaction_id,$tag_no,$status,$state,$reason_id,$reason,$reason_remarks)
        {
            // return Transaction::where('tag_no',$tag_no)->get();
            // if($status != 'tag-tag'){
            //     $tag_no = 0;
            // }else if (Transaction::where('tag_no',$tag_no)->get()){

            // }
            DB::table('transactions')
                ->where('transaction_id', $transaction_id)
                ->update([
                    'status' => $status
                    ,'state' => $state
                    ,'tag_no' => $tag_no
                    ,'reason_id' => $reason_id
                    ,'reason' => $reason
                    ,'reason_remarks' => $reason_remarks
                ]);
        }

        public static function addAND($array)
        {
            $last  = array_slice($array, -1);
            $first = join(', ', array_slice($array, 0, -1));
            $both  = array_filter(array_merge(array($first), $last), 'strlen');
            return join(' and ', $both);
        }

    ##########################################################################################################
    #########################################      RETRIEVE             ######################################
    ##########################################################################################################
    
        public static function getFullname($fname,$mname,$lname,$suffix){
            $fullname = $fname.' '.strtoupper($mname[0]).'. '.$lname.' '.$suffix;
            return $fullname;
        }

        public static function setGroup($group,$field1,$field2){


            $list = collect();
            $total = 0;
            $group_details = collect();
            foreach($group as $specific_group){
                $list->push($specific_group->$field1);
                $total = $total+$specific_group->$field2;

            }

            $group_details->push([
                "".$field1."_list" => $list,
                "total_".$field2."" =>$total
            ]);

            return $group_details;

        }

        public static function generateTagNo(){
            return Transaction::max('tag_no')+1;
        }

        public static function countTableById($table,$id){
            $table = DB::table($table)->where('id', $id);
            return $table->count();
        }

        public static function unique_values_in_array_based_on_key($array, $key) {
            $temp_array = array();
            $i = 0;
            $key_array = array();

            foreach($array as $val) {
                if (!in_array($val->$key, $key_array)) {
                    $key_array[$i] = $val->$key;
                    $temp_array[$i] = $val;
                }
                $i++;
            }
            return array_values($temp_array);
        }

        public static function where($collection,$field,$id,$desired_field){
            $new_request = collect();
            foreach($collection as $specific_collection){

                if($specific_collection[''.$field.''] == $id){
                $new_request->push([
                    "id" => $id,
                    "categories" =>  $specific_collection[''.$desired_field.'']
                ]);
                }
            }

            return $new_request;

        }

        public static function getBalanceAmountOfRefPO($payment_type,$company_id,$supplier_id,$po_no,$document_amount){
            $transactions = DB::table('transactions')
            ->leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
            ->where('transactions.company_id',$company_id)
            ->where('transactions.supplier_id',$supplier_id)
            ->where('p_o_batches.po_no',$po_no)
            ->orderBy('transactions.id','desc')
            ->take(1)
            ->get('transactions.balance_po_ref_amount');

            return $transactions;

        }

        public static function getBalanceQtyOfRefPO($payment_type,$company_id,$supplier_id,$po_no,$ref_qty){
            $transactions = DB::table('transactions')
            ->leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
            ->where('transactions.company_id',$company_id)
            ->where('transactions.supplier_id',$supplier_id)
            ->where('p_o_batches.po_no',$po_no)
            ->orderBy('transactions.id','desc')
            ->take(1)
            ->get('transactions.balance_po_ref_qty');

            return $transactions;

        }

        public static function getUsedPO($payment_type,$company_id,$supplier_id,$po_no,$document_amount){
            $transactions = DB::table('transactions')
            ->leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
            ->where('transactions.payment_type',$payment_type)
            ->where('transactions.company_id',$company_id)
            ->where('transactions.supplier_id',$supplier_id)
            ->where('p_o_batches.po_no',$po_no)
            ->where('transactions.balance_document_po_amount','<',$document_amount);
            return $transactions->count();
        }

        public static function getPOWithInsufficientAmont($payment_type,$company_id,$supplier_id,$po_no,$document_amount){
            $transactions = DB::table('transactions')
            ->leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
            ->where('payment_type',$payment_type)
            ->where('company_id',$company_id)
            ->where('supplier_id',$supplier_id)
            ->where('po_no',$po_no)
            ->where('balance_document_po_amount','<',$document_amount)->get();
            return $transactions;
        }

        public static function getTagIDUsingPONo($payment_type,$company_id,$supplier_id,$po_no){
            $transactions = DB::table('transactions')
            ->select('transactions.request_id')
            ->leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
            ->where('transactions.company_id',$company_id)
            ->where('transactions.supplier_id',$supplier_id)
            ->where('p_o_batches.po_no',$po_no)
            ->where(function($query){
                $query
                ->whereNotNull('transactions.balance_po_ref_amount')
                ->orWhere('transactions.balance_po_ref_amount','>',0);
            })
            ->orderBy('transactions.request_id','desc')
            ->get();
            return $transactions;
        }

        public static function getUsedPOFromDB($payment_type,$company_id,$supplier_id,$po_no,$document_amount){
            $transactions = DB::table('p_o_batches')
            ->where('request_id', '=', function ($query) use ($po_no) {
                $query->selectRaw('request_id')->from('p_o_batches')
                ->where('po_no',$po_no)
                ->orderByDesc('id')
                ->limit(1);
            })
            ->get('po_no');

            return $transactions;
        }

        public static function getUserDetailsById($id)
        {
            $user_details = User::find($id);

            $document_details = DB::select( DB::raw('SELECT documents.id AS "masterlist_id",
            documents.document_type AS "document_name",
            IFNULL(categories.id,"no category")  AS "masterlist_category_id",
            categories.name AS "category_name",
            user_document_category.user_id AS "user_id" ,
            user_document_category.id AS "user_id",
            user_document_category.category_id AS "user_category_id"
            FROM documents
            LEFT JOIN document_categories
            ON documents.id = document_categories.id
            LEFT JOIN categories
            ON document_categories.category_id = categories.id
            LEFT JOIN user_document_category
            ON document_categories.id = user_document_category.id AND document_categories.category_id = user_document_category.category_id
            LEFT JOIN users
            ON user_document_category.user_id = users.id
            ORDER by documents.id,categories.id') );

            $user_document_details = collect();
            $document_types = collect();
            $categories = collect();
            $categories_per_doc = array();

            foreach($document_details as $specific_document_details){


                if(($specific_document_details->masterlist_id == $specific_document_details->user_id) AND
                    ($specific_document_details->masterlist_category_id == $specific_document_details->user_category_id)){
                        $document_status = true;
                        $category_status = true;

                }else if(($specific_document_details->masterlist_id == $specific_document_details->user_id) AND
                ($specific_document_details->masterlist_category_id != $specific_document_details->user_category_id)){
                    $document_status = true;
                    $category_status = false;

                }else if(($specific_document_details->masterlist_id != $specific_document_details->user_id) AND
                ($specific_document_details->masterlist_category_id != $specific_document_details->user_category_id)){
                    $document_status = true;
                    $category_status = false;

                }else{
                    $document_status = false;
                }

                $categories->push([
                    "user_id"=>$specific_document_details->user_id,
                    "user_category_id"=>$specific_document_details->user_category_id,
                    "id"=>$specific_document_details->masterlist_id,
                    "category_id"=>$specific_document_details->masterlist_category_id,
                    "category_name"=>$specific_document_details->category_name,
                    "category_status"=>$category_status,
                ]);

            }



            $final_document_details = GenericMethod::unique_values_in_array_based_on_key($document_details,'masterlist_id');

            foreach($final_document_details as $final_specific_document_details){

                $ids[]= $final_specific_document_details->masterlist_id;
            }

            $get_id_with_no_categories = DB::table('user_document_category')->where('category_id',0)
            ->get();

            // return $get_id_with_no_categories;

            foreach($final_document_details as $final_specific_document_details){

                if($final_specific_document_details->masterlist_id == $final_specific_document_details->user_id){

                    $document_status = true;
                }else{

                    $document_status = false;
                }

                    foreach($categories as $specific_categories){

                        if($specific_categories['id'] == $final_specific_document_details->masterlist_id){
                            array_push($categories_per_doc, array(
                                "category_id"=>$specific_categories['category_id'],
                                "category_name"=>$specific_categories['category_name'],
                                "category_status"=>$specific_categories['category_status']),
                            );
                        }else{
                        }
                    }
                $document_types->push([
                    "id"=>$final_specific_document_details->masterlist_id,
                    "document_name"=>$final_specific_document_details->document_name,
                    "document_status"=>$document_status,
                    "document_categories"=>$categories_per_doc

                ]);

                $categories_per_doc = array();

            }

            $user_document_details->push([
                "id"=> $user_details->id,
                "id_prefix"=> $user_details->id_prefix,
                "id_no"=> $user_details->id_prefix,
                "role"=> $user_details->role,
                "first_name"=> $user_details->first_name,
                "middle_name"=> $user_details->middle_name,
                "last_name"=> $user_details->last_name,
                "suffix"=> $user_details->suffix,
                "department"=> $user_details->department,
                "position"=> $user_details->position,
                "permissions"=> $user_details->permissions,
                "document_types"=> $document_types,
                "username"=> $user_details->username
            ]);

            $result = $user_document_details;


            if (!$result) {
                return [
                    'error_message' => 'Data Not Found',
                ];
            }

            return $result;
        }

        public static function getCategoriesByUserAndDocID($user_id,$id)
        {
            $get_category_ids = DB::select
            ( DB::raw("SELECT user_id,document_id,category_id
            FROM `user_document_category`
            WHERE `user_id` = '$user_id' AND `document_id` = '$id'
            ORDER BY `id` ASC") );

            return $get_category_ids;
        }

        public static function getTransactionFormat($transaction, $table){

            $result = collect();

                if($table == 'taggings'){
                    $remarks = DB::table($table)
                    ->select('remarks')
                    ->where('transaction_id',$transaction->transaction_id)->orderBy('id', 'desc')->first();

                }else{

                    $remarks = DB::table($table)
                    ->select('remarks')
                    ->where('request_id',$transaction->tagging_request_id)->orderBy('id', 'desc')->first();

                }


                if(!isset($transaction->created_at)){
                    $date_requested = DB::table('transactions')->where('transaction_id',$transaction[0]['transaction_id'])->select('created_at')->latest();
                    // $date_requested = date('Y-m-d',strtotime($date_requested->created_at));
                }else{

                    $date_requested = date('Y-m-d',strtotime($transaction->created_at));
                }


                // PO & RR
                $po_group = collect();
                $get_po = DB::table('p_o_batches as PB')
                ->where('PB.request_id',$transaction->request_id)
                ->get();

                foreach($get_po as $specific_po){
                    $id = $specific_po->id;

                    $rr_group = collect();
                    $get_rr = DB::table('r_r_batches as RB')
                    ->where('RB.po_batch_no',$id)
                    ->get();

                    foreach($get_rr as $specific_rr){
                        $rr_group->push([
                            "rr_no"=>$specific_rr->rr_code
                            ,"rr_qty"=>$specific_rr->rr_qty

                        ]);
                    }
                    $po_group->push([
                        "po_no"=>$specific_po->po_no,
                        "rr_group"=>$rr_group,
                        "po_amount"=>$specific_po->po_amount,
                        "po_qty"=>$specific_po->po_qty,
                    ]);

                }
            // REFERRENCE
            $referrence_group = collect();
            $get_referrence = DB::table('referrence_batches')
            ->where('request_id','=',$transaction->request_id)
            ->get();

            foreach($get_referrence as $specific_refference){
                $referrence_group->push([
                    "referrence_type"=>$specific_refference->referrence_type,
                    "referrence_no"=>$specific_refference->referrence_no,
                    "referrence_amount"=>$specific_refference->referrence_amount,
                    "referrence_qty"=>$specific_refference->referrence_qty
                ]);
            }

            //    DOCUMENT CATEGORY



            //    return $transaction;
                $result->push(
                    [
                        'users_id'=>$transaction->users_id,
                        'id_prefix'=>$transaction->id_prefix,
                        'first_name'=>$transaction->first_name,
                        'middle_name'=>$transaction->middle_name,
                        'last_name'=>$transaction->last_name,
                        'suffix'=>$transaction->suffix,
                        'id_no'=>$transaction->id_no,
                        'department'=>$transaction->department,
                        'document_date'=>$transaction->document_date,
                        'reason_id'=>$transaction->reason_id,
                        'reason'=>$transaction->reason,
                        'utilities_from'=>$transaction->utilities_from,
                        'utilities_to'=>$transaction->utilities_to,
                        'created_at'=>$transaction->created_at,
                        'updated_at'=>$transaction->updated_at,
                        'id'=>$transaction->id,
                        'date_requested'=>$date_requested,
                        'transaction_id'=>$transaction->transaction_id,
                        'request_id'=>$transaction->request_id,
                        'tagging_request_id'=>$transaction->tagging_request_id,
                        'document_id'=>$transaction->document_id,
                        'document_type'=>$transaction->document_type,
                        'category_id'=>$transaction->category_id,
                        'category'=>$transaction->category,
                        'document_no'=>$transaction->document_no,
                        'document_amount'=>$transaction->document_amount,
                        'company_id'=>$transaction->company_id,
                        'company'=>$transaction->company,
                        'supplier_id'=>$transaction->supplier_id,
                        'supplier'=>$transaction->supplier,
                        'po_group'=>$po_group,
                        'po_total_amount'=>$transaction->po_total_amount,
                        'po_total_qty'=>$transaction->po_total_qty,
                        'rr_total_qty'=>$transaction->rr_total_qty,
                        "referrence_group"=>$referrence_group,
                        'referrence_total_amount'=>$transaction->referrence_total_amount,
                        'referrence_total_qty'=>$transaction->referrence_total_qty,
                        'payment_type'=>$transaction->payment_type,
                        'status'=>$transaction->status,
                        'remarks'=>$remarks,
                        'status_group_id'=>null,
                        'pcf_date'=>$transaction->pcf_date,
                        'pcf_letter'=>$transaction->pcf_letter,
                        'date_from'=>$transaction->utilities_from,
                        'date_to'=>$transaction->utilities_to,
                        'balance_document_po_amount'=>$transaction->balance_document_po_amount,
                        'balance_document_ref_amount'=>$transaction->balance_document_ref_amount,
                        'balance_po_ref_amount'=>$transaction->balance_po_ref_amount
                    ]);

                    
                    $resultTransaction =$result->sortDesc();
                    // $resultTransaction = $resultTransaction->values();
                    // return GenericMethod::paginateme($resultTransaction);
                    return $result->paginateme(5);
                    return $resultTransaction;
                }

        public static function getPOTotalAmount($request_id,$po_group){
            $po_count = count($po_group);
            $po_total_amount = 0;
            for($i=0;$i<$po_count;$i++){
                $po_amount = $po_group[$i]['amount'];
                $po_total_amount = $po_total_amount + $po_amount;
            }
            return $po_total_amount;
        }

        public static function getBalance($po_total_amount,$balance,$reference_amount){
            $balance = ($po_total_amount+$balance)-$reference_amount;
            return $balance;
        }
        
        public static function getRequestID(){
            $transactions = DB::table('transactions')->select('request_id')->orderBy('id', 'desc')->first();
            return (empty($transactions))?0:$transactions->request_id+1;
        }
        
        public static function getTransactionNo($str)
        {
            $dep_initials = '';
            foreach (explode(' ', $str) as $word) {
                $dep_initials .= strtoupper($word[0]);
            }

            $transactions = DB::table('transactions')->where('transaction_id', 'like', '%' . $dep_initials . '%')
                ->select('transaction_id')->orderBy('id', 'DESC')->first();
            if (empty($transactions)) {
                $transaction_id = 0;
            } else {
                $transaction_id = preg_replace('/[^0-9.]+/', '', ($transactions->transaction_id));

            }
            return ($transaction_id);
        }

        public static function getTransactionCode($str, $transaction_id)
        {
            $dep_initials = '';
            $transaction_no = '';
            if ($str == trim($str) && strpos($str, ' ') !== false) {
                // IF MORE THAN 1 WORD AND DEPARTMENT NAME (MANAGEMENT INFORMATION SYSTEMS)
                foreach (explode(' ', $str) as $word) {
                    $dep_initials .= strtoupper($word[0]);
                }

                return $dep_initials . sprintf('%03d', ($transaction_id + 1));
            } else {
                // IF 1 WORD AND DEPARTMENT NAME (FINANCE)
                $dep_initials = strtoupper(mb_substr($str, 0, 3));

                $transactions = DB::table('transactions')->where('transaction_id', 'like', '%' . $dep_initials . '%')
                    ->select('transaction_id')->orderBy('id', 'desc')->first();

                if (empty($transactions)) {
                    // IF WALANG LAMAN ANG KEYWORD DITO IREREGISTER ANG KEYWORD (FIN,MIS,AUD...)
                    $transaction_id = 0;
                    return $dep_initials . sprintf('%03d', ($transaction_id + 1));
                } else {
                    // IF MAY LAMAN ANG EXISTING NA ANG KEYWORD DOON SA TRANSACTION (FIN,MIS,AUD...)
                    $transaction_code = preg_replace('/[^0-9.]+/', '', $transactions->transaction_id);

                    if (empty($transaction_code)) {
                        return $dep_initials . sprintf('%03d', ($transaction_code + 1));
                    } else {
                        $transaction_id = preg_replace('/[^0-9.]+/', '', ($transaction_code + 1));
                    }
                    return ($dep_initials . sprintf('%03d', ($transaction_id)));

                }

            }

        }
        
        public static function getTransactionID($department){
            $transaction_no = GenericMethod::getTransactionNo($department);
            return GenericMethod::getTransactionCode($department, $transaction_no);
        }

    ##########################################################################################################
    #########################################      VALIDATION           ######################################
    ##########################################################################################################

        public static function documentNoValidation($doc_no){
            if(!isset($doc_no)){
                
                throw new FistoLaravelException("Document number is empty.", 404, NULL, []);
            }
            
            if(TransactionValidationMethod::validateIfDocumentNoExist($doc_no) > 0){
                throw new FistoLaravelException("The given data was invalid.", 422, NULL, collect(["document.no"=>["The Document number has already been taken."]]));
            }
        }
        
        public static function documentNoValidationUpdate($doc_no,$id){
            if(!isset($doc_no)){
                
                throw new FistoException("Document number is empty.", 404, NULL, []);
            }
            
            if(TransactionValidationMethod::validateIfDocumentNoExistUpdate($doc_no,$id) > 0){
                throw new FistoLaravelException("The given data was invalid.", 422, NULL, collect(["document.no"=>["The Document number has already been taken."]]));
            }
        }
            
        public function getEmptyErrorBag($tableName,$index,$errorBag) {
            foreach($tableName as $key=>$value){
                if(empty($value)){
                $errorBag[] = [
                    "error_type" => "empty",
                    "line" => $index,
                    "description" => $key." is empty."
                ];
                }
            }
            return $errorBag;
        }

        public static function validateTransactionByDateRange($from,$to,$company_id,$department_id,$location_id,$category,$id=0){
            $transactions = DB::table('transactions')
            ->where(function ($query) use($from,$to){
                $query->where(function ($query) use($from,$to){
                    $query->where(function ($query1) use($from){
                        $query1->where('utilities_from','<=',$from)
                        ->where('utilities_to','>=',$from);
                    })
                    ->orWhere(function ($query2) use($to){
                        $query2->where('utilities_from','<=',$to)
                        ->where('utilities_to','>=',$to);
                    });
                })
                ->orWhere(function ($query) use($from,$to){
                    $query->where(function ($query1) use($from,$to){
                        $query1->where('utilities_from','>=',$from)
                        ->where('utilities_to','<=',$to);
                    });
                });
            })
            ->where('company_id',$company_id)
            ->where('state','!=','void')
            ->where('utilities_location_id',$location_id)
            ->where('utilities_category',$category)
            ->when($id, function($query, $id){
                $query->where('id','<>',$id);
            })
            ->get();
            if(count($transactions)>0){                
                return GenericMethod::resultLaravelFormat(
                    [
                        'document.from',
                        'document.to',
                        'document.company.id',
                        'document.department.id',
                        'document.utility.location.id',
                        'document.utility.category.id',
                    ],
                    [
                        ["from has already been taken."],
                        ["to has already been taken."],
                        ["Company has already been taken."],
                        ["Department has already been taken."],
                        ["Utility Location has already been taken."],
                        ["Utility Category has already been taken."]
                    ]
                );
            }
        }
        
        public static function validatePayroll($payroll_from
        ,$payroll_to
        ,$company_id
        ,$location_id
        ,$supplier_id
        ,$payroll_client
        ,$payroll_type
        ,$payroll_category
        ,$id=0){
            
            $duplicate_client = [];
            foreach($payroll_client as $specific_client){
                $client_id = $specific_client['id'];
                $client_name = $specific_client['name'];
               $transactions = DB::table('transactions')
                ->leftJoin('transaction_client','transactions.request_id','=','transaction_client.request_id')
                ->select('client_name')
                ->where('company_id',$company_id)
                ->where('supplier_id',$supplier_id)
                ->where('payroll_category',"$payroll_category")
                ->where('payroll_type',$payroll_type)
                ->where('client_name',$client_name)
                ->where('state','!=','void')
                ->when($id,function ($query,$id){
                    $query->where('transactions.id','<>',$id);
                })
                ->where(function ($query) use($payroll_from,$payroll_to){
                    $query->where(function ($query) use($payroll_from,$payroll_to){
                        $query->where(function ($query1) use($payroll_from){
                            $query1->where('payroll_from','<=',$payroll_from)
                            ->where('payroll_to','>=',$payroll_from);
                        })
                        ->orWhere(function ($query2) use($payroll_to){
                            $query2->where('payroll_from','<=',$payroll_to)
                            ->where('payroll_to','>=',$payroll_to);
                        });
                    })
                    ->orWhere(function ($query) use($payroll_from,$payroll_to){
                        $query->where(function ($query1) use($payroll_from,$payroll_to){
                            $query1->where('payroll_from','>=',$payroll_from)
                            ->where('payroll_to','<=',$payroll_to);
                        });
                    });
                })
                ->get();

                if(count($transactions) > 0){
                    array_push($duplicate_client,$client_name);
                }
            }
            $duplicate_clients = GenericMethod::addAnd($duplicate_client);
            if(!empty($duplicate_client)){
                return GenericMethod::resultLaravelFormat(
                    [
                        'document.payroll.type',
                        'document.payroll.clients',
                        'document.payroll.category',
                        'document.from',
                        'document.to',
                    ],
                    [
                        ["Payroll type has already been taken."],
                        ["Payroll client has already been taken."],
                        ["Payroll category has already been taken."],
                        ["from has already been taken."],
                        ["to date has already been taken."]
                    ]
                );
            }

        }

        public static function validateReferenceNo($fields,$id=0){
            $validateTransactionCount =  Transaction::where('company_id',$fields['document']['company']['id'])
            ->where('referrence_no',$fields['document']['reference']['no'])
            ->when($id,function($query,$id){
                $query->where('id','<>',$id);
            })
            ->where('state','!=','void')->get();
            if(count($validateTransactionCount)>0){
                return GenericMethod::resultLaravelFormat('document.reference.no',["Reference number already exist."]);
            }
        }

        
        public static function getAndValidatePOBalance($company_id,$po_no,float $reference_amount,$po_group,$id=0){
             $balance_po_ref_amount = Transaction::leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
            ->where('transactions.company_id',$company_id)
            ->when($id,function($query,$id){
                $query->where('transactions.id','<>',$id);
            })
            ->where('transactions.state','!=','void')
            ->where('p_o_batches.po_no',$po_no)
            ->orderBy('transactions.id', 'desc')
            ->get('balance_po_ref_amount')
            ->first();

            if(empty($balance_po_ref_amount)){
                return;
            }
            $balance_po_ref_amount = $balance_po_ref_amount->balance_po_ref_amount;
            
            if($balance_po_ref_amount == 0){
                return GenericMethod::resultLaravelFormat('po_group.no',["PO already exist."]);
            }
            // Additional PO
            $additional_po_group = []; 
            $po_total_amount = 0;

            foreach($po_group as $k=>$v){
                if(!POBatch::leftJoin('transactions','p_o_batches.request_id','=','transactions.request_id')
                ->where('company_id','=',$company_id)
                ->when($id,function($query,$id){
                    $query->where('transactions.id','<>',$id);
                })
                ->where('p_o_batches.po_no','=',$po_group[$k]['no'])
                ->where('state','!=','void')->exists()){
                    $additional_po_group[$k]['no'] = $po_group[$k]['no'] ;
                    $additional_po_group[$k]['amount'] = $po_group[$k]['amount'] ;
                    $additional_po_group[$k]['rr_no'] = $po_group[$k]['rr_no'] ;
                }
                $po_total_amount = $po_total_amount+ $po_group[$k]['amount'];
            }
            $additional_po_group = array_values($additional_po_group);

            if(count($additional_po_group)>0){

                $new_po_total_amount = GenericMethod::getPOTotalAmount($request_id=0,$additional_po_group);
                $additional_plust_balance_amount =  $new_po_total_amount+$balance_po_ref_amount;
                
                if($additional_plust_balance_amount < $reference_amount ){
                    return GenericMethod::resultLaravelFormat('document.reference.no',["Insufficient PO balance."]);
                }
                $balance = GenericMethod::getBalance($new_po_total_amount,$balance_po_ref_amount,$reference_amount);
                
                 return array(
                "po_total_amount" => $po_total_amount
                ,"new_po_total_amount" => $new_po_total_amount
                ,"balance" => $balance
                ,"new_po_group" => $additional_po_group);
            }
            
            if($balance_po_ref_amount < $reference_amount ){
                return GenericMethod::resultLaravelFormat('document.reference.no',["Insufficient PO balance."]);
            }
            $balance = ($balance_po_ref_amount-$reference_amount);
            return $balance;
        }
        
        public static function getBalancePORefAmount($company_id,$reference_no){
            return Transaction::where('company_id',$company_id)
            ->where('referrence_no',$reference_no)
            ->get('balance_po_ref_amount')->first()->balance_po_ref_amount;
        }

        // public static function validateReceiptPartial($fields){

        //    return $transaction = DB::table('transactions')
        //     ->leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
        //     ->where('transactions.company_id',$fields['document']['company']['id'])
        //     ->where('transactions.supplier_id',$fields['document']['supplier']['id'])
        //     ->where('transactions.balance_po_ref_amount','>',0)
        //     ->where('p_o_batches.po_no',$fields['po_group']['no'])
        //     ->orderBy('p_o_batches.id','desc');
        //     $validateTransactionCount = $transaction->get();

        //     if(count($validateTransactionCount)>0){
        //         return GenericMethod::resultLaravelFormat('document.no',["Reference number already exist."]);
        //     }
        // }
        
        public static function validatePCF($pcf_name,$pcf_date,$pcf_letter,$company_id,$supplier_id,$id=0){
            
            $transactions = DB::table('transactions')
                ->where('pcf_name',$pcf_name)
                ->where('pcf_date',$pcf_date)
                ->where('pcf_letter',$pcf_letter)
                ->where('company_id',$company_id)
                ->where('supplier_id',$supplier_id)
                ->where('state','!=','void')
                ->when($id, function($query, $id){
                    $query->where('id','<>',$id);
                })
                ->get();

            if(count($transactions)>0){
                return GenericMethod::resultLaravelFormat(
                    [
                        'document.pcf_batch.letter',
                        'document.pcf_batch.date',
                    ],
                    [
                        ["PCF letter has already been taken."],
                        ["PCF date has already been taken."]
                    ]
                );
            }
        }

        public static function validatePOFull($company_id,$po_group){
            $po_nos = array_column($po_group,'no');
            
            $transactions = DB::table('transactions')
            ->leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
            ->where('company_id',$company_id)
            ->where('transactions.state','!=','void')
            ->whereIn('po_no',$po_nos);
            $validateTransactionCount = $transactions->get();
            $unique_po = array_unique($validateTransactionCount->pluck('po_no')->toArray());
            $duplicate_po_nos = GenericMethod::addAnd($unique_po);

            if(count($validateTransactionCount)>0){
                return GenericMethod::resultLaravelFormat('po_group.no',["PO ".$duplicate_po_nos." has already been taken."]);
            }
        }
        
        public static function validatePOFullUpdate($company_id,$po_group,$id){
            $po_nos = array_column($po_group,'no');
            
            $transactions = DB::table('transactions')
            ->leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
            ->where('transactions.company_id',$company_id)
            ->where('transactions.id','<>',$id)
            ->whereIn('p_o_batches.po_no',$po_nos);
            $validateTransactionCount = $transactions->get();
            $unique_po = array_unique($validateTransactionCount->pluck('po_no')->toArray());
            $duplicate_po_nos = GenericMethod::addAnd($unique_po);
            
            if(count($validateTransactionCount)>0){
                return GenericMethod::resultLaravelFormat('po_group.no',["PO ".$duplicate_po_nos." has already been taken."]);
            }
        }

        public static function validateIfDocumentNoExist($doc_no){
            $transactions = DB::table('transactions')
            ->where('document_no',$doc_no)
            ->whereNotNull('document_no');
            return $transactions->count();

        }

        public static function validateIfPONoExist($payment_type,$company_id,$supplier_id,$po_no){
            $transactions = DB::table('transactions')
            ->leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
            // ->where('payment_type',$payment_type)
            ->where('company_id',$company_id)
            ->where('supplier_id',$supplier_id)
            ->where('po_no',$po_no);
            return $transactions->count();
        }

        public static function validateIfUtilityExist($payment_type,$company_id,
            $supplier_id,$utilities_from
            ,$utilities_to,$utilities_category,$utilities_account_no,
            $utilities_consumption,$utilities_uom,
            $utilities_receipt_no){
                $transactions = DB::select
                ( DB::raw("SELECT id FROM `transactions`
                    WHERE `company_id` = $company_id AND
                    `supplier_id` = $supplier_id AND
                    `utilities_category` = '$utilities_category' AND
                    `utilities_account_no` = '$utilities_account_no' AND
                    (
                        (`utilities_from` >= '$utilities_from' AND `utilities_from` <= '$utilities_to')
                            OR
                        (`utilities_to` >= '$utilities_from' AND `utilities_to` <= '$utilities_to')
                    )"
                ) );
                return count($transactions);
        }

        public static function validateIfPayrollExist($payment_type,$company_id,
            $supplier_id,$payroll_from
            ,$payroll_to,$payroll_client,
            $payroll_category,$payroll_type){

                $duplicate_client = 0;

                foreach($payroll_client as $specific_client){
                    $transactions = DB::table('transactions')
                    ->select('id')
                    ->where('company_id',$company_id)
                    ->where('supplier_id',$supplier_id)
                    ->where('payroll_category',$payroll_category)
                    ->where('payroll_type',$payroll_type)
                    ->whereJsonContains('payroll_client',$specific_client)
                    ->whereBetween('payroll_from', [$payroll_from, $payroll_to])
                    ->orWhereBetween('payroll_to', [$payroll_from, $payroll_to])
                    ->where(function ($query) use($payroll_from,$payroll_to){
                        $query->where(function ($query2) use($payroll_from,$payroll_to){
                            $query2->where('payroll_from','>=',$payroll_from)
                            ->where('payroll_from','<=',$payroll_to);
                        })->orWhere(function ($query3) use($payroll_from,$payroll_to){
                            $query3->where('payroll_to','>=',$payroll_from)
                            ->where('payroll_to','<=',$payroll_to);
                        });
                    })->get();

                    if(count($transactions) > 0){
                        $duplicate_client = $duplicate_client+1;
                    }
                }
                return $duplicate_client;
        }

        public static function validateIfPCFExist($payment_type,$company_id,
            $supplier_id,$pcf_date,$pcf_letter){

                $duplicate_client = 0;

                $transactions = DB::table('transactions')
                ->where('company_id',$company_id)
                ->where('supplier_id',$supplier_id)
                ->where('pcf_date',$pcf_date)
                ->where('pcf_letter',$pcf_letter)
                ->get();

                return $transactions->count();
        }

        public static function validateIfPONoExistInDifferentSupplierReceiptPartial($payment_type,$company_id,$supplier_id,$po_no){
            $transactions = DB::table('transactions')
            ->leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
            ->where('transactions.company_id',$company_id)
            ->where('transactions.supplier_id',$supplier_id)
            ->where('transactions.balance_po_ref_amount','>',0)
            ->orWhere('transactions.balance_po_ref_qty','>',0)
            ->where('p_o_batches.po_no',$po_no)
            ->orderBy('p_o_batches.id','desc')
            ->get();
            return $transactions;
        }

        public static function validateIfPONoExistInDifferentSupplier($payment_type,$company_id,$supplier_id,$po_no){
            $transactions = DB::table('transactions')
            ->leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
            ->where('transactions.company_id',$company_id)
            ->where('transactions.supplier_id',$supplier_id)
            ->where('p_o_batches.po_no',$po_no);
            return $transactions->count();
        }

        public static function validateIfRefNoExist($payment_type,$company_id,$supplier_id,$ref_no){
            $transactions = DB::table('transactions')
            ->leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
            ->leftJoin('referrence_batches','transactions.request_id','=','referrence_batches.request_id')
            // ->where('payment_type',$payment_type)
            ->where('company_id',$company_id)
            ->where('supplier_id',$supplier_id)
            ->where('referrence_no',$ref_no)
            // ->where(function ($query){
            //     $query->where('transactions.balance_po_ref_amount','=',0)
            //     ->orWhereNull('transactions.balance_po_ref_amount');
            // })
            ->get();
            return count($transactions);
        }

        public static function validateIfDocumentAmountIsGreaterThanPO($po_total_amount,$document_amount,$po_additional_pos){
            if ($po_total_amount < $document_amount){
                $response = [
                    "code" => 403,
                    "message" => "Document amount is higher than the old balance and total amount of additional POs ",
                        "data" => $po_additional_pos,
                ];
            }else{
                    $response = "Insert Additional PO";
            }
            return $response;
        }

        public static function validateIfPOExistInOtherDocNo($payment_type,$company_id,$supplier_id,$po_no,$used_request_id){
            $transactions = DB::table('transactions')
            ->leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
            ->where('transactions.company_id',$company_id)
            ->where('transactions.supplier_id',$supplier_id)
            ->where('p_o_batches.po_no',$po_no)
            ->whereIn('transactions.request_id',$used_request_id);
            return $transactions->count();
        }

        public static function validateDuplicateDocumentType($type){
            $documentType = DB::table('documents')
            ->where('type', '=', $type)
            ->get();

            return $documentType;

        }

        public static function validateDuplicateDocumentTypeInUpdate($type,$id){
            $documentType = DB::table('documents')
            ->where('id', '!=', $id)
            ->where('type', '=', $type)
            ->get();

            return $documentType;

        }

        public static function validateDuplicateByIdAndTable($value,$field,$table){
            $result = DB::table(''.$table.'')
            ->where(''.$field.'', '=', $value)
            ->get();

            return $result;
        }

        public static function validateDuplicateInUpdate($value,$field,$table,$id){
            $result = DB::table(''.$table.'')
            ->where(''.$field.'', '=', $value)
            ->where('id', '!=', $id)
            ->get();

            return $result;

        }

        public static function validateIfPOExists($po_group,$company_id,$id=0){

            $po_total_amount = 0;
            $existingTransaction = [];
            foreach($po_group as $k=>$v){
                $po_no = $po_group[$k]['no'];

                $existingTransaction = Transaction::with('po_details')
                ->where('company_id',$company_id)
                ->where('state','!=','void')
                ->when($id,function($query,$id){
                    $query->where('id','<>',$id);
                })
                ->whereHas('po_details',function($q) use($po_no){$q->where('po_no',$po_no);})
                ->exists();
            }

            foreach($po_group as $k=>$v){
               $po_no = $po_group[$k]['no'];

               $transaction = Transaction::with('po_details')
               ->where('company_id',$company_id)
               ->where('state','!=','void')
               ->when($id,function($query,$id){
                   $query->where('id','<>',$id);
               })
                ->whereHas('po_details',function($q) use($po_no){$q->where('po_no',$po_no);})
                ->get();
                if($transaction->count() > 0){
                    $po_group[$k]['is_add'] = 0;
                    $po_group[$k]['is_editable'] = 0;
                    $po_group[$k]['previous_balance'] = Transaction::with('po_details')
                        ->where('company_id',$company_id)
                        ->where('state','!=','void')
                        ->when($id,function($query,$id){
                            $query->where('id','<>',$id);
                        })
                        ->without('po_details')
                        ->whereHas('po_details',function($q) use($po_no){$q->where('po_no',$po_no);})
                        ->whereHas('po_details',function($q) {$q->where('is_add',0);})
                        ->get('balance_po_ref_amount')->last()->balance_po_ref_amount;
               }else{
                   
                   $po_group[$k]['is_editable'] = 1;  
                   $po_group[$k]['is_add'] = 1;  
                    if(!$existingTransaction){
                        $po_group[$k]['is_add'] = 0;  
                       
                    }

                    $po_group[$k]['previous_balance'] =  $v['amount'];  
                }
            }
            return $po_group;
            
        }

    ##########################################################################################################
    #########################################      RESPONSE             ######################################
    ##########################################################################################################
        public static function result($code,$message,$data){
            $arrayResponse = [
                "code" => $code,
                "message" =>$message,
                "result" => $data,
            ];
            return response($arrayResponse,$code);
        }

        public static function resultResponse($action,$modelName,$data=[]){
            $modelName = ucfirst(strtolower($modelName));
            switch($action){
            case('receive'):
                return GenericMethod::result(200,"Transaction has been received.",[]);
            break;
            case('hold'):
                return GenericMethod::result(200,"Transaction has been hold.",[]);
            break;
            case('unhold'):
                return GenericMethod::result(200,"Transaction has been unhold.",[]);
            break;
            case('return'):
                return GenericMethod::result(200,"Transaction has been returned.",[]);
            break;
            case('void'):
                return GenericMethod::result(200,"Transaction has been voided.",[]);
            break;
            case('tag'):
                return GenericMethod::result(200,"Transaction has been tagged.",[]);
            break;
            case('voucher'):
                return GenericMethod::result(200,"Transaction has been vouchered.",[]);
            break;
            case('approval'):
                return GenericMethod::result(200,"Transaction has been approved.",[]);
            break;
            case('transmit'):
                return GenericMethod::result(200,"Transaction has been transmitted.",[]);
            break;
            case('fetch'):
                return GenericMethod::result(200,Str::plural($modelName)." has been fetched.",$data);
            break;
            
            case('save'):
                return GenericMethod::result(201,"New ".strtolower($modelName)." has been saved.",$data);
            break;
        
            case('import'):
                return GenericMethod::result(201,Str::plural($modelName)." has been imported.",$data);
            break;
            
            case('update'):
                return GenericMethod::result(200,$modelName." has been updated.",$data);
            break;
            
            case('archive'):
                return GenericMethod::result(200,$modelName." has been archived.",$data);
            break;
        
            case('restore'):
                return GenericMethod::result(200,$modelName." has been restored.",$data);
            break;
            
            case('registered'):
                throw new FistoException($modelName." already registered.", 409, NULL, $data);
            break;
            
            case('not-registered'):
                throw new FistoException($modelName." not registered.", 409, NULL, $data);
            break;
                
            case('registered-inactive'):
                throw new FistoException($modelName." already registered but inactive.", 409, NULL, $data);
            break;
                
            case('exist'):
                throw new FistoException($modelName." already exist.", 409, NULL, $data);
            break;
            
            case('exist-flow'):
                throw new FistoException("Transaction already ".strtolower($modelName).".", 409, NULL, $data);
            break;
            
            case('import-error'):
                throw new FistoException("No ".Str::plural(strtolower($modelName))." were imported. Kindly check the errors.", 409, NULL, $data);
            break;
            
            case('import-format'):
                throw new FistoException("Invalid excel template, it should be ".$modelName.".", 406, NULL, []);
            break;
            
            case('nothing-has-changed'):
                return GenericMethod::result(200,"Nothing has changed.",$data);
            break;
        
            case('not-found'):
                throw new FistoException("No records found.", 404, NULL, $data);
            break;
        
            case('password-changed'):
                return GenericMethod::result(200,"Password has been changed.",$data);
            break;
        
            case('password-incorrect'):
                throw new FistoException("The password you entered is incorrect.", 409, NULL, $data);
            break;
        
            case('password-error-cred'):
                throw new FistoException("You don't have the proper credentials to perform this action.", 401, NULL, $data);
            break;
        
            case('login'):
                return GenericMethod::result(200,"Succesfully login.",$data);
            break;
        
            case('logout'):
                return GenericMethod::result(200,"User has been logged out.",$data);
            break;
        
            case('logout-again'):
                throw new FistoException("User is already logged out.", 401, NULL, []);
            break;
        
            case('login-error'):
                throw new FistoException("Invalid username or password.", 409, NULL, $data);
            break;
        
            case('available'):
                return GenericMethod::result(200,$modelName." is available.",$data);
            break;
        
            case('password-reset'):
                return GenericMethod::result(200,"User's default password has been restored.",$data);
            break;

            case('invalid'):
              throw new FistoLaravelException("The given data was invalid.", 422, NULL, $data);
            break;
            }
        }
        
        public static function resultLaravelFormat($column,$message){

            if(gettype($column) == "string"){
                return collect(["$column"=>$message]);
            }

            $result = collect();
            $column_count = count($column);
            $message_count = count($message);


            if($column_count === $message_count){
                foreach ($column as $key => $value) {
                    $result->put("$column[$key]", $message[$key]);
                }
                
            }

            return $result;
        }

    ##########################################################################################################
    #########################################      OTHERS               ######################################
    ##########################################################################################################

        public static function viewRequestLogs($request){
            $rows =  (empty($request['rows']))?10:(int)$request['rows'];
            $search =  $request['search'];
            $paginate = (isset($request['paginate']))? $request['paginate']:$paginate = 1;
            
            $requestor_logs = RequestorLogs::with('transaction')
            ->where(function ($query) use ($search){
              $query->where('transaction_id', 'like', '%'.$search.'%')
              ->orWhere('transaction_no', 'like', '%'.$search.'%')
              ->orWhere('description', 'like', '%'.$search.'%')
              ->orWhere('status', 'like', '%'.$search.'%')
              ->orWhere('date_status', 'like', '%'.$search.'%')
              ->orWhere('user_id', 'like', '%'.$search.'%')
              ->orWhere('reason_description', 'like', '%'.$search.'%')
              ->orWhere('reason_remarks', 'like', '%'.$search.'%');
            })
            ->latest('updated_at');
            
          if ($paginate == 1){
            $requestor_logs = $requestor_logs
            ->select(['id','status as type','created_at as date','transaction_id'])
            ->paginate($rows);
          }else if ($paginate == 0){
            $requestor_logs = $requestor_logs
            ->get(['id','status as type','created_at as date','transaction_id']);
            if(count($requestor_logs)==true){
                $requestor_logs = array("requestor_logs"=>$requestor_logs);;
            }
          }
          return $requestor_logs;
        }

        public static function addToUserDocumentCategory($user_id,$document_id,$category_id)
        {
            $new_user_document_category = UserDocumentCategory::create([
                'user_id' =>$user_id,
                'document_id' =>$document_id,
                'category_id' =>$category_id,
            ]);


        }
    ##########################################################################################################
    }