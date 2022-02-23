<?php

namespace App\Methods;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
// For Pagination with Collection
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

use App\Models\User;
use App\Models\UserDocumentCategory;

class GenericMethod{

    public static function generateTagNo(){
        $tag_no = DB::select(DB::raw('
        SELECT MAX(tagging_tag_id) as max_tag_no FROM transactions'));

        return $tag_no[0]->max_tag_no;
    }

    public static function countTableById($table,$id){
        $table = DB::table($table)->where('id', $id)->where('is_active', 1);
        return $table->count();
    }

    public static function validateIfDocumentNoExist($doc_no){
        $transactions = DB::table('transactions')
        ->where('document_no',$doc_no);
        return $transactions->count();

    }

    public static function validateIfPONoExist($payment_type,$company_id,$supplier_id,$po_no){
        $transactions = DB::table('transactions')
        ->leftJoin('p_o_batches','transactions.tag_id','=','p_o_batches.tag_id')
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


            $transactions = DB::table('transactions')
            ->where('company_id',$company_id)
            ->where('supplier_id',$supplier_id)
            ->where('payroll_category',$payroll_category)
            ->where('payroll_type',$payroll_type)
            ->whereJsonContains('payroll_client',$payroll_client)
            ->where(function ($query) use($payroll_from,$payroll_to){
                $query->where(function ($query2) use($payroll_from,$payroll_to){
                    $query2->where('payroll_from','>=',$payroll_from)
                    ->where('payroll_from','<=',$payroll_to);
                })->orWhere(function ($query3) use($payroll_from,$payroll_to){
                    $query3->where('payroll_to','>=',$payroll_from)
                    ->where('payroll_to','<=',$payroll_to);
                });
            })->get();

            return $transactions->count();
    }

    public static function validateIfPONoExistInDifferentSupplier($payment_type,$company_id,$supplier_id,$po_no){
        $transactions = DB::table('transactions')
        ->leftJoin('p_o_batches','transactions.tag_id','=','p_o_batches.tag_id')
        ->where('payment_type',$payment_type)
        ->where('company_id',$company_id)
        ->where('po_no',$po_no);
        return $transactions->count();
    }

    public static function validateIfRefNoExist($payment_type,$company_id,$supplier_id,$ref_no){
        $transactions = DB::table('transactions')
        ->leftJoin('p_o_batches','transactions.tag_id','=','p_o_batches.tag_id')
        ->leftJoin('referrence_batches','transactions.tag_id','=','referrence_batches.tag_id')
        ->where('payment_type',$payment_type)
        ->where('company_id',$company_id)
        ->where('supplier_id',$supplier_id)
        ->where('referrence_no',$ref_no);
        return $transactions->count();
    }

    public static function getBalanceAmountOfDocumentPO($payment_type,$company_id,$supplier_id,$po_no,$document_amount){
        $transactions = DB::table('transactions')
        ->leftJoin('p_o_batches','transactions.tag_id','=','p_o_batches.tag_id')
        ->where('transactions.payment_type',$payment_type)
        ->where('transactions.company_id',$company_id)
        ->where('transactions.supplier_id',$supplier_id)
        ->where('p_o_batches.po_no',$po_no)
        ->orderBy('transactions.id','desc')
        ->take(1)
        ->get('transactions.balance_document_po_amount');

        return $transactions;

    }

    public static function getUsedPO($payment_type,$company_id,$supplier_id,$po_no,$document_amount){
        $transactions = DB::table('transactions')
        ->leftJoin('p_o_batches','transactions.tag_id','=','p_o_batches.tag_id')
        ->where('transactions.payment_type',$payment_type)
        ->where('transactions.company_id',$company_id)
        ->where('transactions.supplier_id',$supplier_id)
        ->where('p_o_batches.po_no',$po_no)
        ->where('transactions.balance_document_po_amount','<',$document_amount);
        return $transactions->count();
    }

    public static function getPOWithInsufficientAmont($payment_type,$company_id,$supplier_id,$po_no,$document_amount){
        $transactions = DB::table('transactions')
        ->leftJoin('p_o_batches','transactions.tag_id','=','p_o_batches.tag_id')
        ->where('payment_type',$payment_type)
        ->where('company_id',$company_id)
        ->where('supplier_id',$supplier_id)
        ->where('po_no',$po_no)
        ->where('balance_document_po_amount','<',$document_amount)->get();
        return $transactions;
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

    public static function validateIfPOExistInOtherDocNo($payment_type,$company_id,$supplier_id,$po_no,$used_tag_id){
        $transactions = DB::table('transactions')
        ->leftJoin('p_o_batches','transactions.tag_id','=','p_o_batches.tag_id')
        // ->where('transactions.payment_type',$payment_type)
        ->where('transactions.company_id',$company_id)
        ->where('transactions.supplier_id',$supplier_id)
        ->where('p_o_batches.po_no',$po_no)
        ->whereIn('transactions.tag_id',$used_tag_id);
        return $transactions->count();
    }

    public static function getTagIDUsingPONo($payment_type,$company_id,$supplier_id,$po_no){
        $transactions = DB::table('transactions')
        ->select('transactions.tag_id')
        ->leftJoin('p_o_batches','transactions.tag_id','=','p_o_batches.tag_id')
        // ->where('transactions.payment_type',$payment_type)
        ->where('transactions.company_id',$company_id)
        ->where('transactions.supplier_id',$supplier_id)
        ->where('p_o_batches.po_no',$po_no)
        ->orderBy('transactions.tag_id','desc')
        ->get();
        return $transactions;
    }

    public static function getUsedPOFromDB($payment_type,$company_id,$supplier_id,$po_no,$document_amount){
        $transactions = DB::table('p_o_batches')
        ->where('tag_id', '=', function ($query) use ($po_no) {
            $query->selectRaw('tag_id')->from('p_o_batches')
            ->where('po_no',$po_no)
            ->orderByDesc('id')
            ->limit(1);
        })
        ->get('po_no');

        return $transactions;
    }

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

    public static function paginateme($items, $perPage = 1000, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }
    public static function getUserDetailsById($id){
        $user_details = User::find($id);

        $document_details = DB::select( DB::raw('SELECT documents.id AS "masterlist_id",
        categories.is_active,
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
        -- WHERE  documents.is_active = 1
        -- AND document_categories.is_active = 1
        WHERE  IFNULL(categories.is_active,0) = (IF((IFNULL(categories.id,"no category")) = "no category",0, 1))
        -- AND user_document_category.is_active = 1
        -- AND users.is_active = 1
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
            "username"=> $user_details->username,
            "is_active"=> 1,
        ]);

        $result = $user_document_details;


        if (!$result) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }

        return $result;
    }

    public static function getCategoriesByUserAndDocID($user_id,$id){
        $get_category_ids = DB::select
        ( DB::raw("SELECT user_id,document_id,category_id
        FROM `user_document_category`
        WHERE `user_id` = '$user_id' AND `document_id` = '$id' AND `is_active` = 1
         ORDER BY `id` ASC") );

        return $get_category_ids;
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

    public static function addToUserDocumentCategory($user_id,$document_id,$category_id){
        $new_user_document_category = UserDocumentCategory::create([
            'user_id' =>$user_id,
            'document_id' =>$document_id,
            'category_id' =>$category_id,
            'is_active' => 1,
        ]);


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
                ->where('tag_id',$transaction->tagging_tag_id)->orderBy('id', 'desc')->first();

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
            ->where('PB.tag_id',$transaction->tag_id)
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
           ->where('tag_id','=',$transaction->tag_id)
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
                'tag_id'=>$transaction->tag_id,
                'tagging_tag_id'=>$transaction->tagging_tag_id,
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
    }

    public static function updateTransactionStatus($transaction_id,$status){
        DB::table('transactions')
            ->where('transaction_id', $transaction_id)
            ->update(['status' => $status]);
    }
}
