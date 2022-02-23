<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\POBatch;
use App\Models\RRBatch;
use App\Models\ReferrenceBatch;
use App\Models\ReferrenceGroupBatches;
use App\Models\POGroupBatches;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $transactions = Transaction::all();

        $result = collect();
        foreach($transactions as $transaction){

            $date_requested = date('Y-m-d',strtotime($transaction->created_at));


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




            $result->push(
                [
                'date_requested'=>$date_requested,
                'transaction_id'=>$transaction->transaction_id,
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
                'remarks'=>$transaction->remarks,

                ]);
        }
        return $result;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    //  SPECIAL FUNCTIONS

    public function getTransactionID($str)
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

    public function getTransactionCode($str, $transaction_id)
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

    public function addZeroPrefix($doc_no)
    {
        return sprintf('%06d', $doc_no);
    }

    public function documentCount($doc_id)
    {
        $documents = DB::table('documents')->where('id', $doc_id)->where('is_active', 1);
        return $documents->count();

    }

    public function categoryCount($cat_id)
    {
        $categories = DB::table('categories')->where('id', $cat_id)->where('is_active', 1);
        return $categories->count();

    }

    public function companyCount($company_id)
    {
        $companies = DB::table('companies')->where('id', $company_id)->where('is_active', 1);
        return $companies->count();

    }

    public function supplierCount($supplier_id)
    {
        $suppliers = DB::table('suppliers')->where('id', $supplier_id)->where('is_active', 1);
        return $suppliers->count();

    }

    public function referrenceCount($referrence_no)
    {
        $referrences = DB::table('referrences')->where('id', $referrence_no)->where('is_active', 1);
        return $referrences->count();

    }
    public function reasonCount($reason_id)
    {
        $reasons = DB::table('reasons')->where('id', $reason_id)->where('is_active', 1);
        return $reasons->count();

    }

    public function getTagID()
    {
        $transactions = DB::table('transactions')->select('tag_id')->orderBy('id', 'desc')->first();
        if (empty($transactions)) {
            $tag_id = 0;
        } else {
            $tag_id = $transactions->tag_id;
        }
        return ($tag_id + 1);
    }
    //  END SPECIAL FUNCTIONS


    public function validateIfPOExistInSupplier($supplier_id,$po_no){
        $transaction = DB::table('transactions')
        ->leftJoin('p_o_batches','transactions.tag_id','=','p_o_batches.tag_id')
        ->where('supplier_id','=',$supplier_id)
        ->where('po_no','=',$po_no);
        
        if ($transaction->count() > 0){
            $response = [
                "code" => 403,
                "message" => "PO Already exist in the supplier",
                "data" => null,
            ];
        }else{
            $response = "Maay Laman";
            }

        return $response;
    }



    public function store(Request $request)
    {
        $fields = $request->validate([

            // AUTOMATIC USER DETAILS BASED ON LOGIN
            "users_id" => 'required'
            , "id_prefix" => 'required'
            , "id_no" => 'required'
            , "first_name" => 'required'
            , "middle_name" => 'required'
            , "last_name" => 'required'
            , "suffix" => 'nullable'
            , "department" => 'required'

            // SELECTED DOCUMENT TYPE
            , "document_id" => 'required'
            , "document_type" => 'required'

            // SELECTED CATEGORY (CONDITIONAL)
            , "category_id" => 'nullable'
            , "category" => 'nullable'

            // PAYMENT TYPE BASED ON FE SELECTED
            , "payment_type" => 'required'

            // SELECTED COMPANY
            , "company_id" => 'required'
            , "company" => 'required'

            // INPUTTED DOCUMENT NO
            , "document_no" => 'required'

            // SELECTED SUPPLIER
            , "supplier_id" => 'required'
            , "supplier" => 'required'

            // INPUTTED DOCUMENT DATE & AMOUNT
            , "document_date" => 'nullable'
            , "document_amount" => 'nullable'

            // OPTIONAL(ADD REMARKS)
            , "remarks" => 'nullable'

            // CREATE PO GROUP BATCH ID (LINK TO PO BATCHES TABLE WITH AMOUNT)
            , "po_group" => 'nullable'

            // CREATE REF GROUP BATCH ID (LINK TO REF BATCHES TABLE WITH AMOUNT)
            , "referrence_group" => 'nullable'
            , "reason_id" => 'nullable'
            , "reason" => 'nullable'
            , "pcf_date" => 'nullable'
            , "pcf_letter" => 'nullable'
            , "utilities_from" => 'nullable'
            , "utilities_to" => 'nullable'

        ]);

        if ($this->documentCount($fields['document_id']) < 1) {
            $response = [
                "code" => 404,
                "message" => "Document ID is not registered on the masterlist",
                "data" => null,
            ];
        } elseif (isset($fields['category_id']) && $this->categoryCount($fields['category_id']) < 1) {
            $response = [
                "code" => 404,
                "message" => "Category ID is not registered on the masterlist",
                "data" => null,
            ];

        } elseif ($this->companyCount($fields['company_id']) < 1) {
            $response = [
                "code" => 404,
                "message" => "Company ID is not registered on the masterlist",
                "data" => null,
            ];

        } elseif ($this->supplierCount($fields['supplier_id']) < 1) {
            $response = [
                "code" => 404,
                "message" => "Supplier ID is not registered on the masterlist",
                "data" => null,
            ];
        } else {

            $tag_id = $this->getTagID();
            $date_requested = date('Y-m-d H:i:s');
            $status = "Pending";

            $transaction_id = $this->getTransactionID($fields['department']);
            $transaction_id = $this->getTransactionCode($fields['department'], $transaction_id);
            $systemize_doc_no = $this->addZeroPrefix($fields['document_no']);


            // PAD VALIDATION
            if(empty($fields['po_group'])){
                $response = [
                    "code" => 422,
                    "message" => "PO Details not found ",
                    "data" => null,
                ];
            }else{

                $po = $fields['po_group'];
                $po_count = count($po);
                $po_total_amount = 0;
                $po_total_qty = 0;
                $rr_total_qty = 0;

                for($i=0;$i<$po_count;$i++){
                    $po_object = $fields['po_group'][$i];
                    $po_object = (object) $po_object;

                    $po_no =  $po_object->po_no;
                    $po_amount =(float) str_replace(',', '', $po_object->po_amount);
                    $po_qty =(float) str_replace(',', '', $po_object->po_qty);
                    $po_total_amount = $po_total_amount+$po_amount;
                    $po_total_qty = $po_total_qty+$po_qty;

                    print_r($this->validateIfPOExistInSupplier($fields['supplier_id'],$po_no));


                    // $insert_po_group = POGroupBatches::create([
                    //     'tag_id' => $tag_id
                    //     , "po_no" => $po_no
                    // ]);

                    // $insert_po_batch = POBatch::create([
                    //     'tag_id' => $tag_id,
                    //     'po_no' => $po_no
                    //     , "po_amount" => $po_amount
                    //     , "po_qty" => $po_qty
                    // ]);



                    $rr_group = $po_object->rr_group;

                    // foreach($rr_group as $rr){

                    //     $rr_no = $rr['rr_no'];
                    //     $rr_qty = $rr['rr_qty'];
                    //     $rr_total_qty = $rr_total_qty+$rr_qty;

                    //     $transaction = DB::table('p_o_batches as PB')
                    //     ->where('PB.po_no',$po_no)
                    //     ->where('PB.tag_id',$tag_id)
                    //     ->get('PB.id');


                    //     $po_batch_no = $transaction[0]->id;

                    //     $insert_rr_batch = RRBatch::create([
                    //         'po_batch_no' => $po_batch_no
                    //         , "rr_code" => $rr_no
                    //         , "rr_qty" => $rr_qty
                    //     ]);

                    // }

                }

            }







            // $ref = $fields['referrence_group'];
            // $ref_count = count($ref);

            // $referrence_total_amount = 0;
            // $referrence_total_qty = 0;

            // for($i=0;$i<$ref_count;$i++){
            //     $ref_object = $fields['referrence_group'][$i];
            //     $ref_object = (object) $ref_object;

            //     $referrence_amount =  $ref_object->referrence_amount;
            //     $referrence_qty =  $ref_object->referrence_qty;
            //     $referrence_no =  $ref_object->referrence_no;
            //     $referrence_type =  $ref_object->referrence_type;

            //     $referrence_total_amount = $referrence_total_amount+$referrence_amount;
            //     $referrence_total_qty = $referrence_total_qty+$referrence_qty;

            //     $insert_po_group = ReferrenceGroupBatches::create([
            //         'tag_id' => $tag_id
            //         , "referrence_no" => $referrence_no
            //     ]);

            //     $insert_referrence_batch = ReferrenceBatch::create([
            //         'referrence_type' => $referrence_type
            //         , "referrence_no" => $referrence_no
            //         , "referrence_amount" => $referrence_amount
            //         , "referrence_qty" => $referrence_qty
            //         , "tag_id" => $tag_id
            //     ]);

            // }

            // // PAD VALIDATION
            // $transaction_exist = DB::table('transactions')
            //     ->where('document_type', $fields['document_type'])
            //     ->where('payment_type', $fields['payment_type'])
            //     ->where('document_no', $fields['document_no'])
            //     ->where('document_date', $fields['document_date'])
            //     ->where('document_amount', $fields['document_amount'])
            //     ->where('company', $fields['company'])
            //     ->where('supplier', $fields['supplier'])
            // // ->whereJsonContains('po_group', $fields['po_group'])
            // // ->whereJsonContains('referrence_group', $fields['referrence_group'])
            //     ->get();

            // if(count($transaction_exist)>0){
            //     $response = [
            //         "code" => 403,
            //         "message" => "Transaction Exist, (Doc Type, Payment Type, Doc No., Doc Date, Doc Amount, Company and Supplier)",
            //         "data" => null,
            //     ];
            // }else{

            // $new_transaction = Transaction::create([
            //     'transaction_id' => $transaction_id
            //     , "users_id" => $fields['users_id']
            //     , "id_prefix" => $fields['id_prefix']
            //     , "id_no" => $fields['id_no']
            //     , "first_name" => $fields['first_name']
            //     , "middle_name" => $fields['middle_name']
            //     , "last_name" => $fields['last_name']
            //     , "suffix" => $fields['suffix']
            //     , "department" => $fields['department']
            //     , "document_id" => $fields['document_id']
            //     , "document_type" => $fields['document_type']
            //     , "payment_type" => $fields['payment_type']
            //     , "category_id" => $fields['category_id']
            //     , "category" => $fields['category']
            //     , "company_id" => $fields['company_id']
            //     , "company" => $fields['company']
            //     , "document_no" => $fields['document_no']
            //     , "supplier_id" => $fields['supplier_id']
            //     , "supplier" => $fields['supplier']
            //     , "document_date" => $fields['document_date']
            //     , "document_amount" => $fields['document_amount']
            //     , "remarks" => $fields['remarks']
            //     , "po_total_amount" => $po_total_amount
            //     , "po_total_qty" => $po_total_qty
            //     , "rr_total_qty" => $rr_total_qty
            //     , "referrence_total_amount" => $referrence_total_amount
            //     , "referrence_total_qty" => $referrence_total_qty
            //     , "tag_id" => $tag_id
            //     , "date_requested" => $date_requested
            //     , "status" => $status,
            // ]);
            // $response = 'Succesfully Created!';

            // }






        //     // $po_exist_in_a_company = DB::table('transactions')
        //     //     ->where('document_type', $fields['document_type'])
        //     //     ->where('payment_type', $fields['payment_type'])
        //     //     ->where('company', $fields['company'])
        //     // // ->whereJsonContains('po_group', $fields['po_group'])
        //     //     ->get();

        //     // if ($transaction_exist) {
        //     //     $result = 'Transaction Exist';
        //     // } elseif ($po_exist_in_a_company) {

        //     //     $result = 'PO Exist In a Company';
        //     // }
        //     // print_r($result);



        }


        // return $response;

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
