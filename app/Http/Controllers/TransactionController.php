<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\PODetailsRequest;
use App\Methods\PADValidationMethod;
use App\Methods\GenericMethod;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;


use App\Http\Requests\TransactionPostRequest;

class TransactionController extends Controller
{

    public function index(Request $request)
    {
        $role = Auth::user()->role;
        $status =  isset($request['state']) && $request['state'] ? $request['state'] : "request";
        $rows =  isset($request['rows']) && $request['rows'] ? (int)$request['rows'] : 10;
        $search =  $request['search'];

        $transactions = Transaction::select([
            'id',
            'date_requested',
            'transaction_id',
            'document_type',
            'company',
            'supplier',
            'po_total_amount',
            'referrence_total_amount',
            'document_amount',
            'payment_type',
            'status'
        ])
        ->when($role === 'Requestor',function($query){
            $query->where('users_id',Auth::id());
        })
        // ->when($role === 'Approver',function($query){
        //     $query->where('users_id',Auth::id());
        // })
        // ->when($role === 'Requestor',function($query){
        //     $query->where('users_id',Auth::id());
        // })
        ->where('state', $status)
        ->where(function ($query) use ($search) {
            $query->where('date_requested', 'like', '%' . $search . '%')
            ->orWhere('transaction_id', 'like', '%' . $search . '%')
            ->orWhere('document_amount', 'like', '%' . $search . '%')
            ->orWhere('document_type', 'like', '%' . $search . '%')
            ->orWhere('payment_type', 'like', '%' . $search . '%')
            ->orWhere('company', 'like', '%' . $search . '%')
            ->orWhere('supplier', 'like', '%' . $search . '%')
            ->orWhere('po_total_amount', 'like', '%' . $search . '%')
            ->orWhere('referrence_total_amount', 'like', '%' . $search . '%');
        })
        ->latest('updated_at')
        ->paginate($rows);

        if (count($transactions)) return $this->resultResponse('fetch', 'Transaction', $transactions);

        return $this->resultResponse('not-found', 'Transaction', []);
    }


