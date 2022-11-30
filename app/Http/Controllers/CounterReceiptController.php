<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CounterReceipt;
use App\Methods\CounterReceiptMethod;
use App\Methods\GenericMethod;
use App\Http\Requests\CounterReceiptRequest;
use App\Http\Resources\CounterReceipt as CounterReceiptResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CounterReceiptController extends Controller
{
    public function index(Request $request){

        $department = [];
        $dateToday = Carbon::now()->timezone('Asia/Manila');
        $status =  isset($request['state']) && $request['state'] ? $request['state'] : "request";
        $rows =  isset($request['rows']) && $request['rows'] ? (int)$request['rows'] : 10;
        $suppliers =  isset($request['suppliers']) && $request['suppliers'] ? array_map('intval', json_decode($request['suppliers'])) : [];
        $transaction_from =  isset($request['transaction_from']) && $request['transaction_from'] ? Carbon::createFromFormat('Y-m-d', $request['transaction_from'])->startOfDay()->format('Y-m-d H:i:s')  : $dateToday->startOfDay()->format('Y-m-d H:i:s');
        $transaction_to =  isset($request['transaction_to']) && $request['transaction_to'] ? Carbon::createFromFormat('Y-m-d', $request['transaction_to'])->endOfDay()->format('Y-m-d H:i:s')  : $dateToday->endOfDay()->format('Y-m-d H:i:s');
        $search =  $request['search'];
        !empty($request['department'])? $department = json_decode($request['department']): array_push($department, Auth::user()->department[0]['name']) ;
        
       
        $transactions = CounterReceipt::latest()->select([
            'id',
            'date_countered',
            'date_transaction',
            'counter_receipt_no',
            'receipt_type_id',
            'receipt_type',
            'receipt_no',
            'supplier_id',
            'supplier',
            'department_id',
            'department',
            'amount',
            'status'
        ])
        ->when(!empty($suppliers),function($query) use ($suppliers){
            $query->whereIn('supplier_id',$suppliers);
        })
        ->when(!empty($transaction_from) || !empty($transaction_to),function($query) use ($transaction_from, $transaction_to){
            $query->where('date_countered','>=',$transaction_from) 
            ->where('date_countered','<=',$transaction_to);
        })
        ->where(function ($query) use ($search) {
            $query->where('id', 'like', '%' . $search . '%')
            ->orWhere('date_countered', 'like', '%' . $search . '%')
            ->orWhere('date_transaction', 'like', '%' . $search . '%')
            ->orWhere('counter_receipt_no', 'like', '%' . $search . '%')
            ->orWhere('receipt_type_id', 'like', '%' . $search . '%')
            ->orWhere('receipt_type', 'like', '%' . $search . '%')
            ->orWhere('receipt_no', 'like', '%' . $search . '%')
            ->orWhere('supplier_id', 'like', '%' . $search . '%')
            ->orWhere('supplier', 'like', '%' . $search . '%')
            ->orWhere('department_id', 'like', '%' . $search . '%')
            ->orWhere('department', 'like', '%' . $search . '%')
            ->orWhere('amount', 'like', '%' . $search . '%')
            ->orWhere('status', 'like', '%' . $search . '%');
          
        })
        ->where('status',preg_replace('/\s+/', '', $status));
        
        // $transactions = CounterReceiptResource::collection($transactions);
        if ($transactions->count() > 0) {
            return GenericMethod::resultResponse('fetch', 'Counter Receipt Transaction', $transactions->paginate($rows));
        }
         return GenericMethod::resultResponse('not-found', 'Transaction', []);
    }
    
    public function show(Request $request, $id){

        $transaction = CounterReceipt::where('id',$id);
        $transaction_exists = $transaction->exists();
        $transaction_details = $transaction->get();

        if ($transaction_exists) {
            $transaction = CounterReceiptResource::collection($transaction_details);
            return $this->resultResponse('fetch', 'Counter Receipt Transaction', $transaction->first());
        }else{
            return $this->resultResponse('not-found', 'Transaction', []);
        }
    }

    public function store(CounterReceiptRequest $request){
        $fields = $request->validated();
        
        $is_multiple = CounterReceiptMethod::multiple_counter($request);
        $is_duplicate = CounterReceiptMethod::duplicate_counter($fields);
        if($is_multiple || $is_duplicate){
            $errors = array_merge($is_multiple, $is_duplicate);
            return GenericMethod::resultResponse("upload-error","",$errors );
        }
        
        $is_created = CounterReceiptMethod::create_counter($fields);
        if($is_created){
            return GenericMethod::resultResponse("save","Transaction",[]);
        }
    }
    

    public function update(Request $request, CounterReceipt $counter,$errors=[]){
        $is_multiple = CounterReceiptMethod::multiple_counter($request);
        $is_duplicate = CounterReceiptMethod::duplicate_counter($request,$counter->counter_receipt_no);
        if($is_multiple || $is_duplicate){
            $errors = array_merge($is_multiple, $is_duplicate);
            return GenericMethod::resultResponse("upload-error","",$errors );
        }
        
        $is_update = CounterReceiptMethod::create_counter($request,$counter->counter_receipt_no);
        if($is_update){
            return GenericMethod::resultResponse("save","Transaction",[]);
        }
    }

    public function validate_receipt(Request $request){
        $supplier = $request['supplier_id'];
        $receipt_no = $request['receipt_no'];
        $is_duplicate = CounterReceiptMethod::is_duplicate_receipt($supplier, $receipt_no);
    }
}
