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

        $user = User::where('id',$this->users_id)->get()->first();
        $po = POBatch::where('request_id',$this->request_id)->get(['request_id as batch','po_no as no', 'po_amount as amount','rr_group as rr_no']);
        $balance  = $this->balance_po_ref_amount;
       
        if(empty($this->balance_po_ref_amount)){
         $balance  = 0;
         }

        $po->mapToGroups(function ($item,$v) use ($balance){
            return [
                $item['balance']=0,
                $item['rr_no']=json_decode($item['rr_no'], true)
            ];
        });
        $po[0]['balance'] = $balance;

        return [
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
                ,"department"=>$this->department
            ],
            "document"=>[
                "id"=>$this->document_id
                ,"name"=>$this->document_type
                ,"no"=>$this->document_no
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
            ]
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
                        "id"=>1
                        ,"name"=>null
                        ,"type"=>null
                        ,"amount"=>null
                    ]]
                ]
            ]
        ];
    }
}