    public function store(TransactionPostRequest $request)
    {
        $fields=$request->validated();
        $date_requested = date('Y-m-d H:i:s');
        $transaction_id = GenericMethod::getTransactionID($fields['requestor']['department']);
        $request_id = GenericMethod::getRequestID();
        
        switch($fields['document']['id']){
            case 1: //PAD
            case 5: //Contractor's Billing
        
                if (empty($fields['po_group']) ) 
                {
                    $errorMessage = GenericMethod::resultLaravelFormat('po_group',["PO group required"]);
                    return $this->resultResponse('invalid','',$errorMessage);
                }
                
                $duplicatePO = GenericMethod::validatePOFull($fields['document']['company']['id'],$fields['po_group']);
                if(isset($duplicatePO)){
                    return $this->resultResponse('invalid','',$duplicatePO);
                }
               
                $po_total_amount = GenericMethod::getPOTotalAmount($request_id,$fields['po_group']);
                if ($fields['document']['amount'] != $po_total_amount){
                   $errorMessage = GenericMethod::resultLaravelFormat('po_group.amount',["Document amount (".$fields['document']['amount'].") and total PO amount (".$po_total_amount.")  are not equal."]);
                   return $this->resultResponse('invalid','',$errorMessage);
                }

                GenericMethod::insertPO($request_id,$fields['po_group']);
                $transaction = GenericMethod::insertTransaction($transaction_id,$po_total_amount,
                $request_id,$date_requested,$fields);
                if(isset($transaction->transaction_id)){
                   return $this->resultResponse('save','Transaction',[]);
                }
            break;

            case 2: //PRM Common
                $transaction = GenericMethod::insertTransaction($transaction_id,NULL,
                $request_id,$date_requested,$fields);
                if(isset($transaction->transaction_id)){
                   return $this->resultResponse('save','Transaction',[]);
                }
            break;

            case 6: //Utilities
                $duplicateUtilities = GenericMethod::validateTransactionByDateRange(
                    $fields['document']['from']
                    ,$fields['document']['to']
                    ,$fields['document']['company']['id']
                    ,$fields['document']['department']['id']
                    ,$fields['document']['utility']['location']['id']
                    ,$fields['document']['utility']['category']['name']
                );
                
                if(isset($duplicateUtilities)){
                    return $this->resultResponse('invalid','',$duplicateUtilities);
                }
                
                $transaction = GenericMethod::insertTransaction($transaction_id,NULL,
                $request_id,$date_requested,$fields);
                if(isset($transaction->transaction_id)){
                   return $this->resultResponse('save','Transaction',[]);
                }
            break;

            case 8: //PCF
                $duplicatePCF = GenericMethod::validatePCF(
                    $fields['document']['pcf_batch']['name']
                    ,$fields['document']['pcf_batch']['date']
                    ,$fields['document']['pcf_batch']['letter']
                    ,$fields['document']['company']['id']
                    ,$fields['document']['supplier']['id']
                );
                if(isset($duplicatePCF)){
                    return $this->resultResponse('invalid','',$duplicatePCF);
                }
                
                $transaction = GenericMethod::insertTransaction($transaction_id,NULL,
                $request_id,$date_requested,$fields);
                if(isset($transaction->transaction_id)){
                   return $this->resultResponse('save','Transaction',[]);
                }
            break;

            case 7: //Payroll
                $duplicatePayroll = GenericMethod::validatePayroll(
                    $fields['document']['from']
                    ,$fields['document']['to']
                    ,$fields['document']['company']['id']
                    ,$fields['document']['location']['id']
                    ,$fields['document']['supplier']['id']
                    ,$fields['document']['payroll']['clients']
                    ,$fields['document']['payroll']['type']
                    ,$fields['document']['payroll']['category']['name']
                );

                if(isset($duplicatePayroll)){
                    return $this->resultResponse('invalid','',$duplicatePayroll);
                }
                GenericMethod::insertClient($request_id,$fields['document']['payroll']['clients']);
                $transaction = GenericMethod::insertTransaction($transaction_id,NULL,
                $request_id,$date_requested,$fields);
                if(isset($transaction->transaction_id)){
                   return $this->resultResponse('save','Transaction',[]);
                }
            break;

            case 4: //Receipt
                $isFull = strtoupper($fields['document']['payment_type']) === 'FULL';
                $isQty = $fields['document']['reference']['type'] === 'DR Qty';

                if (empty($fields['po_group']) ) 
                {
                    $errorMessage = GenericMethod::resultLaravelFormat('po_group',["PO group required"]);
                    return $this->resultResponse('invalid','',$errorMessage);
                }

                $duplicatePO = GenericMethod::validatePOFull($fields['document']['company']['id'],$fields['po_group']);
                if(isset($duplicatePO)){
                    return $this->resultResponse('invalid','',$duplicatePO);
                }
                
                if(!$isQty && $isFull){

                    $duplicateRef = GenericMethod::validateReceiptFull($fields);
                    if(isset($duplicateRef)){
                        return $this->resultResponse('invalid','',$duplicateRef);   
                    }
                    
                    $po_total_amount = GenericMethod::getPOTotalAmount($request_id,$fields['po_group']);
                    if ($fields['document']['reference']['amount'] != $po_total_amount){
                       $errorMessage = GenericMethod::resultLaravelFormat('document.amount',["Reference amount (".$fields['document']['reference']['amount'].") and total PO amount (".$po_total_amount.")  are not equal."]);
                       return $this->resultResponse('invalid','',$errorMessage);
                    }
                    
                    GenericMethod::insertPO($request_id,$fields['po_group']);
                    $transaction = GenericMethod::insertTransaction($transaction_id,$po_total_amount,
                    $request_id,$date_requested,$fields);
                    if(isset($transaction->transaction_id)){
                       return $this->resultResponse('save','Transaction',[]);
                    }
                }

                
                if(!$isQty && !$isFull){
                    return "Amount based and Partial payment";

                    return $duplicateRef = GenericMethod::validateReceiptPartial($fields);
                    if(isset($duplicateRef)){
                        return $this->resultResponse('invalid','',$duplicateRef);   
                    }
                    
                    // $po_total_amount = GenericMethod::getPOTotalAmount($request_id,$fields['po_group']);
                    // GenericMethod::insertPO($request_id,$fields['po_group']);
                    // GenericMethod::insertRef($request_id,$fields);
                    // $transaction = GenericMethod::insertTransaction($transaction_id,$po_total_amount,
                    // $request_id,$date_requested,$fields);
                    // if(isset($transaction->transaction_id)){
                    //    return $this->resultResponse('save','Transaction',[]);
                    // }

                }

                if($isQty && $isFull){
                    return "Qty based and Full payment";
                }

                if($isQty && !$isFull){
                    return "Payment Type does not exist in DR Qty";
                }

            break;
        }

        return $this->resultResponse('not-exist','Document number',[]);
    }
    
