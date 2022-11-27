<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CounterReceipt;
use App\Methods\CounterReceiptMethod;
use App\Methods\GenericMethod;
use App\Http\Requests\CounterReceiptRequest;
use App\Http\Resources\CounterReceipt as CounterReceiptResource;

class CounterReceiptController extends Controller
{
    public function index(Request $request){
        $transactions = CounterReceipt::all();
        $transactions = CounterReceiptResource::collection($transactions);
        
        if ($transactions) {
            return $this->resultResponse('fetch', 'Counter Receipt Transaction', $transactions);
        }
         return $this->resultResponse('not-found', 'Transaction', []);
    }
    
    public function store(CounterReceiptRequest $request){
        $fields = $request->validated();
        
        $is_duplicate = CounterReceiptMethod::duplicate_counter($fields);
        if($is_duplicate){
            return GenericMethod::resultResponse("upload-error","",$is_duplicate );
        }
        
        $is_created = CounterReceiptMethod::create_counter($fields);
        if($is_created){
            return GenericMethod::resultResponse("save","Transaction",[]);
        }
    }

    public function validate_receipt(Request $request){
        $supplier = $request['supplier_id'];
        $receipt_no = $request['receipt_no'];
        $is_duplicate = CounterReceiptMethod::is_duplicate_receipt($supplier, $receipt_no);
    }
}
