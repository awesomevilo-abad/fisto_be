<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Methods\PADValidationMethod;
use App\Methods\GenericMethod;
use App\Models\Transaction;


use App\Http\Requests\TransactionPostRequest;

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
        $fields=$request->validated();
        $date_requested = date('Y-m-d H:i:s');
        
        $transaction_id = GenericMethod::getTransactionID($fields['requestor']['department']);
        $request_id = GenericMethod::getRequestID();
        $duplicatePO = PADValidationMethod::validatePOFull($fields['document']['company']['id'],$fields['po_group']);

        if(isset($duplicatePO)){
            return $this->resultResponse('invalid','Transaction',$duplicatePO);
         }

        $po_total_amount = GenericMethod::insertPO($request_id,$fields['po_group']);
        $transaction = GenericMethod::insertTransaction($transaction_id,$po_total_amount,
        $request_id,$date_requested,$fields);

        if(isset($transaction->transaction_id)){
           return $this->resultResponse('save','Transaction',[]);
        }
    }

}