    public function update (Request $request, $id)
    {
        
        $fields=$request->validated();

        switch($fields['document']['id']){
            case 1: //PAD
            case 4: //Contractor's Billing
        
                if (empty($fields['po_group']) ) 
                {
                    $errorMessage = GenericMethod::resultLaravelFormat('po_group',["PO group required"]);
                    return $this->resultResponse('invalid','',$errorMessage);
                }
                
                $duplicatePO = GenericMethod::validatePOFullUpdate($fields['document']['company']['id'],$fields['po_group'],$id);
                if(isset($duplicatePO)){
                    return $this->resultResponse('invalid','',$duplicatePO);
                }
               
                $po_total_amount = GenericMethod::getPOTotalAmount($request_id,$fields['po_group']);
                if ($fields['document']['amount'] != $po_total_amount){
                   $errorMessage = GenericMethod::resultLaravelFormat('po_group.amount',["Document amount (".$fields['document']['amount'].") and total PO amount (".$po_total_amount.")  are not equal."]);
                   return $this->resultResponse('invalid','',$errorMessage);
                }

                // GenericMethod::insertPO($request_id,$fields['po_group']);
                // $transaction = GenericMethod::insertTransaction($transaction_id,$po_total_amount,
                // $request_id,$date_requested,$fields);
                // if(isset($transaction->transaction_id)){
                //    return $this->resultResponse('save','Transaction',[]);
                // }
            break;

            case 2: //PRM Common
                $transaction = GenericMethod::insertTransaction($transaction_id,NULL,
                $request_id,$date_requested,$fields);
                if(isset($transaction->transaction_id)){
                   return $this->resultResponse('save','Transaction',[]);
                }
            break;
        }

        return $this->resultResponse('not-exist','Document number',[]);
    }

    public function getPODetails(PODetailsRequest $request)
    {

        $fields = $request->validated();
        $po_details = DB::table('transactions')
        ->leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
        ->where('transactions.company_id',$fields['company_id'])
        ->where('p_o_batches.po_no',$fields['po_no'])
        ->get(['po_no','po_amount','balance_document_po_amount as po_balance']);
        
        if(count($po_details)>0){
            if(strtoupper($fields['payment_type'])=="FULL"){
                $errorMessage = GenericMethod::resultLaravelFormat('po_group.no',["PO number already exist."]);
                return $this->resultResponse('invalid','',$errorMessage);   
            }

            if(($po_details->first()->po_balance <= 0) || ($po_details->first()->po_balance == null) ){
                $errorMessage = GenericMethod::resultLaravelFormat('po_group',["No available balance."]);
                return $this->resultResponse('invalid','',$errorMessage);   
            }
            return $this->resultResponse('fetch','PO number',$po_details->first());   
        }
        return $this->resultResponse('success-no-content','',[]);  
    }

    public function validateDocumentNo(Request $request)
    {
       if (Transaction::where('document_no',$request['document_no'])->first()){
            $errorMessage = GenericMethod::resultLaravelFormat('document.no',["Document number already exist."]);
            return $this->resultResponse('invalid','',$errorMessage);   
        }   
        return $this->resultResponse('success-no-content','',[]); 
    }

    public function validateReferenceNo(Request $request)
    {
       if(Transaction::where('company_id',$request['company_id'])
            ->where('referrence_no',$request['reference_no'])
            ->first()){
                $errorMessage = GenericMethod::resultLaravelFormat('document.reference.no',["Reference number already exist."]);
                return $this->resultResponse('invalid','',$errorMessage);  
            }
            return $this->resultResponse('success-no-content','',[]); 
 
    }
    
    public function validatePCFName(Request $request)
    {

       if (Transaction::where('pcf_name',$request['pcf_name'])->first()){
            $errorMessage = GenericMethod::resultLaravelFormat('pcf_batch.name',["PCF name already exist."]);
            return $this->resultResponse('invalid','',$errorMessage);   
        }   
        return $this->resultResponse('success-no-content','',[]); 
    }
}
