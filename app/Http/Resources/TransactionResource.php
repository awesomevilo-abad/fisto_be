<?php

namespace App\Http\Resources;
use App\Models\User;
use App\Models\POBatch;
use App\Models\Transaction;
use App\Models\Reason;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
        $po_details = [];
        $reference = [];
        $po_no = [];
        $previous_balance=0;
        $first_transaction_keys = [];
        $keys = [];

        $transaction =  Transaction::with('tag.voucher.account_title')->where('id',$this->id)->get()->first();
        $transaction_tag_no= (isset($transaction->tag_no)?$transaction->tag_no:NULL);
        $transaction_voucher_no = (isset($transaction->voucher_no)?$transaction->voucher_no:NULL);
        $transaction_voucher_month = (isset($transaction->voucher_month)?$transaction->voucher_month:NULL);
        
        // TAG PROCESS
        if(count($transaction->tag)>0){
            $transaction_tag= $transaction->tag->first();
            (isset($transaction['document']['capex_no'])?$transaction['document']['capex_no']:NULL);
            $transaction_tag_date= (isset($transaction_tag->date)?$transaction_tag->date:NULL);
            $transaction_tag_status= (isset($transaction_tag->status)?$transaction_tag->status:NULL);
            $transaction_tag_distributed_id= (isset($transaction_tag->distributed_id)?$transaction_tag->distributed_id:NULL);
            $transaction_tag_distributed_name= (isset($transaction_tag->distributed_name)?$transaction_tag->distributed_name:NULL);
            $reason_id = (isset($transaction_tag->reason_id)?$transaction_tag->reason_id:NULL);
            $reason = (isset($transaction_tag->reason_id)?Reason::find($transaction_tag->reason_id)->reason:NULL);
            $reason_remarks = (isset($transaction_tag->remarks)?$transaction_tag->remarks:NULL);
        }
        // END TAG PROCESS


        // VOUCHER PROCESS
        if(count($transaction->tag)>0){
            if(count($transaction_tag->voucher)>0){
            $voucher = $transaction_tag->voucher->first();
            $voucher_receipt_type= (isset($voucher->receipt_type)?$voucher->receipt_type:NULL);
            $voucher_percentage_tax= (isset($voucher->percentage_tax)?$voucher->percentage_tax:NULL);
            $vouocher_witholding_tax= (isset($voucher->witholding_tax)?$voucher->witholding_tax:NULL);
            $voucher_net_amount= (isset($voucher->net_amount)?$voucher->net_amount:NULL);
            $voucher_approver_id= (isset($voucher->approver_id)?$voucher->approver_id:NULL);
            $voucher_approver_name= (isset($voucher->approver_name)?$voucher->approver_name:NULL);
            $voucher_date= (isset($voucher->date)?$voucher->date:NULL);
            $voucher_status= (isset($voucher->status)?$voucher->status:NULL);
            $voucher_reason_id= (isset($voucher->reason_id)?$voucher->reason_id:NULL);
            $voucher_reason = (isset($voucher->reason_id)?Reason::find($voucher->reason_id)->reason:NULL);
            $voucher_reason_remarks= (isset($voucher->remarks)?$voucher->remarks:NULL);
            }
        }
        // END VOUCHER PROCESS

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
            $tax = null;
            $account_title = null;

            if(isset($transaction_tag_distributed_id)){
                $tax = [
                    "receipt_type"=>$voucher_receipt_type,
                    "percentage_tax"=>$voucher_percentage_tax,
                    "witholding_tax"=>$vouocher_witholding_tax,
                    "net_amount"=>$voucher_net_amount
                ];
            }
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
            }
            
            if(isset($transaction_tag_distributed_id)){
                $approver = [
                    "id"=>$voucher_approver_id,
                    "name"=>$voucher_approver_name
                ];
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
                    "tax"=>$tax,
                    "account_title"=>$account_title,
                    "approver"=>$approver,
                    "reason"=>$reason
                ];

        }

        $transaction_result= [
            "transaction"=>[
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
            ,"po_group"=>$po_details
            ,"tag"=> $tag
            ,"voucher"=> $voucher
            ,"file"=> null
        ];

        $result = [];
        foreach ( $transaction_result as $k=>$v){
             if($transaction_result[$k]!=null){
                $result[$k] = $transaction_result[$k];
             }
        }

        return $result;
        //  unset($transaction_result['reason']);
        //  return $transaction_result;
    }
}
