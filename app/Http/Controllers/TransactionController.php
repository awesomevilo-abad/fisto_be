<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\POGroupBatches;
use App\Models\POBatch;
use App\Exceptions\FistoException;
use App\Http\Requests\TransactionPostRequest;
use App\Methods\TransactionValidationMethod;
use App\Http\Controllers\Validation\PadValidationController;
use App\Rules\PODuplicateFull;

class TransactionController extends Controller
{
    public function __construct(){
        // $this->fields = $request->all();
    }

    public function index()
    {
    }

    public function store(TransactionPostRequest $request)
    {

        $validateTransaction = $transactions = DB::table('transactions')
        ->leftJoin('p_o_batches','transactions.tag_id','=','p_o_batches.tag_id')
        ->where('company_id',1)
        ->where('po_no',10002);
       $validateTransactionCount = $transactions->count();

        $fields=$request->validate([
            "requestor.id" => 'required'
            , "requestor.id_prefix" => 'required'
            , "requestor.id_no" => 'required'
            , "requestor.role" => 'required'
            , "requestor.position" => 'required'
            , "requestor.first_name" => 'required'
            , "requestor.middle_name" => 'required'
            , "requestor.last_name" => 'required'
            , "requestor.suffix" => 'nullable'
            , "requestor.department" => 'required'
           
            , "document.id" => 'required'
            , "document.payment_type" => 'required'
            , "document.no" => 'required|unique:transactions,document_no'
            , "document.date" => 'required'
            , "document.amount" => 'required|numeric'
            , "document.remarks" => 'nullable'
            , "document.company.id" => 'required'
            , "document.company.name" => 'required'
            , "document.department.id" => 'required'
            , "document.department.name" => 'required'
            , "document.location.id" => 'required'
            , "document.location.name" => 'required'
            , "document.supplier.id" => 'required'
            , "document.supplier.name" => 'required'

            , "po_group.*.no" => ['required','numeric', new PODuplicateFull($validateTransactionCount)]
            , "po_group.*.amount" => 'required|numeric'
            , "po_group.*.rr_no" => 'required',
        ]);
        $transaction_id = $this->getTransactionID($fields['requestor']['department']);
        
        $date_requested = date('Y-m-d H:i:s');
        $po_count = count($fields['po_group']);
        $po_total_amount = 0;
        $po_total_qty = 0;
        $tag_id = 3;

        for($i=0;$i<$po_count;$i++){
            $po_no = $fields['po_group'][$i]['no'];
            $po_amount = $fields['po_group'][$i]['amount'];
            $po_total_amount = $po_total_amount + $po_amount;

            $insert_po_group = POGroupBatches::create([
                'tag_id' => $tag_id
                , "po_no" => $po_no
                , "po_total_amount" => $po_total_amount
            ]);

            $insert_po_batch = POBatch::create([
                'tag_id' => $tag_id,
                'po_no' => $po_no
                , "po_amount" => $po_amount
            ]);

        }


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
            , "document_no" => $fields['document']['no']
            , "document_date" => $fields['document']['date']
            , "document_amount" => $fields['document']['amount']
            , "remarks" => $fields['document']['remarks']
            , "document_type" => 'PAD'

            , "po_total_amount" => $po_total_amount

            , "tag_id" => $tag_id
            , "tagging_tag_id" => 0
            , "date_requested" => $date_requested
            , "status" => "Pending"

       
        ]);
        return $fields;
    }

}
