<?php

namespace App\Methods;

use App\Exceptions\FistoException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
// For Pagination with Collection
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

use App\Models\User;
use App\Models\POBatch;
use App\Models\TransactionClient;
use App\Models\ReferrenceBatch;
use App\Models\Transaction;
use App\Models\PayrollClient;

use App\Models\UserDocumentCategory;
use Illuminate\Routing\Route;

class GenericMethod{


    ##########################################################################################################
    #########################################      REUSABLE FUNCTION    ######################################
    ##########################################################################################################

        public static function insertTransaction($transaction_id,$po_total_amount=0,
        $request_id,$date_requested,$fields,$balance_po_ref_amount=0){
            
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
            }else{
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

            return $new_transaction;
            // if($new_transaction->count()>1){
            //     return GenericMethod::resultLaravelFormat('po_group.no',"The PO number has already been taken.: ".$validateTransactionCount->pluck('po_no')->implode(','));
            // }


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

        public static function insertPO($request_id,$po_group,$po_total_amount){
            $po_count = count($po_group);
            for($i=0;$i<$po_count;$i++){
                
                $po_no = $po_group[$i]['no'];
                $po_amount = $po_group[$i]['amount'];
                $rr_group = $po_group[$i]['rr_no'];
                $insert_po_batch = POBatch::create([
                    'request_id' => $request_id,
                    'po_no' => $po_no,
                    'po_amount' => $po_amount,
                    'rr_group' => $rr_group,
                    'po_total_amount' => $po_total_amount
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

        public static function updateTransactionStatus($transaction_id,$status)
        {
            DB::table('transactions')
                ->where('transaction_id', $transaction_id)
                ->update(['status' => $status]);
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
            $tag_no = DB::select(DB::raw('
            SELECT MAX(tagging_request_id) as max_tag_no FROM transactions'));

            return $tag_no[0]->max_tag_no;
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

        public static function getTransactionFormat($transactions, $table){

            $result = collect();
            foreach($transactions as $transaction){

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
            }

            $resultTransaction =$result->sortDesc();
            $resultTransaction = $resultTransaction->values();
            return GenericMethod::paginateme($resultTransaction);
            // return $result->paginateme(5);
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
                
                throw new FistoException("Document number is empty.", 404, NULL, []);
            }
            
            if(TransactionValidationMethod::validateIfDocumentNoExist($doc_no) > 0){
                throw new FistoException("Document number already exist in other document types", 409, NULL, []);
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

        public static function validateTransactionByDateRange($from,$to,$company_id,$department_id,$location_id,$category){
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
            ->where('utilities_location_id',$location_id)
            ->where('utilities_category',$category)
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
        ,$payroll_category){
            
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

        public static function validateReferenceNo($fields){
            $transaction =  Transaction::where('company_id',$fields['document']['company']['id'])
            ->where('referrence_no',$fields['document']['reference']['no']);
            $validateTransactionCount = $transaction->get();

            if(count($validateTransactionCount)>0){
                return GenericMethod::resultLaravelFormat('document.reference.no',["Reference number already exist."]);
            }
        }

        
        public static function getAndValidatePOBalance($company_id,$po_no,float $reference_amount,$po_group){
             $balance_po_ref_amount = Transaction::leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
            ->where('transactions.company_id',$company_id)
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
                ->where('p_o_batches.po_no','=',$po_group[$k]['no'])->exists()){
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
        
        public static function validatePCF($pcf_name,$pcf_date,$pcf_letter,$company_id,$supplier_id){
            
            $transactions = DB::table('transactions')
                ->where('pcf_name',$pcf_name)
                ->where('pcf_date',$pcf_date)
                ->where('pcf_letter',$pcf_letter)
                ->where('company_id',$company_id)
                ->where('supplier_id',$supplier_id)
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
            ->whereIn('po_no',$po_nos);
            $validateTransactionCount = $transactions->get();
            
            $duplicate_po_nos = GenericMethod::addAnd($validateTransactionCount->pluck('po_no')->toArray());

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
            
            if(count($validateTransactionCount)>0){
                return GenericMethod::resultLaravelFormat('po_group.no',["PO ".$validateTransactionCount->pluck('po_no')->implode(', ')." has already been taken."]);
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

    ##########################################################################################################
    #########################################      RESPONSE             ######################################
    ##########################################################################################################

        public function resultResponse($action,$modelName,$data=[]){
            $modelName = ucfirst(strtolower($modelName));
            switch($action){
            case('fetch'):
                return $this->result(200,Str::plural($modelName)." has been fetched.",$data);
            break;
            
            case('save'):
                return $this->result(201,"New ".strtolower($modelName)." has been saved.",$data);
            break;
    
            case('import'):
                return $this->result(201,Str::plural($modelName)." has been imported.",$data);
            break;
            
            case('update'):
                return $this->result(200,$modelName." has been updated.",$data);
            break;
            
            case('archive'):
                return $this->result(200,$modelName." has been archived.",$data);
            break;
    
            case('restore'):
                return $this->result(200,$modelName." has been restored.",$data);
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
            
            case('import-error'):
                throw new FistoException("No ".Str::plural(strtolower($modelName))." were imported. Kindly check the errors.", 409, NULL, $data);
            break;
            
            case('import-format'):
                throw new FistoException("Invalid excel template, it should be ".$modelName.".", 406, NULL, []);
            break;
            
            case('nothing-has-changed'):
                return $this->result(200,"Nothing has changed.",$data);
            break;
    
            case('not-found'):
                throw new FistoException("No records found.", 404, NULL, $data);
            break;
    
            case('password-changed'):
                return $this->result(200,"Password has been changed.",$data);
            break;
    
            case('password-incorrect'):
                throw new FistoException("The password you entered is incorrect.", 409, NULL, $data);
            break;
    
            case('password-error-cred'):
                throw new FistoException("You don't have the proper credentials to perform this action.", 401, NULL, $data);
            break;
    
            case('login'):
                return $this->result(200,"Succesfully login.",$data);
            break;
    
            case('logout'):
                return $this->result(200,"User has been logged out.",$data);
            break;
    
            case('logout-again'):
                throw new FistoException("User is already logged out.", 401, NULL, []);
            break;
    
            case('login-error'):
                throw new FistoException("Invalid username or password.", 409, NULL, $data);
            break;
    
            case('available'):
                return $this->result(200,$modelName." is available.",$data);
            break;
    
            case('password-reset'):
                return $this->result(200,"User's default password has been restored.",$data);
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
