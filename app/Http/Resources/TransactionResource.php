<?php

namespace App\Http\Resources;
use App\Models\User;
use App\Models\POBatch;
use App\Models\Transaction;
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
        $po_details = [];
        $reference = [];
        $po_no = [];
        $previous_balance=0;
        $keys = [];

        $payment_type = strtoupper($this->payment_type);
        $user = User::where('id',$this->users_id)->get()->first();
        $po_transaction = POBatch::leftJoin('transactions','p_o_batches.request_id','=','transactions.request_id')->get();
        $po_details = POBatch::leftJoin('transactions','p_o_batches.request_id','=','transactions.request_id')
       ->where('transactions.request_id',$this->request_id)
        ->when($payment_type === 'PARTIAL',function($q){
                $q->select(['is_add','is_editable','po_no as no', 'po_amount as amount','previous_balance','balance_po_ref_amount as balance','rr_group as rr_no']);
            }, function($q){
                $q->select(['po_no as no', 'po_amount as amount','rr_group as rr_no','p_o_batches.request_id']);
            } 
        )
        ->get();

        foreach($po_details as $j=>$u){
            $rr_no = json_decode($po_details[$j]['rr_no']);
            $po_details[$j]['rr_no'] = $rr_no;
            $po_details[$j]['is_editable'] = 1;
            $po_details[$j]['previous_balance'] = $po_details[$j]['amount'];
        }

       if(strtoupper($this->payment_type) == 'PARTIAL'){
            $balance = ($po_details->where('is_add',0)->first()->balance);
            $previous_balance = ($po_details->where('is_add',0)->first()->previous_balance);
            foreach($po_details as $k=>$v){
                if($po_details[$k]['is_add']==0){
                    $keys[] = $k;
                    $po_details[$k]['previous_balance'] = 0;
                    $po_details[$k]['balance'] = 0;
                }
                unset($po_details[$k]->is_add);
            }

        $key= current($keys);
        $po_details[$key]['previous_balance'] = $previous_balance;
        $po_details[$key]['balance'] = $balance;

       }
        $transaction =($po_transaction->where('request_id',$this->request_id));
        $document_amount = Transaction::where('request_id',$this->request_id)->first()->document_amount;

        $condition =  ($this->state=='void')? '=': '!=';
        $last_transaction_id = $po_transaction->where('po_no',$po_no)->where('state',$condition,'void')->pluck('id')->last();
        $is_latest_transaction=0;
        if($last_transaction_id == $this->id){
            $is_latest_transaction=1;
        }

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
                        ,"capex"=>$this->capex_no
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

        return [
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
            ,"voucher"=>[
                "ap_associate"=>null
                ,"receipt_type"=>null
                ,"witholding_tax"=>null
                ,"percentage_tax"=>null
                ,"gross_amount"=>null
                ,"net_amount"=>null
                ,"month_in"=>null
                ,"no"=>null
                ,"date"=>null
                ,"account_title"=>[
                    "total_amount"=>null
                    ,"account_title_details"=>[[
                        "id"=>null
                        ,"name"=>null
                        ,"type"=>null
                        ,"amount"=>null
                    ]]
                ]
            ]
            ,"cheque"=>[
                "company"=>[
                    "id"=>null,
                    "name"=>null
                ]
                ,"supplier"=>[
                    "id"=>null,
                    "name"=>null
                    ,"term"=>null
                ]
                ,"total_amount"=>null
                ,"cheque_details"=>[
                    [
                    "no"=>null
                    ,"date"=>null
                    ,"amount"=>null
                    ,"bank"=>[
                        "id"=>null
                        ,"name"=>null
                    ]
                    ]
                ]
                ,"account_title"=>[
                    "total_amount"=>null
                    ,"account_title_details"=>[[
                        "id"=>null
                        ,"name"=>null
                        ,"type"=>null
                        ,"amount"=>null
                    ]]
                ]
            ]
        ];
    }
}
