<?php

namespace App\Http\Controllers;

use App\Models\CounterReceipt;
use App\Models\Monitoring;
use App\Models\Transaction;

use App\Methods\CounterReceiptMethod;
use App\Methods\GenericMethod;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Http\Requests\CounterReceipt\FlowRequest;
use App\Http\Requests\CounterReceipt\StoreRequest;
use App\Http\Requests\CounterReceipt\CheckRequest;
use App\Http\Requests\CounterReceipt\DisplayRequest;
use App\Http\Requests\CounterReceipt\DownloadRequest;

class CounterReceiptController extends Controller
{
    public function index (DisplayRequest $request) {

        $state = isset($request["state"]) && !empty($request["state"]) ? $request["state"] : NULL;
        
        $suppliers =  isset($request["suppliers"]) && !empty(json_decode($request["suppliers"])) ? json_decode($request["suppliers"]) : NULL;
        $departments =  isset($request["departments"]) && !empty(json_decode($request["departments"])) ? json_decode($request["departments"]) : NULL;
        
        $transaction_from =  isset($request["from"]) && !empty($request["from"]) ? Carbon::createFromFormat('Y-m-d', $request["from"])->startOfDay()->format('Y-m-d H:i:s') : NULL;
        $transaction_to =  isset($request["to"]) && !empty($request["to"]) ? Carbon::createFromFormat('Y-m-d', $request["to"])->endOfDay()->format('Y-m-d H:i:s') : NULL;
        
        $status =  isset($request["status"]) && !empty($request["status"]) ? $request["status"] : "pending";
        $rows =  isset($request["rows"]) && !empty($request["rows"]) ? (int) $request["rows"] : 10;

        $paginate = isset($request["paginate"]) ? (int) $request["paginate"] : 1;

        $search =  $request["search"];

        $transactions = CounterReceipt::select([
            'counter_receipts.id',
            DB::raw("transactions.id as transaction_id"),
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
            'counter_receipts.receiver',
            'counter_receipts.notice_count',
            'counter_receipts.latest_notice',
            DB::raw("
                CASE
                    WHEN ISNULL(transactions.status) AND counter_receipts.status = 'received' THEN 'Unprocessed'
                    WHEN ISNULL(transactions.status) THEN counter_receipts.status
                    ELSE transactions.status
                END
                as status
            ")
        ])
        ->when($status === "pending", function ($query) {
            $query->whereIn("counter_receipts.state", ['pending', 'monitoring-return']);
        })
        ->when($status === "pending-monitoring", function ($query) {
            $query->whereIn("counter_receipts.state", ['pending']);
        })
        ->when($status === "monitoring-receive", function ($query) {
            $query->whereIn("counter_receipts.state", ['monitoring-receive', 'monitoring-unreturn']);
        })
        ->when($status !== "pending" && $status !== "pending-monitoring" && $status !== "monitoring-receive", function ($query) use ($status) {
            $query->where("counter_receipts.state", preg_replace("/\s+/", "", $status));
        })
        ->when($suppliers !== NULL, function ($query) use ($suppliers) {
            $query->whereIn("counter_receipts.supplier_id", $suppliers);
        })
        ->when($departments !== NULL, function ($query) use ($departments) {
            $query->whereIn("counter_receipts.department_id", $departments);
        })
        ->when($transaction_from !== NULL && $transaction_to !== NULL, function($query) use ($transaction_from, $transaction_to) {
            $query->where("counter_receipts.date_countered", ">=", $transaction_from) 
                  ->where("counter_receipts.date_countered", "<=", $transaction_to);
        })
        ->where(function ($query) use ($search) {
            $query->where("counter_receipts.date_countered", "like", "%" . $search . "%")
                  ->orWhere("counter_receipts.date_transaction", "like", "%" . $search . "%")
                  ->orWhere("counter_receipts.counter_receipt_no", "like", "%" . $search . "%")
                  ->orWhere("counter_receipts.receipt_type", "like", "%" . $search . "%")
                  ->orWhere("counter_receipts.receipt_no", "like", "%" . $search . "%")
                  ->orWhere("counter_receipts.supplier", "like", "%" . $search . "%")
                  ->orWhere("counter_receipts.department", "like", "%" . $search . "%")
                  ->orWhere("counter_receipts.amount", "like", "%" . $search . "%")
                  ->orWhere("counter_receipts.status", "like", "%" . $search . "%");
        })
        ->leftJoin("transactions", function ($query) {
            $query->on("counter_receipts.supplier_id", "transactions.supplier_id")
                  ->on("counter_receipts.receipt_no", "transactions.referrence_no")
                  ->on("counter_receipts.department_id", "transactions.department_id");
        })
        ->when($state, function ($query) use ($state) {
            $query
            ->when(strtolower($state) === "processed", function ($query) {
                $query->whereNotNull("transactions.status");
            })
            ->when(strtolower($state)=== "unprocessed", function ($query) {
                $query->whereNull("transactions.status");
            });
        });

        if ($transactions->count() > 0)
        {
            $transactions = $transactions->latest("counter_receipts.updated_at");

            if ($paginate) $transactions = $transactions->paginate($rows);
            else $transactions = $transactions->get();

            return GenericMethod::resultResponse("fetch", "Counter Receipt Transaction", $transactions);
        }
        
        return GenericMethod::resultResponse("not-found", "Transaction", []);
    }

    public function showCounter ($counter) {
        $data = CounterReceipt::where("counter_receipt_no", $counter)->where(function ($query) {
            $query->where("state", "pending")
                  ->orWhere("state", "monitoring-return");
        })
        ->get();

        if (count($data))
        {
            $counter = $data->first();
            $supplier = [
                "id" => $counter->supplier_id,
                "name" => $counter->supplier
            ];
    
            $receipts = [];
            foreach ($data as $receipt)
            {
                array_push($receipts, [
                    "id" => $receipt->id,
                    "department" => [
                        "id" => $receipt->department_id,
                        "name" => $receipt->department
                    ],
                    "receipt_type" => [
                        "id" => $receipt->receipt_type_id,
                        "type" => $receipt->receipt_type,
                    ],
                    "receipt_no" => $receipt->receipt_no,
                    "date_transaction" => $receipt->date_transaction,
                    "amount" => $receipt->amount
                ]);
            }
    
            $counter_receipts = [
                "no" => $counter->counter_receipt_no,
                "supplier" => $supplier,
                "remarks" => $counter->remarks,
                "counter_receipt" => $receipts 
            ];
    
            return $this->resultResponse("fetch", "Counter Receipt Transaction", $counter_receipts);
        }
        
        return $this->resultResponse('not-found', 'Counter Receipt', []);
    }

    public function showReceipt ($receipt) {
        $data = CounterReceipt::firstWhere("id", $receipt);

        if (!empty($data))
        {
            $receipt = [
                "id" => $data->id,
                "no" => $data->counter_receipt_no,
                "date_countered" => $data->date_countered,
                "supplier" => [
                    "id" => $data->supplier_id,
                    "name" => $data->supplier
                ],
                "department" => [
                    "id" => $data->department_id,
                    "name" => $data->department
                ],
                "receipt_type" => [
                    "id" => $data->receipt_type_id,
                    "type" => $data->receipt_type,
                ],
                "receipt_no" => $data->receipt_no,
                "date" => $data->date_transaction,
                "amount" => $data->amount,
                "remarks" => $data->remarks,
                "receiver" => $data->receiver,
                "memo_notice" => $data->latest_notice,
                "status" => $data->status,
                "state" => $data->state,
                "reason" => [
                    "id" => $data->reason_id,
                    "description" => $data->reason,
                    "remarks" => $data->reason_remarks
                ]
            ];

            return $this->resultResponse('fetch', 'Counter Receipt', $receipt);
        }
        
        return $this->resultResponse('not-found', 'Receipt', []);
    }


    public function store (StoreRequest $request) {
        $fields = $request->validated();

        $latest_crn = CounterReceipt::orderByDesc('id')->value('counter_receipt_no');
        $current_crn = $latest_crn ? ++$latest_crn : 1;

        foreach($fields['counter_receipt'] as $receipt) {
            $counter_receipt = CounterReceipt::create([
                "date_countered"        =>          date("Y-m-d H:i:s"),
                "counter_receipt_no"    =>          $current_crn,
                
                "supplier_id"           =>          $fields['supplier']['id'],
                "supplier"              =>          $fields['supplier']['name'],
                "remarks"               =>          $fields['remarks'],

                "receipt_type_id"       =>          $receipt['receipt_type']['id'],
                "receipt_type"          =>          $receipt['receipt_type']['type'],   
                "receipt_no"            =>          $receipt['receipt_no'],
                "amount"                =>          $receipt['amount'],
                "date_transaction"      =>          $receipt['date_transaction'],
                "department_id"         =>          $receipt['department']['id'],
                "department"            =>          $receipt['department']['name'],
                
                "state"                 =>          "pending",
                "status"                =>          "Pending"
            ]);
        }

        return GenericMethod::resultResponse("save","Transaction",[]);
    }

    public function update (StoreRequest $request, $counter) {
        $fields = $request->validated();

        $counter_receipts_in = collect($request->counter_receipt);
        $counter_receipts_db = CounterReceipt::where('counter_receipt_no', $counter)->get();

        foreach ($counter_receipts_in as $receipt)
        {
            $update = $counter_receipts_db->firstWhere('id', $receipt["id"]);

            if ($update) {
                $update->supplier_id = $request["supplier"]["id"];
                $update->supplier = $request["supplier"]["name"];
                $update->remarks = $request["remarks"];

                $update->receipt_type_id = $receipt['receipt_type']['id'];
                $update->receipt_type = $receipt['receipt_type']['type'];
                $update->receipt_no = $receipt['receipt_no'];
                $update->amount = $receipt['amount'];
                $update->date_transaction = $receipt['date_transaction'];
                $update->department_id = $receipt['department']['id'];
                $update->department = $receipt['department']['name'];

                if ($update->state === "monitoring-return")
                {
                    $update->state = "pending";
                    $update->status = "Pending";

                    $update->reason_id = NULL;
                    $update->reason = NULL;
                    $update->reason_remarks = NULL;
                }

                $update->save();
            }
            else {
                CounterReceipt::create([
                    "date_countered"        =>          date("Y-m-d H:i:s"),
                    "counter_receipt_no"    =>          $fields['no'],
                    
                    "supplier_id"           =>          $fields['supplier']['id'],
                    "supplier"              =>          $fields['supplier']['name'],
                    "remarks"               =>          $fields['remarks'],
    
                    "receipt_type_id"       =>          $receipt['receipt_type']['id'],
                    "receipt_type"          =>          $receipt['receipt_type']['type'],   
                    "receipt_no"            =>          $receipt['receipt_no'],
                    "amount"                =>          $receipt['amount'],
                    "date_transaction"      =>          $receipt['date_transaction'],
                    "department_id"         =>          $receipt['department']['id'],
                    "department"            =>          $receipt['department']['name'],
                    
                    "state"                 =>          "pending",
                    "status"                =>          "Pending"
                ]);
            }
        }
        
        $difference = array_diff(
            $counter_receipts_db->pluck('id')->toArray(),
            $counter_receipts_in->pluck('id')->toArray()
        );

        if (count($difference))
        {
            foreach ($difference as $id)
            {
                $delete = $counter_receipts_db->where("id", $id)
                                              ->filter(function ($query) {
                                                    return $query->state === "pending" || $query->state === "monitoring-return";
                                              })
                                              ->first();

                if ($delete) $delete->delete();
            }
        }

        return GenericMethod::resultResponse("save","Transaction",[]);
    }

    public function download (DownloadRequest $request) {

        $notice = $request->with_memo;
        $counter_receipts_in = $request->counter_receipts;
        $counter_receipts_db = CounterReceipt::whereIn("id", array_column($counter_receipts_in, "id"))->get();

        foreach ($counter_receipts_db as $counter)
        {
            $receipt = (object) collect($counter_receipts_in)->firstWhere("id", $counter->id);

            $counter->receiver = $receipt->receiver;
            $counter->notice_count = $notice ? ++$counter->notice_count : $counter->notice_count;
            $counter->latest_notice = $notice ? date("Y-m-d H:i:s") : $counter->latest_notice;

            $counter->save();
        }

        return GenericMethod::resultResponse("counter-save", "Counter Receipt Transaction", []);
    }


    public function flow (FlowRequest $request, $id) {
        $counter_receipt = CounterReceipt::where("id", $id);

        if ($counter_receipt->count())
        {
            switch ($request["process"])
            {
                case "counter":
                    if ($request["subprocess"] === "void")
                    {
                        $update = $counter_receipt->update([
                            "status" => "Voided",
                            "state" => "counter-void",
    
                            "reason_id" => $request['reason']['id'],
                            "reason" => $request['reason']['description'],
                            "reason_remarks" => $request['reason']['remarks']
                        ]);
                    }
    
                    break;
    
                case "monitoring":
                    if ($request["subprocess"] === "receive")
                    {
                        $update = $counter_receipt->update([
                            "status" => "Received",
                            "state" => "monitoring-receive"
                        ]);
                    }
                    
                    if ($request["subprocess"] === "return")
                    {
                        $update = $counter_receipt->update([
                            "status" => "Returned",
                            "state" => "monitoring-return",
    
                            "reason_id" => $request['reason']['id'],
                            "reason" => $request['reason']['description'],
                            "reason_remarks" => $request['reason']['remarks']
                        ]);
                    }
                    
                    if ($request["subprocess"] === "unreturn")
                    {
                        $update = $counter_receipt->update([
                            "status" => "Received",
                            "state" => "monitoring-unreturn",
    
                            "reason_id" => NULL,
                            "reason" => NULL,
                            "reason_remarks" => NULL
                        ]);
                    }
    
                    break;
    
                default:
            }

            if ($update) $this->counter_logger($counter_receipt->first());

            return GenericMethod::resultResponse($request["subprocess"], "Counter Receipt", []);
        }

        return $this->resultResponse('not-found', 'Counter Receipt', []);
    }

    public function check (CheckRequest $request) {
        return GenericMethod::resultResponse("available", "Receipt Number", []);
    }


    // logger
    private function counter_logger ($counter) {
        Monitoring::create([
            "counter_id"        =>          $counter->id,
            "receipt_no"        =>          $counter->receipt_no,
            "status"            =>          $counter->status,
            "state"             =>          $counter->state,
            "receiver"          =>          $counter->receiver,
            "notice_count"      =>          $counter->notice_count,
            "latest_notice"     =>          $counter->latest_notice,
            "reason_id"         =>          $counter->reason_id,
            "reason"            =>          $counter->reason,
            "reason_remarks"    =>          $counter->reason_remarks
        ]);
    }
}
