<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CounterReceipt;
use App\Models\Transaction;
use App\Methods\CounterReceiptMethod;
use App\Methods\GenericMethod;
use App\Http\Requests\CounterReceiptRequest;
use App\Http\Resources\CounterReceipt as CounterReceiptResource;
use App\Http\Resources\CounterReceiptIndex;
use App\Http\Resources\CounterReceiptSingleView;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CounterReceiptController extends Controller
{
    public function index(Request $request){

        $department = [];
        $dateToday = Carbon::now()->timezone('Asia/Manila');
        $status =  isset($request['state']) && $request['state'] ? $request['state'] : NULL;
        $rows =  isset($request['rows']) && $request['rows'] ? (int)$request['rows'] : 10;
        $suppliers =  isset($request['suppliers']) && $request['suppliers'] ? array_map('intval', json_decode($request['suppliers'])) : [];
        $transaction_from =  isset($request['transaction_from']) && $request['transaction_from'] ? Carbon::createFromFormat('Y-m-d', $request['transaction_from'])->startOfDay()->format('Y-m-d H:i:s')  : NULL;
        $transaction_to =  isset($request['transaction_to']) && $request['transaction_to'] ? Carbon::createFromFormat('Y-m-d', $request['transaction_to'])->endOfDay()->format('Y-m-d H:i:s')  : NULL;
        $search =  $request['search'];
        $department =  isset($request['departments']) ? array_map('intval', json_decode($request['departments'])) : [];
        $counter_receipt_status = isset($request['counter_receipt_status'])?$request['counter_receipt_status']:NULL;

        $transactions = CounterReceipt::select([
            'counter_receipts.id',
            'counter_receipts.date_countered',
            'counter_receipts.date_transaction',
            'counter_receipts.counter_receipt_no',
            'counter_receipts.receipt_type_id',
            'counter_receipts.receipt_type',
            'counter_receipts.receipt_no',
            'counter_receipts.supplier_id',
            'counter_receipts.supplier',
            'counter_receipts.department_id',
            'counter_receipts.department',
            'counter_receipts.amount',
            'counter_receipts.status',
            'counter_receipts.state',
            DB::raw("IFNULL(transactions.status, 'Unprocessed') as counter_receipt_status")
        ])
        ->when(!empty($suppliers),function($query) use ($suppliers){
            $query->whereIn('counter_receipts.supplier_id',$suppliers);
        })
        ->when(!empty($department),function($query) use ($department){
            $query->whereIn('counter_receipts.department_id',$department);
        })
        ->when(!empty($transaction_from) || !empty($transaction_to),function($query) use ($transaction_from, $transaction_to){
            $query->where('counter_receipts.date_countered','>=',$transaction_from) 
            ->where('counter_receipts.date_countered','<=',$transaction_to);
        })
        ->leftJoin('transactions', function ($join){
            $join
            ->on('counter_receipts.department_id','=','transactions.department_id')
            ->on('counter_receipts.supplier_id','=','transactions.supplier_id')
            ->on('counter_receipts.receipt_no','=','transactions.referrence_no');
        })
        ->when($counter_receipt_status, function ($query) use ($counter_receipt_status){
            $query->when(strtolower($counter_receipt_status) == strtolower("Processed"), function ($query){
                   $query->whereNotNull('transactions.status');
            },function ($query) use ($counter_receipt_status){
                    $query->when(strtolower($counter_receipt_status) == strtolower("Unprocessed"), function ($query){
                        $query->whereNull('transactions.status');
                    }, function ($query) use ($counter_receipt_status){
                        $query->where('transactions.status',$counter_receipt_status);
                    });
            });
        })
        ->where(function ($query) use ($search) {
            $query->where('counter_receipts.id', 'like', '%' . $search . '%')
            ->orWhere('counter_receipts.date_countered', 'like', '%' . $search . '%')
            ->orWhere('counter_receipts.date_transaction', 'like', '%' . $search . '%')
            ->orWhere('counter_receipts.counter_receipt_no', 'like', '%' . $search . '%')
            ->orWhere('counter_receipts.receipt_type_id', 'like', '%' . $search . '%')
            ->orWhere('counter_receipts.receipt_type', 'like', '%' . $search . '%')
            ->orWhere('counter_receipts.receipt_no', 'like', '%' . $search . '%')
            ->orWhere('counter_receipts.supplier_id', 'like', '%' . $search . '%')
            ->orWhere('counter_receipts.supplier', 'like', '%' . $search . '%')
            ->orWhere('counter_receipts.department_id', 'like', '%' . $search . '%')
            ->orWhere('counter_receipts.department', 'like', '%' . $search . '%')
            ->orWhere('counter_receipts.amount', 'like', '%' . $search . '%')
            ->orWhere('counter_receipts.status', 'like', '%' . $search . '%');
        })
        ->when($status, function ($query) use ($status){
            $query
            ->when(strtolower($status) == "pending", function($query){
                $query->whereIn('counter_receipts.state',['pending','monitoring-return'])
                ->orderByDesc('counter_receipts.id');
            }, function ($query) use ($status){
                $query->when(strtolower($status) == "monitoring-receive", function ($query) use ($status){
                    $query->whereIn('counter_receipts.state',['monitoring-receive','monitoring-unreturn']);
                }, function ($query) use ($status){
                    $query->where('counter_receipts.state',preg_replace('/\s+/', '', $status));
                });
            });
        });

        if ($transactions->count() > 0) {
            $transactions = $transactions
            ->latest('counter_receipts.updated_at')
            ->paginate($rows);
            // CounterReceiptIndex::collection($transactions);
            return GenericMethod::resultResponse('fetch', 'Counter Receipt Transaction', $transactions);
        }
         return GenericMethod::resultResponse('not-found', 'Transaction', []);
    }
    

    public function showEdit(Request $request, CounterReceipt $counter){
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

    public function showView(Request $request, CounterReceipt $counter){
        $transaction_id = CounterReceiptMethod::get_trasanction_id($counter->receipt_no,$counter->supplier_id,$counter->department_id);
        if(!$transaction_id){
                return $this->resultResponse('not-found', 'Transaction', []);
        }

        $transaction = Transaction::where('id',$transaction_id)->get();
       
        $transaction->map(function ($value) use($counter){
                $value['counter_receipt_no'] =$counter->counter_receipt_no;
        });

        $singleTransaction = CounterReceiptSingleView::collection($transaction);
        if(count($singleTransaction)!=true){
            throw new FistoException("No records found.", 404, NULL, []);
        }
        return $this->resultResponse('fetch','Counter Receipt Transaction',$singleTransaction->first());
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
