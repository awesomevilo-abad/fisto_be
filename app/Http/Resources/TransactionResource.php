<?php

namespace App\Http\Resources;
use App\Models\User;
use App\Models\POBatch;
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
        $po = [];
        $reference = [];
        $po_no = [];

        $user = User::where('id',$this->users_id)->get()->first();
        $po = POBatch::where('request_id',$this->request_id)->get(['request_id as batch','po_no as no', 'po_amount as amount','rr_group as rr_no']);
        $po_transaction = POBatch::leftJoin('transactions','p_o_batches.request_id','=','transactions.request_id')->get();
        if(isset( $po_transaction->where('request_id',$this->request_id)->first()->po_no)){
            $po_no =  $po_transaction->where('request_id',$this->request_id)->first()->po_no;
        }

        $condition =  ($this->state=='void')? '=': '!=';
        // return $po_transaction->where('po_no',$po_no)->where('state',$condition,'void');
        
        $last_po =  $po_transaction->where('request_id',$this->request_id)->pluck('po_no');
        $last_po_array = $last_po->toArray();
        $last_po = next($last_po_array);

        return $last_po;
        
        $first_transaction_id = $po_transaction->where('po_no',$po_no)->where('state',$condition,'void')->pluck('id')->first();
        $last_transaction_id = $po_transaction->where('po_no',$po_no)->where('state',$condition,'void')->pluck('id')->last();
        $previous_balance = 0;
        $is_latest_transaction=0;

        if($last_transaction_id == $this->id){
            $is_latest_transaction=1;
        }
       
        if($first_transaction_id == $this->id){
            return "First transaction";
            $previous_balance = $po_transaction->where('request_id',$this->request_id)->first()->po_total_amount;
        }else{
            return "not first transaction";
            $previous_balance_transaction = $po_transaction->where('po_no',$po_no)->where('state','!=','void')->pluck('balance_po_ref_amount');
            if(!empty($previous_balance_transaction->first())){
                $previous_balance =  $previous_balance_transaction[count($previous_balance_transaction)-2];
            }
        }
       


        $balance  = $this->balance_po_ref_amount;
        if(empty($this->balance_po_ref_amount)){
         $balance  = 0;
         }

         if(!$po->isEmpty()){
            $po->mapToGroups(function ($item,$v) use ($balance){
                return [
                    $item['balance']=0,
                    $item['previous_balance']=0,
                    $item['rr_no']=json_decode($item['rr_no'], true)
                ];
            });
            $po[0]['balance'] = $balance;
            $po[0]['previous_balance'] = $previous_balance;
         }
        
        switch($this->document_id){
            case 1: //PAD
            case 5: //Contractor's Billing
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
            ,"po_group"=>$po
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
