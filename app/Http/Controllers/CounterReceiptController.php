<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CounterReceipt;
use App\Methods\CounterReceiptMethod;
use App\Methods\GenericMethod;
use App\Http\Requests\CounterReceiptRequest;
use App\Http\Resources\CounterReceipt as CounterReceiptResource;
use App\Http\Resources\CounterReceiptIndex;
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
        $transaction_from =  isset($request['transaction_from']) && $request['transaction_from'] ? Carbon::createFromFormat('Y-m-d', $request['transaction_from'])->startOfDay()->format('Y-m-d H:i:s')  : NULL;
        $transaction_to =  isset($request['transaction_to']) && $request['transaction_to'] ? Carbon::createFromFormat('Y-m-d', $request['transaction_to'])->endOfDay()->format('Y-m-d H:i:s')  : NULL;
        $search =  $request['search'];
        $department =  isset($request['departments']) ? array_map('intval', json_decode($request['departments'])) : [];
        $counter_receipt_status = isset($request['counter_receipt_status'])?$request['counter_receipt_status']:NULL;

        // return $counter_receipt_status;


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
            'status',
            'state'
        ])
        ->when(!empty($suppliers),function($query) use ($suppliers){
            $query->whereIn('supplier_id',$suppliers);
        })
        ->when(!empty($department),function($query) use ($department){
            $query->whereIn('department_id',$department);
        })
        ->when(!empty($transaction_from) || !empty($transaction_to),function($query) use ($transaction_from, $transaction_to){
            $query->where('date_countered','>=',$transaction_from) 
            ->where('date_countered','<=',$transaction_to);
        })
        // ->when($counter_receipt_status, function ($query) use ($counter_receipt_status){
        //     $query->when($counter_receipt_status == "Unprocessed", function ($query){
        //            $query->join('transactions', function ($join){
        //                 $join->on('counter_receipts.department_id','=','transactions.department_id')
        //                 ->on('counter_receipts.supplier_id','=','transactions.supplier_id')
        //                 ->on('counter_receipts.receipt_no','=','transactions.referrence_no');
        //            });
        //     });
        //     // $query->where('counter_receipt_status',)
        // })
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
        ->when(strtolower($status) == "pending", function($query){
            $query->whereIn('state',['pending','monitoring-return']);
        }, function ($query) use ($status){
            $query->when(strtolower($status) == "monitoring-receive", function ($query) use ($status){
                $query->whereIn('state',['monitoring-receive','monitoring-unreturn']);
            }, function ($query) use ($status){
                $query->where('state',preg_replace('/\s+/', '', $status));
            });
        });

        if ($transactions->count() > 0) {
            $transactions = $transactions
            ->latest('updated_at')
            ->paginate($rows);
            CounterReceiptIndex::collection($transactions);
            return GenericMethod::resultResponse('fetch', 'Counter Receipt Transaction', $transactions);
        }
         return GenericMethod::resultResponse('not-found', 'Transaction', []);
    }
    
    public function show(Request $request, CounterReceipt $counter){
        $transaction = CounterReceipt::where('id',$counter->id);
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

    public function update_flow_counter(Request $request, $id){
        $subprocess = $request['subprocess'];
        $is_flow_update =  CounterReceiptMethod::update_flow_counter($request, $id);

        if($is_flow_update){
            return GenericMethod::resultResponse($subprocess,"Transaction",[]);
        }

    }   

    public function validate_receipt(Request $request){
        $supplier = $request['supplier_id'];
        $receipt_no = $request['receipt_no'];
        $transaction_id = $request['transaction_id'];
        $is_duplicate = CounterReceiptMethod::is_duplicate_receipt($supplier, $receipt_no, $transaction_id);
    }

    public function update_receiver_notice(Request $request){
        return $request;
    }
}
