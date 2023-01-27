<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Models\POBatch;
use App\Models\Transaction;
use App\Models\Reason;
use Illuminate\Http\Resources\Json\JsonResource;

class CounterReceiptSingleView extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $document  = [];
        $tag  = null;
        $voucher  = null;
        $approve  = null;
        $transmit  = null;
        $cheque_description = null;
        $release_description  = null;
        $file_description  = null;
        $reverse_description  = null;
        $po_details = [];
        $prm_group = [];
        $reference = [];
        $po_no = [];
        $previous_balance=0;
        $first_transaction_keys = [];
        $keys = [];
        $reverse_distributor = [];
        $autoDebit_group = [];

        $transaction =  Transaction::where('referrence_no',$this->referrence_no)
        ->where('supplier_id',$this->supplier_id)
        ->where('department_id',$this->department_id);

        if($transaction->exists()){
            $counter_receipt_status =  $transaction->get()->first()->status;
            $counter_receipt_no =  $this->counter_receipt_no;
        }
        else{
            $counter_receipt_status = "Unprocessed";
            $counter_receipt_no = NULL;
        }

        $transaction =  Transaction::with('tag')
        ->with('voucher.account_title')
        ->with('approve')
        ->with('transmit')
        ->with('cheque')
        ->with('cheque')
        ->with('release')
        ->with('file')
        ->with('reverse')
        ->when($this->document_type =="Auto Debit", function($query){
            $query->with('auto_debit');
        })
        ->where('id',$this->id)->get()->first();
        $transaction_tag_no= (isset($transaction->tag_no)?$transaction->tag_no:NULL);
        $transaction_voucher_no = (isset($transaction->voucher_no)?$transaction->voucher_no:NULL);
        $transaction_voucher_month = (isset($transaction->voucher_month)?$transaction->voucher_month:NULL);
        $transaction_with_debit = $transaction;
     
        // TAG PROCESS
        if(count($transaction->tag)>0){
            $transaction_tag= $transaction->tag->first();
            (isset($transaction['document']['capex_no'])?$transaction['document']['capex_no']:NULL);
            $transaction_tag_date= (isset($transaction_tag->date)?$transaction_tag->date:NULL);
            $transaction_tag_status= (isset($transaction_tag->status)?$transaction_tag->status:NULL);
            $transaction_tag_distributed_id= (isset($transaction->distributed_id)?$transaction->distributed_id:NULL);
            $transaction_tag_distributed_name= (isset($transaction->distributed_name)?$transaction->distributed_name:NULL);
            
            $reason_id = (isset($transaction_tag->reason_id)?$transaction_tag->reason_id:NULL);
            $reason = (isset($transaction_tag->reason_id)?Reason::find($transaction_tag->reason_id)->reason:NULL);
            $reason_remarks = (isset($transaction_tag->remarks)?$transaction_tag->remarks:NULL);
        }
        // END TAG PROCESS

        // VOUCHER PROCESS
        if(count($transaction->voucher)>0){
            $voucher = $transaction->voucher->first();
            $voucher_receipt_type= (isset($voucher->receipt_type)?$voucher->receipt_type:NULL);
            // $voucher_percentage_tax= (isset($voucher->percentage_tax)?$voucher->percentage_tax:NULL);
            // $vouocher_witholding_tax= (isset($voucher->witholding_tax)?$voucher->witholding_tax:NULL);
            // $voucher_net_amount= (isset($voucher->net_amount)?$voucher->net_amount:NULL);
            $voucher_approver_id= (isset($voucher->approver_id)?$voucher->approver_id:NULL);
            $voucher_approver_name= (isset($voucher->approver_name)?$voucher->approver_name:NULL);
            $voucher_date= (isset($voucher->date)?$voucher->date:NULL);
            $voucher_status= (isset($voucher->status)?$voucher->status:NULL);
            $voucher_reason_id= (isset($voucher->reason_id)?$voucher->reason_id:NULL);
            $voucher_reason = (isset($voucher->reason_id)?Reason::find($voucher->reason_id)->reason:NULL);
            $voucher_reason_remarks= (isset($voucher->remarks)?$voucher->remarks:NULL);
        }
        // END VOUCHER PROCESS
        
        // APPROVE PROCESS
        if(count($transaction->approve)>0){
            $approve = $transaction->approve->first();

            $approve_id= (isset($approve->id)?$approve->id:NULL);
            $approve_distributed_id= (isset($transaction->distributed_id)?$transaction->distributed_id:NULL);
            $approve_distributed_name= (isset($transaction->distributed_name)?$transaction->distributed_name:NULL);
            $approve_date= (isset($approve->date)?$approve->date:NULL);
            $approve_status= (isset($approve->status)?$approve->status:NULL);
            $approve_reason_id= (isset($approve->reason_id)?$approve->reason_id:NULL);
            $approve_reason = (isset($approve->reason_id)?Reason::find($approve->reason_id)->reason:NULL);
            $approve_reason_remarks= (isset($approve->remarks)?$approve->remarks:NULL);
        }
        // END APPROVE PROCESS
        
        // TRANSMITAL PROCESS
        if(count($transaction->transmit)>0){
            $transmit = $transaction->transmit->first();

            $transmit_id= (isset($transmit->id)?$transmit->id:NULL);
            $transmit_date= (isset($transmit->date)?$transmit->date:NULL);
            $transmit_status= (isset($transmit->status)?$transmit->status:NULL);
        }
        // END TRANSMITAL PROCESS
        
        // CHEQUE PROCESS
        if(count($transaction->cheques)>0){
            $cheque = $transaction->cheques->first();
            $cheque_status= (isset($cheque->status)?$cheque->status:NULL);
            $cheque_date_status= (isset($cheque->date)?$cheque->date:NULL);
            $cheque_reason_id= (isset($cheque->reason_id)?$cheque->reason_id:NULL);
            $cheque_reason = (isset($cheque->reason_id)?Reason::find($cheque->reason_id)->reason:NULL);
            $cheque_reason_remarks= (isset($cheque->remarks)?$cheque->remarks:NULL);
        }
        // END CHEQUE PROCESS
        
        // RELEASE PROCESS
        if(count($transaction->release)>0){
            $release = $transaction->release->first();

            $release_id= (isset($release->id)?$release->id:NULL);
            $release_date= (isset($release->date)?$release->date:NULL);
            $release_reason_id= (isset($release->reason_id)?$release->reason_id:NULL);
            $release_reason = (isset($release->reason_id)?Reason::find($release->reason_id)->reason:NULL);
            $release_reason_remarks= (isset($release->remarks)?$release->remarks:NULL);
            $release_status= (isset($release->status)?$release->status:NULL);
            $release_distributed_id= (isset($release->distributed_id)?$release->distributed_id:NULL);
            $release_distributed_name= (isset($release->distributed_name)?$release->distributed_name:NULL);
        }
        // END RELEASE PROCESS
        
        // FILE PROCESS
        if(count($transaction->file)>0){
            $file = $transaction->file->first();

            $file_id= (isset($file->id)?$file->id:NULL);
            $file_date= (isset($file->date)?$file->date:NULL);
            $file_status= (isset($file->status)?$file->status:NULL);
            $file_reason_id= (isset($file->reason_id)?$file->reason_id:NULL);
            $file_reason = (isset($file->reason_id)?Reason::find($file->reason_id)->reason:NULL);
            $file_reason_remarks= (isset($file->remarks)?$file->remarks:NULL);
        }
        // END FILE PROCESS

        // REVERSE PROCESS
        if(count($transaction->reverse)>0){
            $reverse = $transaction->reverse->first();

            $reverse_id= (isset($reverse->id)?$reverse->id:NULL);
            $reverse_date= (isset($reverse->date)?$reverse->date:NULL);
            $reverse_status= (isset($reverse->status)?$reverse->status:NULL);
            $reverse_reason_id= (isset($reverse->reason_id)?$reverse->reason_id:NULL);
            $reverse_reason = (isset($reverse->reason_id)?Reason::find($reverse->reason_id)->reason:NULL);
            $reverse_reason_remarks= (isset($reverse->remarks)?$reverse->remarks:NULL);
            $reverse_user_role= (isset($reverse->user_role)?$reverse->user_role:NULL);
            $reverse_user_id= (isset($reverse->user_id)?$reverse->user_id:NULL);
            $reverse_user_name= (isset($reverse->user_name)?$reverse->user_name:NULL);
            $reverse_distributed_id= (isset($reverse->distributed_id)?$reverse->distributed_id:NULL);
            $reverse_distributed_name= (isset($reverse->distributed_name)?$reverse->distributed_name:NULL);
        }
        // END REVERSE PROCESS
        $condition =  ($this->state=='void')? '=': '!=';
        $document_amount = Transaction::where('request_id',$this->request_id)->where('state',$condition,'void')->first()->document_amount;
        $payment_type = strtoupper($this->payment_type);
        $user = User::where('id',$this->users_id)->get()->first();
        $po_transaction = POBatch::leftJoin('transactions','p_o_batches.request_id','=','transactions.request_id')
        ->where('transactions.state',$condition,'void')
        ->get();
        $po_details = POBatch::leftJoin('transactions','p_o_batches.request_id','=','transactions.request_id')
        ->where('transactions.state',$condition,'void')
        ->where('transactions.request_id',$this->request_id)
        ->when($payment_type === 'PARTIAL',function($q){
                $q->select(['is_add','is_editable','p_o_batches.id as id','po_no as no', 'po_amount as amount','previous_balance','balance_po_ref_amount as balance','rr_group as rr_no']);
            }, function($q){
                $q->select(['p_o_batches.id as id','po_no as no', 'po_amount as amount','rr_group as rr_no','p_o_batches.request_id']);
            } 
        )
        ->get();

        foreach($po_details as $j=>$u){
            $rr_no = json_decode($po_details[$j]['rr_no']);
            $po_details[$j]['rr_no'] = $rr_no;
            $po_details[$j]['is_editable'] = 1;
            $po_details[$j]['previous_balance'] = $po_details[$j]['amount'];
        }
        
       $is_latest_transaction=1;
       if(strtoupper($this->payment_type) == 'PARTIAL'){
        $is_latest_transaction=0;

            $first_po_no = $po_details->where('is_add',0)->last()->no;
            $with_linked_transactions = $po_transaction->where('po_no',$first_po_no)->where('id','<',$this->id)->pluck('id');
            $balance = ($po_details->where('is_add',0)->first()->balance);
            $previous_balance = ($po_details->where('is_add',0)->first()->previous_balance);

            foreach($po_details as $k=>$v){
                $po_no = $po_details[$k]['no'];
                if(($po_details[$k]['is_add']==0) AND (count($with_linked_transactions) == 0)){
                    $first_transaction_keys[] = $k;
                    $po_details[$k]['previous_balance'] = $po_details[$k]['amount'];
                    $po_details[$k]['balance'] = 0;
                }
                else if(($po_details[$k]['is_add']==0) AND (count($with_linked_transactions) > 0)){
                    $old_po_with_linked_transaction_keys[] = $k;
                    $po_details[$k]['previous_balance'] = 0;
                    $po_details[$k]['balance'] = 0;
                }
            //     else if($po_details[$k]['is_add']==0 ){
            //         $keys[] = $k;
            //         $po_details[$k]['previous_balance'] = 0;
            //         $po_details[$k]['balance'] = 0;
            //     }
            //     // unset($po_details[$k]->is_add);
                $last_transaction_id = $po_transaction->where('po_no',$po_no)->where('state',$condition,'void')->pluck('id')->last();
                if($last_transaction_id == $this->id){
                    $is_latest_transaction=1;
                }
            }

            // return current($old_po_with_linked_transaction_keys);
            
            if(!empty($first_transaction_keys)){
                $key = current($first_transaction_keys);
                $po_details[$key]['balance'] = $balance;
            }else if(!empty($old_po_with_linked_transaction_keys)){
                $last_transaction_no =  $with_linked_transactions->last();
                $previous_balance = $po_transaction->firstWhere('id',$last_transaction_no)->balance_po_ref_amount;
                $key = current($old_po_with_linked_transaction_keys);
                $po_details[$key]['previous_balance'] = $previous_balance;
            }else{
            }
            $po_details->last()->balance = $po_details->pluck('previous_balance')->sum() - $this->referrence_amount;
        }
        $transaction =($po_transaction->where('request_id',$this->request_id));
 
        switch($this->document_id){
            case 1: //PAD
            case 2: //PRM Common
                $document = [
                    "id"=>$this->document_id
                    ,"name"=>$this->document_type
                    ,"no"=>$this->document_no
                    ,"date"=>$this->document_date
                    ,"payment_type"=>$this->payment_type
                    ,"amount"=>$this->document_amount
                    ,"remarks"=>$this->remarks
                    ,"category"=>[
                        "id"=>$this->category_id,
                        "name"=>$this->category
                    ],
                    "company"=>[
                        "id"=>$this->company_id,
                        "name"=>$this->company
                    ],
                    "department"=>[
                        "id"=>$this->department_id,
                        "name"=>$this->department
                    ],
                    "location"=>[
                        "id"=>$this->location_id,
                        "name"=>$this->location
                    ],
                    "supplier"=>[
                        "id"=>$this->supplier_id,
                        "name"=>$this->supplier
                    ]
                ];
            break;
            case 3: //PRM Multiple
                $document = [
                    "id"=>$this->document_id
                    ,"name"=>$this->document_type
                    ,"no"=>$this->document_no
                    ,"date"=>$this->document_date
                    ,"payment_type"=>$this->payment_type
                    ,"amount"=>$this->document_amount
                    ,"release_date"=>$this->release_date
                    ,"batch_no"=>$this->batch_no
                    ,"remarks"=>$this->remarks
                    ,"category"=>[
                        "id"=>$this->category_id,
                        "name"=>$this->category
                    ],
                    "company"=>[
                        "id"=>$this->company_id,
                        "name"=>$this->company
                    ],
                    "department"=>[
                        "id"=>$this->department_id,
                        "name"=>$this->department
                    ],
                    "location"=>[
                        "id"=>$this->location_id,
                        "name"=>$this->location
                    ],
                    "supplier"=>[
                        "id"=>$this->supplier_id,
                        "name"=>$this->supplier
                    ]
                ];
                switch($this->category){
                    
                    case "rental":
                        $document['period_covered'] = $this->period_covered;
                        $document['prm_multiple_from'] = $this->prm_multiple_from;
                        $document['prm_multiple_to'] = $this->prm_multiple_to;
                        $document['gross_amount'] = $this->gross_amount;
                        $document['witholding_tax'] = $this->witholding_tax;
                        $document['net_of_amount'] = $this->net_amount;
                        $document['cheque_date'] = $this->cheque_date;
                        break;
                    case "leasing":
                        $document['amortization'] = $this->amortization;
                        $document['principal'] = $this->principal;
                        $document['interest'] = $this->interest;
                        $document['cwt'] = $this->cwt;
                        $document['net_of_amount'] = $this->net_amount;
                        $document['cheque_date'] = $this->cheque_date;
                    break;
                    case "loans":
                        $document['principal'] = $this->principal;
                        $document['interest'] = $this->interest;
                        $document['cwt'] = $this->cwt;
                        $document['net_of_amount'] = $this->net_amount;
                        $document['cheque_date'] = $this->cheque_date;
                    break;
                }

            break;
            case 5: //Contractor's Billing
                    $document = [
                        "id"=>$this->document_id
                        ,"name"=>$this->document_type
                        ,"no"=>$this->document_no
                        ,"capex_no"=>$this->capex_no
                        ,"date"=>$this->document_date
                        ,"payment_type"=>$this->payment_type
                        ,"amount"=>$this->document_amount
                        ,"remarks"=>$this->remarks
                        ,"category"=>[
                            "id"=>$this->category_id,
                            "name"=>$this->category
                        ],
                        "company"=>[
                            "id"=>$this->company_id,
                            "name"=>$this->company
                        ],
                        "department"=>[
                            "id"=>$this->department_id,
                            "name"=>$this->department
                        ],
                        "location"=>[
                            "id"=>$this->location_id,
                            "name"=>$this->location
                        ],
                        "supplier"=>[
                            "id"=>$this->supplier_id,
                            "name"=>$this->supplier
                        ]
                    ];
            break;
            
            case 6: //Utilities
                $document = [
                    "id"=>$this->document_id
                    ,"name"=>$this->document_type
                    ,"payment_type"=>$this->payment_type
                    ,"amount"=>$this->document_amount
                    ,"from"=> $this->utilities_from
                    ,"to"=> $this->utilities_to
                    ,"remarks"=>$this->remarks
                    ,"company"=>[
                        "id"=>$this->company_id,
                        "name"=>$this->company
                    ],
                    "department"=>[
                        "id"=>$this->department_id,
                        "name"=>$this->department
                    ],
                    "location"=>[
                        "id"=>$this->location_id,
                        "name"=>$this->location
                    ],
                    "supplier"=>[
                        "id"=>$this->supplier_id,
                        "name"=>$this->supplier
                    ],
                    "utility" => [
                        "receipt_no"=> $this->utilities_receipt_no
                        ,"consumption"=> $this->utilities_consumption
                        ,"location"=> [
                            "id"=> $this->utilities_location_id,
                            "name"=> $this->utilities_location
                        ]
                        ,"category"=> [
                            "id"=>  $this->utilities_category_id,
                            "name"=> $this->utilities_category
                        ]
                        ,"account_no"=> [
                            "id"=>  $this->utilities_account_no_id,
                            "no"=> $this->utilities_account_no
                        ]
                    ]
                ];
            break;
                
            case 8: //PCF
                
                $document = [
                    "id"=>$this->document_id
                    ,"name"=>$this->document_type
                    ,"date"=>$this->document_date
                    ,"amount"=>$this->document_amount
                    ,"payment_type"=>$this->payment_type
                    ,"remarks"=>$this->remarks
                    ,"company"=>[
                        "id"=>$this->company_id,
                        "name"=>$this->company
                    ],
                    "department"=>[
                        "id"=>$this->department_id,
                        "name"=>$this->department
                    ],
                    "location"=>[
                        "id"=>$this->location_id,
                        "name"=>$this->location
                    ],
                    "supplier"=>[
                        "id"=>$this->supplier_id,
                        "name"=>$this->supplier
                    ],
                    "pcf_batch" => [
                        "name"=> $this->pcf_name
                        ,"letter"=> $this->pcf_letter
                        ,"date"=>  $this->pcf_date
                    ]
                ];

            break;
            
            case 7: //Payroll
                $document = [
                    "id"=>$this->document_id
                    ,"name"=>$this->document_type
                    ,"payment_type"=>$this->payment_type
                    ,"amount"=>$this->document_amount
                    ,"from"=> $this->payroll_from
                    ,"to"=> $this->payroll_to
                    ,"remarks"=>$this->remarks
                    ,"company"=>[
                        "id"=>$this->company_id,
                        "name"=>$this->company
                    ],
                    "department"=>[
                        "id"=>$this->department_id,
                        "name"=>$this->department
                    ],
                    "location"=>[
                        "id"=>$this->location_id,
                        "name"=>$this->location
                    ],
                    "supplier"=>[
                        "id"=>$this->supplier_id,
                        "name"=>$this->supplier
                    ],
                    "payroll" => [
                        "type"=> $this->payroll_type
                        ,"clients"=> $this->payroll_client
                        ,"category"=> [
                            "id"=>  $this->payroll_category_id,
                            "name"=> $this->payroll_category
                        ]
                    ]
                ];
            break;
                
            case 4: //Receipt
            $document = [
                "id"=>$this->document_id
                ,"name"=>$this->document_type
                ,"date"=>$this->document_date
                ,"payment_type"=>$this->payment_type
                ,"remarks"=>$this->remarks
                ,"category"=>[
                    "id"=>$this->category_id,
                    "name"=>$this->category
                ],
                "company"=>[
                    "id"=>$this->company_id,
                    "name"=>$this->company
                ],
                "department"=>[
                    "id"=>$this->department_id,
                    "name"=>$this->department
                ],
                "location"=>[
                    "id"=>$this->location_id,
                    "name"=>$this->location
                ],
                "supplier"=>[
                    "id"=>$this->supplier_id,
                    "name"=>$this->supplier
                ],
                "reference"=>[
                    "id"=>$this->referrence_id,
                    "type"=>$this->referrence_type,
                    "no"=>$this->referrence_no,
                    "amount"=> $this->referrence_amount
                ]
            ];
            break;
            case 9: //Auto Debit
                $document = [
                    "id"=>$this->document_id
                    ,"name"=>$this->document_type
                    ,"date"=>$this->document_date
                    ,"payment_type"=>$this->payment_type
                    ,"amount"=>$this->document_amount
                    ,"remarks"=>$this->remarks
                    ,"category"=>[
                        "id"=>$this->category_id,
                        "name"=>$this->category
                    ],
                    "company"=>[
                        "id"=>$this->company_id,
                        "name"=>$this->company
                    ],
                    "department"=>[
                        "id"=>$this->department_id,
                        "name"=>$this->department
                    ],
                    "location"=>[
                        "id"=>$this->location_id,
                        "name"=>$this->location
                    ],
                    "supplier"=>[
                        "id"=>$this->supplier_id,
                        "name"=>$this->supplier
                    ]
                ];
            break;
        }

        // TAG
        if(isset($transaction_tag_status)){

            $reason = null;
            $distributed_to = null;

            if(isset($transaction_tag_distributed_id)){
                $distributed_to = [
                    "id"=>$transaction_tag_distributed_id,
                    "name"=>$transaction_tag_distributed_name
                ];
            }
            if(isset($reason_id)){
                $reason = [
                    "id"=>$reason_id,
                    "reason"=>$reason,
                    "remarks"=>$reason_remarks
                ];
            }

            $tag = [
                    "status"=>$transaction_tag_status,
                    "no"=>$transaction_tag_no,
                    "date"=>$transaction_tag_date,
                    "distributed_to"=>$distributed_to,
                    "reason"=>$reason
                ];

        }
        
        // VOUCHER
        if(isset($voucher_status)){

            $reason = null;
            $approver = null;
            $account_title = null;

            if(isset($voucher->account_title)){
                $voucher_account_title = $voucher->account_title;
                $voucher_account_title = $voucher_account_title->mapToGroups(function ($item, $key) {
                    return [$item['associate_id'] => 
                                [
                                "id"=>$item['associate_id']
                                ,"entry"=>$item['entry']
                                ,"account_title"=>
                                    [
                                        "id"=>$item['account_title_id']
                                        ,"name"=>$item['account_title_name']
                                    ]
                                ,"amount"=>$item['amount']
                                ,"remarks"=>$item['remarks']
                                ]
                            ];
                });
                $account_title= $voucher_account_title->values();

                if(!($account_title->isEmpty())){
                    $account_title = $account_title->first();
                }else{
                    $account_title = [];
                }
            }
            
            if(isset($transaction_tag_distributed_id)){
                if($voucher_approver_id){
                    $approver = [
                        "id"=>$voucher_approver_id,
                        "name"=>$voucher_approver_name
                    ];
                }
            }

            if(isset($voucher_reason_id)){
                $reason = [
                    "id"=>$voucher_reason_id,
                    "reason"=>$voucher_reason,
                    "remarks"=>$voucher_reason_remarks
                ];
            }

            $voucher = [
                    "status"=>$voucher_status,
                    "date"=>$voucher_date,
                    "no"=>$transaction_voucher_no,
                    "month"=>$transaction_voucher_month,
                    "receipt_type"=>$voucher_receipt_type,
                    "accounts"=>$account_title,
                    "approver"=>$approver,
                    "reason"=>$reason
                ];

        }

       // APPROVE
        if(isset($approve_status)){
            $reason = null;
            $distributed_to = null;

            if(isset($approve_distributed_id)){
                $distributed_to = [
                    "id"=>$approve_distributed_id,
                    "name"=>$approve_distributed_name
                ];
            }
            if(isset($approve_reason_id)){
                $reason = [
                    "id"=>$approve_reason_id,
                    "reason"=>$approve_reason,
                    "remarks"=>$approve_reason_remarks
                ];
            }

            $approve = [
                    "status"=>$approve_status,
                    "date"=>$approve_date,
                    "distributed_to"=>$distributed_to,
                    "reason"=>$reason
                ];
        }
            
        // TRANSMIT
        if(isset($transmit_status)){

            $reason = null;

            $transmit = [
                    "status"=>$transmit_status,
                    "date"=>$transmit_date,
                ];

        }
        
        // CHEQUE
        if(isset($cheque_status)){

            $reason = null;
            $account_title = null;

            if(isset($cheque->cheques)){
             $cheque_cheques = $cheque->cheques;
             $cheque_cheques = $cheque_cheques->filter( function ($value,$key){
                return $value['transaction_type'] ==  'new';
             });

            $cheque_details = $cheque_cheques->mapToGroups(function ($item, $key) {
                
                return [$item['treasury_id'] => 
                            [
                                // "id"=>$item['treasury_id']
                                "type"=>$item['entry_type']
                                ,"bank"=>[
                                        "id"=>intval($item['bank_id']),
                                        "name"=>$item['bank_name'],
                                    ]
                                ,"no"=>$item['cheque_no'],
                                "date"=>$item['cheque_date'],
                                "amount"=>$item['cheque_amount'],
                                
                            ]
                        ];
            });
            $cheque_details= $cheque_details->values();
            }
            
            if(isset($cheque->account_title)){
                $cheque_account_title = $cheque->account_title;
                $cheque_account_title = $cheque_account_title->filter( function ($value,$key){
                   return $value['transaction_type'] ==  'new';
                });
                $cheque_account_title = $cheque_account_title->mapToGroups(function ($item, $key) {
                    return [$item['treasury_id'] => 
                                [
                                "id"=>$item['treasury_id']
                                ,"entry"=>$item['entry']
                                ,"account_title"=>
                                    [
                                        "id"=>$item['account_title_id']
                                        ,"name"=>$item['account_title_name']
                                    ]
                                ,"amount"=>$item['amount']
                                ,"remarks"=>$item['remarks']
                                ]
                            ];
                    });
                $account_title= $cheque_account_title->values();
            }

            if(!($cheque_details->isEmpty())){
                $cheque_details = $cheque_details->first();
            }else{
                $cheque_details = [];
            }

            if(!($account_title->isEmpty())){
                $account_title = $account_title->first();
            }else{
                $account_title = [];
            }      
            
            if(isset($cheque_reason_id)){
                $reason = [
                    "id"=>$cheque_reason_id,
                    "reason"=>$cheque_reason,
                    "remarks"=>$cheque_reason_remarks
                ];
            }

            $cheque_description = [
                    "status"=>$cheque_status,
                    "date"=>$cheque_date_status,
                    "cheques"=>$cheque_details,
                    "accounts"=>$account_title,
                    "reason"=>$reason
                ];

        }


        // RELEASE
        if(isset($release_status)){

            $reason = null;
            $distributed_to = null;

            if(isset($release_distributed_id)){
                $distributed_to = [
                    "id"=>$release_distributed_id,
                    "name"=>$release_distributed_name
                ];
            }

            if(isset($release_reason_id)){
                $reason = [
                    "id"=>$release_reason_id,
                    "reason"=>$release_reason,
                    "remarks"=>$release_reason_remarks
                ];
            }

            $release_description = [
                    "status"=>$release_status,
                    "date"=>$release_date,
                    "distributed_to"=>$distributed_to,
                    "reason"=>$reason
                ];

        }

        // FILE
        if(isset($file_status)){

            $reason = null;

            if(isset($file_reason_id)){
                $reason = [
                    "id"=>$file_reason_id,
                    "reason"=>$file_reason,
                    "remarks"=>$file_reason_remarks
                ];
            }

            $file_description = [
                    "status"=>$file_status,
                    "date"=>$file_date,
                    "reason"=>$reason
                ];

        }
        
        // REVERSE
        if(isset($reverse_status)){

            $reason = null;
            $modified_by = null;

            if(isset($reverse_reason_id)){
                $reason = [
                    "id"=>$reverse_reason_id,
                    "reason"=>$reverse_reason,
                    "remarks"=>$reverse_reason_remarks
                ];
            }

            if(isset($reverse_user_id)){
                $modified_by = [
                    "role"=>$reverse_user_role,
                    "id"=>$reverse_user_id,
                    "name"=>$reverse_user_name
                ];
            }

            if(isset($reverse_distributed_id)){
                $reverse_distributor = [
                        "id"=>$reverse_distributed_id,
                        "name"=>$reverse_distributed_name
                    ];
            }

            $reverse_description = [
                "status"=>$reverse_status,
                "date"=>$reverse_date,
                "modified_by"=>$modified_by,
                "reason"=>$reason,
                "distributed_to"=>$reverse_distributor
            ];

            if($reverse_status != "reverse-request"){
                $reverse_description = [
                    "status"=>$reverse_status,
                    "date"=>$reverse_date,
                    "modified_by"=>$modified_by,
                    "reason"=>$reason,
                ];
            }


        }

        // PRM GROUP
        if($this->document_type == "PRM Multiple"){
            switch($this->category){
                case "rental":
                    $prm_fields = Transaction::where('transaction_id',$this->transaction_id)->select(["status","period_covered","gross_amount","witholding_tax as wht","net_amount as net_of_amount","cheque_date"])->get();
                break;
                case "leasing":
                    $prm_fields = Transaction::where('transaction_id',$this->transaction_id)->select(["status","amortization","principal","interest","cwt","net_amount as net_of_amount","cheque_date"])->get();
                break;
                case "loans":
                    $prm_fields = Transaction::where('transaction_id',$this->transaction_id)->select(["status","principal","interest","cwt","net_amount as net_of_amount","cheque_date"])->get();
                break;
            }
           $prm_group = $prm_fields;
        }
        
        // AUTO DEBIT GROUP
        if($this->document_type == "Auto Debit"){
            $auto_debit = [];
            foreach($transaction_with_debit->auto_debit as $k=>$auto_debit_batch){
                $auto_debit[$k]["request_id"]=$auto_debit_batch->request_id;
                $auto_debit[$k]["pn_no"] = $auto_debit_batch->pn_no;
                $auto_debit[$k]["interest_from"] = $auto_debit_batch->interest_from;
                $auto_debit[$k]["interest_to"] = $auto_debit_batch->interest_to;
                $auto_debit[$k]["outstanding_amount"] = floatVal($auto_debit_batch->outstanding_amount);
                $auto_debit[$k]["interest_rate"] = floatVal($auto_debit_batch->interest_rate);
                $auto_debit[$k]["no_of_days"] = floatVal($auto_debit_batch->no_of_days);
                $auto_debit[$k]["principal_amount"] = floatVal($auto_debit_batch->principal_amount);
                $auto_debit[$k]["interest_due"] = floatVal($auto_debit_batch->interest_due);
                $auto_debit[$k]["cwt"]=floatVal($auto_debit_batch->cwt);
            }

            $autoDebit_group = $auto_debit;
        }

        $counter_receipt = [];
        if($counter_receipt_status){
            $counter_receipt = [
                    "status"=>$counter_receipt_status
                    ,"no"=>$counter_receipt_no
            ];
        }
        
        $transaction_result= [
            "counter_receipt"=>$counter_receipt
            ,"transaction"=>[
                "id"=>$this->id
                ,"is_latest_transaction"=>$is_latest_transaction
                ,"request_id"=>$this->request_id
                ,"no"=>$this->transaction_id
                ,"date_requested"=>$this->date_requested                
                ,"status"=>$this->status     
                ,"state"=>$this->state
            ],
            "reason"=>[
                "id"=>$this->reason_id
                ,"description"=>$this->reason
                ,"remarks"=>$this->reason_remarks
            ],
            "requestor"=>[
                "id"=> $this->users_id
                ,"id_prefix"=>$this->id_prefix
                ,"id_no"=>$this->id_no
                ,"role"=>$user->role
                ,"position"=> $user->position
                ,"first_name"=>$this->first_name
                ,"middle_name"=>$this->middle_name
                ,"last_name"=>$this->last_name
                ,"suffix"=>$this->suffix      
                ,"department"=>$this->department_details
            ],
            "document"=>$document
        ];

        $transaction_result['autoDebit_group']=$autoDebit_group;
        $transaction_result['po_group']=$po_details;
        $transaction_result['prm_group']=$prm_group;
        $transaction_result['tag']=$tag;
        $transaction_result['voucher']=$voucher;
        $transaction_result['approve']=$approve;
        $transaction_result['transmit']=$transmit;
        $transaction_result['cheque']=$cheque_description;
        $transaction_result['release']=$release_description;
        $transaction_result['file']=$file_description;
        $transaction_result['reverse']=$reverse_description;
        // return $transaction_result;
        $result = [];
        foreach ( $transaction_result as $k=>$v){
             if($transaction_result[$k]!=null){
                $result[$k] = $transaction_result[$k];
             }
        }
        return $result;
    }
}
