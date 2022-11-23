<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\PODetailsRequest;
use App\Methods\PADValidationMethod;
use App\Methods\GenericMethod;
use App\Models\Transaction;
use App\Models\POBatch;
use App\Models\Tagging;
use App\Models\RequestorLogs;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\TransactionIndex;
use App\Http\Resources\RequestLog;
use App\Exceptions\FistoException;
use Carbon\Carbon;


use App\Http\Requests\TransactionPostRequest;

class TransactionController extends Controller
{

    public function showUserDepartment(){
        $departments = Auth::user()->department;
        if (count($departments)) return $this->resultResponse('fetch', 'Departments', array("departments"=>$departments));
        return $this->resultResponse('not-found', 'Transaction', []);
    }
    public function index(Request $request)
    {
       $dateToday = Carbon::now()->timezone('Asia/Manila');
       
        $department = [];
        $users_id = Auth::user()->id;
        $role = Auth::user()->role;
        $status =  isset($request['state']) && $request['state'] ? $request['state'] : "request";
        $rows =  isset($request['rows']) && $request['rows'] ? (int)$request['rows'] : 10;
        $suppliers =  isset($request['suppliers']) && $request['suppliers'] ? array_map('intval', json_decode($request['suppliers'])) : [];
        $document_ids =  isset($request['document_ids']) && $request['document_ids'] ? array_map('intval', json_decode($request['document_ids'])) : [];
        $transaction_from =  isset($request['transaction_from']) && $request['transaction_from'] ? Carbon::createFromFormat('Y-m-d', $request['transaction_from'])->startOfDay()->format('Y-m-d H:i:s')  : $dateToday->startOfDay()->format('Y-m-d H:i:s');
        $transaction_to =  isset($request['transaction_to']) && $request['transaction_to'] ? Carbon::createFromFormat('Y-m-d', $request['transaction_to'])->endOfDay()->format('Y-m-d H:i:s')  : $dateToday->endOfDay()->format('Y-m-d H:i:s');
        $search =  $request['search'];
        $state = isset($request['state'])? $request['state']: 'request';
        !empty($request['department'])? $department = json_decode($request['department']): array_push($department, Auth::user()->department[0]['name']) ;

        $request_window = ['Requestor'];
        $admin_window = ['Administrator'];
        $tag_window = ['AP Tagging'];
        $voucher_window = ['AP Associate','AP Specialist'];
        $approve_window = ['Approver'];
        $cheque_window = ['Treasury Associate'];

        $transactions = Transaction::select([
            'id',
            'company_id'
        ])
        ->with('users', function ($query) {
            return $query->select(['id', 'first_name', 'middle_name', 'last_name', 'users.department', 'position']);
        })
        ->with('supplier.supplier_type', function ($query) {
            return $query->select(['id', 'type as name','transaction_days']);
        })
        ->with('po_details', function ($query) {
            return $query->select(['id', 'request_id', 'po_no', 'po_total_amount']);
        })
        ->when(!empty($document_ids),function($query) use ($document_ids){
            $query->whereIn('document_id',$document_ids);
        })
        ->when(!empty($suppliers),function($query) use ($suppliers){
            $query->whereIn('supplier_id',$suppliers);
        })
        ->when(!empty($document_ids) || !empty($suppliers),function($query) use ($transaction_from, $transaction_to){
            $query->where('date_requested','>=',$transaction_from) 
            ->where('date_requested','<=',$transaction_to);
        })
        ->where(function ($query) use ($search) {
            $query->where('date_requested', 'like', '%' . $search . '%')
            ->orWhere('remarks', 'like', '%' . $search . '%')
            ->orWhere('transaction_id', 'like', '%' . $search . '%')
            ->orWhere('document_amount', 'like', '%' . $search . '%')
            ->orWhere('document_type', 'like', '%' . $search . '%')
            ->orWhere('payment_type', 'like', '%' . $search . '%')
            ->orWhere('company', 'like', '%' . $search . '%')
            ->orWhere('department', 'like', '%' . $search . '%')
            ->orWhere('location', 'like', '%' . $search . '%')
            ->orWhere('supplier', 'like', '%' . $search . '%')
            ->orWhere('document_no', 'like', '%' . $search . '%')
            ->orWhere('referrence_no', 'like', '%' . $search . '%')
            ->orWhere('po_total_amount', 'like', '%' . $search . '%')
            ->orWhere('referrence_total_amount', 'like', '%' . $search . '%')
            ->orWhereHas('po_details', function ($query) use ($search){
                $query->where('po_no', 'like', '%' . $search . '%');
            })
            ->orWhereHas('users',function ($query) use ($search){
                $query->where(DB::raw(
                    "REPLACE(
                        CONCAT(
                            COALESCE(first_name,''),' ',
                            COALESCE(last_name,''),
                            COALESCE(suffix,'')
                        ),
                    '  ',' ')"
                ),
            'like', '%' . $search . '%');
            });
        })
        ->when(in_array($role,$request_window),function($query) use($status,$department){
            $query->when(strtoupper($status) == "PENDING", function ($query){
                $query->whereNotIn('status',['requestor-void','tag-return']);
            },function ($query) use($status){
                $query->when(strtolower($status) == "return-return", function ($query) use ($status){
                    $query->whereIn('status',['tag-return']);
                },function ($query) use ($status){
                    $query->when(strtolower($status) == "return-hold", function ($query) use ($status){
                        $query->whereIn('status',['tag-hold']);
                     },function ($query) use($status){
                        $query->when(strtolower($status) == "return-void", function ($query) use ($status){
                            $query->whereIn('status',['tag-void']);
                        },function ($query) use($status){
                            $query->where('status',preg_replace('/\s+/', '', $status));
                        });
                    });
                });
            })
            ->whereIn('department_details',$department)
            ->select([
                'id',
                'users_id',
                'request_id',
                'supplier_id',
                'document_id',
                'tag_no',
                
                'transaction_id',
                'document_type',
                'payment_type',
                'remarks',
                'date_requested',
    
                'company_id',
                'company',
                'department',
                'location',
    
                'document_no',
                'document_amount',
                'referrence_no',
                'referrence_amount',
    
                'status',
                'state'
            ]);
        })
        ->when(in_array($role,$tag_window),function($query) use ($status){
            $query
            ->when(strtolower($status) == "tag-receive", function ($query) {
                $query->whereIn('status',['tag-receive','tag-unhold','tag-unreturn']);
                }, function ($query) use ($status) {
                    $query->when(strtolower($status) == "pending", function ($query){
                        $query->whereIn('status',['pending']);
                    },function ($query) use ($status){
                        $query->when(strtolower($status) == "pending-cheque",function ($query) use ($status){
                            $query->whereIn('status',['cheque-release']);
                        }, function ($query) use($status){
                            $query->when(strtolower($status) == "pending-file",function($query){
                                $query->whereIn('status',['file-file']);
                            },function ($query) use($status){
                                $query->when(strtolower($status) == 'reverse-request',function ($query) use ($status){
                                    $query->whereIn('status',['reverse-request']);
                                },function ($query) use($status){
                                    $query->when(strtolower($status) == "return-return", function ($query) use ($status){
                                        $query->whereIn('status',['voucher-return']);
                                    },function ($query) use ($status){
                                        $query->when(strtolower($status) == "return-hold", function ($query) use ($status){
                                            $query->whereIn('status',['voucher-hold']);
                                         },function ($query) use($status){
                                            $query->when(strtolower($status) == "return-void", function ($query) use ($status){
                                                $query->whereIn('status',['voucher-void']);
                                            },function ($query) use($status){
                                                $query->where('status',preg_replace('/\s+/', '', $status));
                                            });
                                        });
                                    });
                                });
                            });
                        });
                    });
                })
            ->select([
                'id',
                'users_id',
                'request_id',
                'supplier_id',
                'document_id',
                'tag_no',
                
                'transaction_id',
                'document_type',
                'payment_type',
                'remarks',
                'date_requested',
    
                'company_id',
                'company',
                'department',
                'location',
    
                'document_no',
                'document_amount',
                'referrence_no',
                'referrence_amount',
    
                'status',
                'state'
            ]);
        })
        ->when(in_array($role,$voucher_window),function($query) use ($users_id,$status){
            $query->when(strtolower($status) == "voucher-receive", function ($query) {
                $query->whereIn('status',['voucher-receive','voucher-unhold','voucher-unreturn']);
            }, function ($query) use ($users_id, $status) {
                $query->when(strtolower($status) == "pending", function ($query){
                    $query->whereIn('status',['tag-tag']);
                },function ($query) use($users_id, $status){
                    $query->when(strtolower($status) == "pending-transmit", function ($query){
                        $query->whereIn('status',['approve-approve']);
                    }, function ($query) use ($users_id, $status){
                        $query->when(strtolower($status) == "pending-file", function ($query){
                            $query->whereIn('status',['release-release']);
                        },function ($query) use ($users_id, $status){
                            $query->when(strtolower($status) == "pending-request",function($query) use ($users_id){
                                $query->whereIn('status',['reverse-request']);
                            },function($query) use($users_id, $status){
                                $query->when(strtolower($status) == "reverse-receive-approver", function($query){
                                    $query->whereIn('status',['reverse-receive-approver']);
                                },function ($query) use($status){
                                    $query->when(strtolower($status) == "return-return", function ($query) use ($status){
                                        $query->whereIn('status',['cheque-return','approve-return']);
                                    },function ($query) use ($status){
                                        $query->when(strtolower($status) == "return-hold", function ($query) use ($status){
                                            $query->whereIn('status',['cheque-hold','approve-hold']);
                                         },function ($query) use($status){
                                            $query->when(strtolower($status) == "return-void", function ($query) use ($status){
                                                $query->whereIn('status',['cheque-void','approve-void']);
                                            },function ($query) use($status){
                                                $query->where('status',preg_replace('/\s+/', '', $status));
                                            });
                                        });
                                    });
                                });
                            });
                        });
                    });
                });
            })
            ->select([
                'id',
                'users_id',
                'request_id',
                'supplier_id',
                'document_id',
                'tag_no',
                
                'transaction_id',
                'document_type',
                'payment_type',
                'remarks',
                'date_requested',
    
                'company_id',
                'company',
                'department',
                'location',
    
                'document_no',
                'document_amount',
                'referrence_no',
                'referrence_amount',
    
                'status',
                'state',

                'distributed_id',
                'distributed_name'
            ])
            ->when(in_array(strtolower($status),["pending-request","reverse-receive-approver","reverse-approve"]), function($query) use($users_id){
                $query->where('reverse_distributed_id', $users_id);
            }, function ($query) use($users_id){
                $query->where('distributed_id',$users_id);
            });
            
        })
        ->when(in_array($role,$approve_window),function($query) use ($users_id,$status){
            $query->when(strtolower($status) == "approve-receive", function ($query) {
                $query->whereIn('status',['approve-receive','approve-unhold','approve-unreturn']);
            }, function ($query) use ($status) {
                $query->when(strtolower($status) == "pending", function ($query){
                    $query->whereIn('status',['voucher-voucher']);
                },function ($query) use($status){
                    $query->where('status',preg_replace('/\s+/', '', $status));
                });
            })
            ->select([
                'id',
                'users_id',
                'request_id',
                'supplier_id',
                'document_id',
                'tag_no',
                
                'transaction_id',
                'document_type',
                'payment_type',
                'remarks',
                'date_requested',
    
                'company_id',
                'company',
                'department',
                'location',
    
                'document_no',
                'document_amount',
                'referrence_no',
                'referrence_amount',

                'approver_id',
                'approver_name',
    
                'status',
                'state'
            ])
            ->where('approver_id',$users_id);
        })
        ->when(in_array($role,$cheque_window),function($query) use ($status){
            $query->when(strtolower($status) == "cheque-receive", function ($query) {
                $query->whereIn('status',['cheque-receive','cheque-unhold','cheque-unreturn']);
            }, function($query) use ($status){
                $query->when(strtolower($status) == "cheque-cheque", function($query){
                    $query->whereIn('status',['cheque-cheque','cheque-reverse']);
                }, function ($query) use ($status){
                    $query->when(strtolower($status) == "pending", function ($query){
                        $query->whereIn('status',['transmit-transmit']);
                    },function ($query) use($status){
                        $query->when(strtolower($status) == "return-return", function ($query) use ($status){
                            $query->whereIn('status',['release-return','reverse-return']);
                        },function ($query) use ($status){
                            $query->when(strtolower($status) == "return-hold", function ($query) use ($status){
                                $query->whereIn('status',['release-hold']);
                             },function ($query) use($status){
                                $query->when(strtolower($status) == "return-void", function ($query) use ($status){
                                    $query->whereIn('status',['release-void']);
                                },function ($query) use($status){
                                    $query->where('status',preg_replace('/\s+/', '', $status));
                                });
                            });
                        });
                    });
                });
            })
            ->select([
                'id',
                'users_id',
                'request_id',
                'supplier_id',
                'document_id',
                'tag_no',
                
                'transaction_id',
                'document_type',
                'payment_type',
                'remarks',
                'date_requested',
    
                'company_id',
                'company',
                'department',
                'location',
    
                'document_no',
                'document_amount',
                'referrence_no',
                'referrence_amount',
    
                'status',
                'state'
            ]);
        })
        ->latest('updated_at')
        ->paginate($rows);

        TransactionIndex::collection($transactions);

        if (count($transactions)) return $this->resultResponse('fetch', 'Transaction', $transactions);
        return $this->resultResponse('not-found', 'Transaction', []);
    }

    public function showTransaction($id)
    {
        // $transaction = DB::table('transactions')->where('id',$id)->first();
        $transaction = Transaction::where('id',$id)->get();
        $singleTransaction = TransactionResource::collection($transaction);
        if(count($singleTransaction)!=true){
            throw new FistoException("No records found.", 404, NULL, []);
        }
        return $this->resultResponse('fetch','Transaction details',$singleTransaction->first());
    }

    public function showCurrentPO($id)
    {
        $transaction = Transaction::where('id',$id)->get();
        $singleTransaction = TransactionResource::collection($transaction);
        if(count($singleTransaction)!=true){
            throw new FistoException("No records found.", 404, NULL, []);
        }
        return $singleTransaction->first();
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
                GenericMethod::documentNoValidation($request['document']['no']);

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
               
                $errorMessage = GenericMethod::validateWith1PesoDifference('po_group.amount','Document',$fields['document']['amount'],$po_total_amount);
                if(! empty($errorMessage)){
                    return GenericMethod::resultResponse('invalid','',$errorMessage);
                }
                GenericMethod::insertPO($request_id,$fields['po_group'],$po_total_amount,strtoupper($fields['document']['payment_type']));
                $transaction = GenericMethod::insertTransaction($transaction_id,$po_total_amount,
                $request_id,$date_requested,$fields);
                if(isset($transaction->transaction_id)){
                   return $this->resultResponse('save','Transaction',[]);
                }
            break;

            case 2: //PRM Common
                GenericMethod::documentNoValidation($request['document']['no']);
                $transaction = GenericMethod::insertTransaction($transaction_id,NULL,
                $request_id,$date_requested,$fields);
                if(isset($transaction->transaction_id)){
                   return $this->resultResponse('save','Transaction',[]);
                }
            break;

            case 3: // PRM Multiple
                GenericMethod::documentNoValidation($request['document']['no']);
                $transaction = GenericMethod::insertTransaction($transaction_id,NULL,
                $request_id,$date_requested,$fields);
                
                if(isset($transaction->transaction_id)){
                   return $this->resultResponse('save','Transaction',[]);
                }
                return $transaction;
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
                
                

                if(!$isQty && $isFull){

                    $duplicateRef = GenericMethod::validateReferenceNo($fields);
                    if(isset($duplicateRef)){
                        return $this->resultResponse('invalid','',$duplicateRef);   
                    }
                    
                    $duplicatePO = GenericMethod::validatePOFull($fields['document']['company']['id'],$fields['po_group']);
                    if(isset($duplicatePO)){
                        return $this->resultResponse('invalid','',$duplicatePO);
                    }
                    
                    $po_total_amount = GenericMethod::getPOTotalAmount($request_id,$fields['po_group']);
                    
                    $errorMessage = GenericMethod::validateWith1PesoDifference('document.amount','Reference',$fields['document']['reference']['amount'],$po_total_amount);
                    if(! empty($errorMessage)){
                        return GenericMethod::resultResponse('invalid','',$errorMessage);
                    }

                    GenericMethod::insertPO($request_id,$fields['po_group'],$po_total_amount,strtoupper($fields['document']['payment_type']));
                    $transaction = GenericMethod::insertTransaction($transaction_id,$po_total_amount,
                    $request_id,$date_requested,$fields);
                    if(isset($transaction->transaction_id)){
                       return $this->resultResponse('save','Transaction',[]);
                    }
                }

                
                if(!$isQty && !$isFull){
                    $duplicateRef = GenericMethod::validateReferenceNo($fields);
                    if(isset($duplicateRef)){
                        return $this->resultResponse('invalid','',$duplicateRef);   
                    }
                    
                    $fields['po_group'] =  GenericMethod::ValidateIfPOExists($fields['po_group'],$fields['document']['company']['id']);
                    $getAndValidatePOBalance = GenericMethod::getAndValidatePOBalance($fields['document']['company']['id'],last($fields['po_group'])['no'],$fields['document']['reference']['amount'],$fields['po_group']);
                    if(gettype($getAndValidatePOBalance) == 'object'){
                        return $this->resultResponse('invalid','',$getAndValidatePOBalance);  
                    }
                    if(gettype($getAndValidatePOBalance) == 'array'){ //Additional PO Validation
                        $new_po= $getAndValidatePOBalance['new_po_group'];
                        $po_total_amount= $getAndValidatePOBalance['po_total_amount'];
                        $balance_with_additional_total_po_amount= $getAndValidatePOBalance['balance'];
                       GenericMethod::insertPO($request_id,$fields['po_group'],$po_total_amount,strtoupper($fields['document']['payment_type']));
                       $transaction = GenericMethod::insertTransaction($transaction_id,$po_total_amount,
                       $request_id,$date_requested,$fields,$balance_with_additional_total_po_amount);
                       if(isset($transaction->transaction_id)){
                          return $this->resultResponse('save','Transaction',[]);
                       }
                    }
                    
                    $po_total_amount = GenericMethod::getPOTotalAmount($request_id,$fields['po_group']);
                    $balance_po_ref_amount = $po_total_amount - $fields['document']['reference']['amount'];
                    
                    if($po_total_amount < $fields['document']['reference']['amount']){
                        $amountValdiation =  GenericMethod::resultLaravelFormat('document.reference.no',["Insufficient PO balance."]);
                        return $this->resultResponse('invalid','',$amountValdiation);  
                    }
                    
                    if(isset($getAndValidatePOBalance)){
                        $balance_po_ref_amount = $getAndValidatePOBalance;
                    }
                    GenericMethod::insertPO($request_id,$fields['po_group'],$po_total_amount,strtoupper($fields['document']['payment_type']));
                    $transaction = GenericMethod::insertTransaction($transaction_id,$po_total_amount,
                    $request_id,$date_requested,$fields,$balance_po_ref_amount);
                    if(isset($transaction->transaction_id)){
                       return $this->resultResponse('save','Transaction',[]);
                    }
                }

            break;
        }
        return $this->resultResponse('not-exist','Document number',[]);
    }
    
    public function update (TransactionPostRequest $request, $id)
    {
        $fields=$request->validated();
        $date_requested = date('Y-m-d H:i:s');
        $request_id = $request['transaction']['request_id'];

        switch($fields['document']['id']){
            case 1: //PAD
            case 5: //Contractor's Billing
        
                GenericMethod::documentNoValidationUpdate($request['document']['no'],$id);

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

                $errorMessage = GenericMethod::validateWith1PesoDifference('po_group.amount','Document',$fields['document']['amount'],$po_total_amount);
                if(! empty($errorMessage)){
                    return GenericMethod::resultResponse('invalid','',$errorMessage);
                }
                
                $changes = GenericMethod::getTransactionChanges($request_id,$request,$id);
                GenericMethod::updatePO($request_id,$fields['po_group'],$po_total_amount,strtoupper($fields['document']['payment_type']),$id);

                $transaction = GenericMethod::updateTransaction($id,$po_total_amount,
                $request_id,$date_requested,$request,0,$changes);

                if($transaction == "Nothing Has Changed"){
                    return $this->resultResponse('nothing-has-changed',"Transaction",[]);
                }
                if(isset($transaction->transaction_id)){
                    return $this->resultResponse('update','Transaction',[]);
                }
            break;
            
            case 2: //PRM Common
                $po_total_amount=NULL;
               GenericMethod::documentNoValidationUpdate($request['document']['no'],$id);
               
               $changes = GenericMethod::getTransactionChanges($request_id,$request,$id);

               $transaction = GenericMethod::updateTransaction($id,$po_total_amount,
               $request_id,$date_requested,$request,0,$changes);

               if($transaction == "Nothing Has Changed"){
                   return $this->resultResponse('nothing-has-changed',"Transaction",[]);
               }
               if(isset($transaction->transaction_id)){
                  return $this->resultResponse('update','Transaction',[]);
               }
            break;

            case 6: //Utilities
                $po_total_amount=NULL;
                $duplicateUtilities = GenericMethod::validateTransactionByDateRange(
                    $fields['document']['from']
                    ,$fields['document']['to']
                    ,$fields['document']['company']['id']
                    ,$fields['document']['department']['id']
                    ,$fields['document']['utility']['location']['id']
                    ,$fields['document']['utility']['category']['name']
                    ,$id
                );
                if(isset($duplicateUtilities)){
                    return $this->resultResponse('invalid','',$duplicateUtilities);
                }
                $changes = GenericMethod::getTransactionChanges($request_id,$request,$id);

                $transaction = GenericMethod::updateTransaction($id,$po_total_amount,
                $request_id,$date_requested,$request,0,$changes);
                if(isset($transaction->transaction_id)){
                   return $this->resultResponse('update','Transaction',[]);
                }
            break;
            
            case 7: //Payroll
                $po_total_amount=NULL;
                $duplicatePayroll = GenericMethod::validatePayroll(
                    $fields['document']['from']
                    ,$fields['document']['to']
                    ,$fields['document']['company']['id']
                    ,$fields['document']['location']['id']
                    ,$fields['document']['supplier']['id']
                    ,$fields['document']['payroll']['clients']
                    ,$fields['document']['payroll']['type']
                    ,$fields['document']['payroll']['category']['name']
                    ,$id
                );

                if(isset($duplicatePayroll)){
                    return $this->resultResponse('invalid','',$duplicatePayroll);
                }
                $changes = GenericMethod::getTransactionChanges($request_id,$request,$id);
                GenericMethod::updateClients($request_id,$fields['document']['payroll']['clients'],$id);
                $transaction = GenericMethod::updateTransaction($id,$po_total_amount,
                $request_id,$date_requested,$request,0,$changes);
                if(isset($transaction->transaction_id)){
                   return $this->resultResponse('update','Transaction',[]);
                }
            break;

            case 8: //PCF
                $po_total_amount=NULL;
                $duplicatePCF = GenericMethod::validatePCF(
                    $fields['document']['pcf_batch']['name']
                    ,$fields['document']['pcf_batch']['date']
                    ,$fields['document']['pcf_batch']['letter']
                    ,$fields['document']['company']['id']
                    ,$fields['document']['supplier']['id']
                    ,$id
                );
                if(isset($duplicatePCF)){
                    return $this->resultResponse('invalid','',$duplicatePCF);
                }
                $changes = GenericMethod::getTransactionChanges($request_id,$request,$id);

                $transaction = GenericMethod::updateTransaction($id,$po_total_amount,
                $request_id,$date_requested,$request,0,$changes);
                if(isset($transaction->transaction_id)){
                   return $this->resultResponse('update','Transaction',[]);
                }
            break;

            case 4: //Receipt
                $isFull = strtoupper($fields['document']['payment_type']) === 'FULL';
                
                if (empty($fields['po_group']) ) 
                {
                    $errorMessage = GenericMethod::resultLaravelFormat('po_group',["PO group required"]);
                    return $this->resultResponse('invalid','',$errorMessage);
                }
                
                $duplicateRef = GenericMethod::validateReferenceNo($fields,$id);
                if(isset($duplicateRef)){
                    return $this->resultResponse('invalid','',$duplicateRef);   
                }

                if($isFull){
                    $duplicatePO = GenericMethod::validatePOFullUpdate($fields['document']['company']['id'],$fields['po_group'],$id);
                    if(isset($duplicatePO)){
                        return $this->resultResponse('invalid','',$duplicatePO);
                    }
                    
                    $po_total_amount = GenericMethod::getPOTotalAmount($request_id,$fields['po_group']);

                    $errorMessage = GenericMethod::validateWith1PesoDifference('document.amount','Reference',$fields['document']['reference']['amount'],$po_total_amount);
                    if(! empty($errorMessage)){
                        return GenericMethod::resultResponse('invalid','',$errorMessage);
                    }
                    
                    $changes = GenericMethod::getTransactionChanges($request_id,$request,$id);
                    GenericMethod::updatePO($request_id,$fields['po_group'],$po_total_amount,strtoupper($fields['document']['payment_type']),$id);
                    $transaction = GenericMethod::updateTransaction($id,$po_total_amount,
                    $request_id,$date_requested,$request,0,$changes);
                    // if($transaction == "Nothing Has Changed"){
                    //     return $this->resultResponse('nothing-has-changed',"Transaction",[]);
                    // }
                    if(isset($transaction->transaction_id)){
                        return $this->resultResponse('update','Transaction',[]);
                    }
                }

                $fields['po_group'] =  GenericMethod::ValidateIfPOExists($fields['po_group'],$fields['document']['company']['id'],$id);
                $getAndValidatePOBalance = GenericMethod::getAndValidatePOBalance($fields['document']['company']['id'],last($fields['po_group'])['no'],$fields['document']['reference']['amount'],$fields['po_group'],$id);
                if(gettype($getAndValidatePOBalance) == 'object'){
                    return $this->resultResponse('invalid','',$getAndValidatePOBalance);  
                }

                if(gettype($getAndValidatePOBalance) == 'array'){ //Additional PO Validation
                    $new_po= $getAndValidatePOBalance['new_po_group'];
                    $po_total_amount= $getAndValidatePOBalance['po_total_amount'];
                    $balance_with_additional_total_po_amount= $getAndValidatePOBalance['balance'];

                    $changes = GenericMethod::getTransactionChanges($request_id,$request,$id);
                    GenericMethod::updatePO($request_id,$fields['po_group'],$po_total_amount,strtoupper($fields['document']['payment_type']),$id);
                    $transaction = GenericMethod::updateTransaction($id,$po_total_amount,
                    $request_id,$date_requested,$request,$balance_with_additional_total_po_amount,$changes);
                    if(isset($transaction->transaction_id)){
                        return $this->resultResponse('update','Transaction',[]);
                    }
                }
                $po_total_amount = GenericMethod::getPOTotalAmount($request_id,$fields['po_group']);
                $balance_po_ref_amount = $po_total_amount - $fields['document']['reference']['amount'];
                    
                if($po_total_amount < $fields['document']['reference']['amount']){
                    $amountValdiation =  GenericMethod::resultLaravelFormat('document.reference.no',["Insufficient PO balance."]);
                    return $this->resultResponse('invalid','',$amountValdiation);  
                }
                
                if(isset($getAndValidatePOBalance)){
                    $balance_po_ref_amount = $getAndValidatePOBalance;
                }

                $changes = GenericMethod::getTransactionChanges($request_id,$request,$id);
                GenericMethod::updatePO($request_id,$fields['po_group'],$po_total_amount,strtoupper($fields['document']['payment_type']),$id);
                $transaction = GenericMethod::updateTransaction($id,$po_total_amount,
                $request_id,$date_requested,$request,$balance_po_ref_amount,$changes);
                if(isset($transaction->transaction_id)){
                    return $this->resultResponse('update','Transaction',[]);
                }

            break;
        }

        return $this->resultResponse('not-exist','Document number',[]);
    }

    public function getPODetails(PODetailsRequest $request)
    {
        $transaction_id = $request->transaction_id;
        $fields = $request->validated();
        $po_details = DB::table('transactions')
        ->leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
        ->where('transactions.company_id',$fields['company_id'])
        ->where('p_o_batches.po_no',$fields['po_no'])
        ->where('transactions.state','!=','void')
        ->when(isset($transaction_id),function($query) use($transaction_id){
          $query->where('transactions.id','<>',$transaction_id);
        })
        ->get(['balance_po_ref_amount as po_balance','transactions.request_id']);


        if(count($po_details)>0){
            if(strtoupper($fields['payment_type'])=="FULL"){
                $errorMessage = GenericMethod::resultLaravelFormat('po_group.no',["PO number already exist."]);
                return $this->resultResponse('invalid','',$errorMessage);   
            }

            if(($po_details->last()->po_balance <= 0) || ($po_details->last()->po_balance == null) ){
                
                $errorMessage = GenericMethod::resultLaravelFormat('po_group.no',["No available balance."]);
                return $this->resultResponse('invalid','',$errorMessage);   
            }
            $po_group = collect();
            $balance =  $po_details->last()->po_balance;
            $po_details = POBatch::where('request_id',$po_details->last()->request_id)->orderByDesc('id')->get(['request_id as batch','po_no as no','po_amount as amount','rr_group as rr_no']);
            
            $po_details->mapToGroups(function ($item,$v) use ($balance){
                return [
                    $item['balance']=0,
                    $item['rr_no']=json_decode($item['rr_no'], true),
                ];
            });
            
            $po_details = $po_details->reverse()->values();
            $po_details->first()->balance = $balance;
            $po_object =  (object) array("po_group"=>$po_details);
            return $this->resultResponse('fetch','PO number',$po_object);   
        }
        return $this->resultResponse('success-no-content','',[]);  
    }

    public function validateDocumentNo(Request $request)
    {
       $transaction_id = $request->transaction_id;
        
       if (Transaction::where('document_no',$request['document_no'])
       ->when(isset($transaction_id),function($query) use($transaction_id){
         $query->where('id','<>',$transaction_id);
       })
       ->where('state','!=','void')
       ->first()){
            $errorMessage = GenericMethod::resultLaravelFormat('document.no',["Document number already exist."]);
            return $this->resultResponse('invalid','',$errorMessage);   
        }   
        return $this->resultResponse('success-no-content','',[]); 
    }

    public function validateReferenceNo(Request $request)
    {
       $transaction_id = $request->transaction_id;

       if(Transaction::where('company_id',$request['company_id'])
            ->where('referrence_no',$request['reference_no'])
            ->where('state','!=','void')
            ->when(isset($transaction_id),function($query) use($transaction_id){
                $query->where('id','<>',$transaction_id);
            })
            ->first()){
                $errorMessage = GenericMethod::resultLaravelFormat('document.reference.no',["Reference number already exist."]);
                return $this->resultResponse('invalid','',$errorMessage);  
            }
            return $this->resultResponse('success-no-content','',[]); 
 
    }
    
    public function validatePCFName(Request $request)
    {
       $transaction_id = $request->transaction_id;
       if (Transaction::where('pcf_name',$request['pcf_name'])
       ->where('state','!=','void')
       ->when(isset($transaction_id),function($query) use($transaction_id){
         $query->where('transactions.id','<>',$transaction_id);
       })
       ->exists()){
            $errorMessage = GenericMethod::resultLaravelFormat('pcf_batch.name',["PCF name already exist."]);
            return $this->resultResponse('invalid','',$errorMessage);   
        }   
        return $this->resultResponse('success-no-content','',[]); 
    }

    public function voidTransaction(Request $request,$id){

       $transaction = Transaction::where('id',$id)->where('state','!=','void')->first();
       $date_requested = date('Y-m-d H:i:s');
       $status="void";

       if(!isset($transaction)){
        return $this->resultResponse('not-found', 'Transaction', []);
       }
      
       $transaction->status = 'requestor-void';
       $transaction->state = 'void';
       $transaction->reason_id = $request->id;
       $transaction->reason = $request->description;
       $transaction->reason_remarks = $request->remarks;
       $transaction->save();
       
       GenericMethod::insertRequestorLogs($id,$transaction->transaction_id,$date_requested,$transaction->remarks,
       $transaction->users_id,$status,$request->id,$request->description,$request->remarks);

       return $this->resultResponse('void',strtoupper($transaction->transaction_id),[]);
    }

    public function viewRequestorLogs(Request $request){
       $requestor_logs = GenericMethod::viewRequestLogs($request);
       if(count($requestor_logs)==true){
        $requestor_logs = RequestLog::collection($requestor_logs);
         return $this->resultResponse('fetch','Requestor Logs',$requestor_logs);
       }
       return $this->resultResponse('not-found','Requestor Logs',[]);
    }
}