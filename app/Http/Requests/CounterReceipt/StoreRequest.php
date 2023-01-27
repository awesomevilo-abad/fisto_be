<?php

namespace App\Http\Requests\CounterReceipt;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $counter_receipt_no = $this->get("no");
        $supplier = $this->get("supplier")["id"];

        return [
            "no" => "nullable",
            "remarks" => "nullable",
            "supplier" => "required",
            "supplier.id" => ["required", "exists:suppliers,id"],
            "supplier.name" => "required",
            "counter_receipt.*.department.id" => ["required", "exists:departments,id"],
            "counter_receipt.*.department.name" => "required",
            "counter_receipt.*.receipt_type.id" => ["required", "exists:referrences,id"],
            "counter_receipt.*.receipt_type.type" => "required",
            "counter_receipt.*.receipt_no" => [
                "required",
                $counter_receipt_no
                    ? Rule::unique('counter_receipts', 'receipt_no')->ignore('counter-void', 'state')->where(function ($query) use ($counter_receipt_no, $supplier) {
                        return $query->where('counter_receipt_no', '<>', $counter_receipt_no)
                                     ->where('supplier_id', $supplier)
                                     ->where('deleted_at', NULL);
                    })
                    : Rule::unique('counter_receipts', 'receipt_no')->ignore('counter-void', 'state')->where(function ($query) use ($supplier) {
                        return $query->where('supplier_id', $supplier)->where('deleted_at', NULL);
                    }),
                "distinct"
            ],
            "counter_receipt.*.date_transaction" => "required",
            "counter_receipt.*.amount" => "required",
        ];
    }

    public function attributes()
    {
       return [
            "remarks" => "remarks",
            "supplier.id" => "supplier",
            "supplier.name" => "supplier name",
            "counter_receipt.*.department.id" => "department",
            "counter_receipt.*.department.name" => "department name",
            "counter_receipt.*.date_transaction" => "transaction date",
            "counter_receipt.*.receipt_type.id" => "receipt type",
            "counter_receipt.*.receipt_type.type" => "receipt type name",
            "counter_receipt.*.receipt_no" => "receipt number"
       ];
    }
    
    public function messages(){
        return [
            "required"  =>  ":attribute is required.",
            "exists"    =>  ":Attribute is not registered.",
            "distinct"  =>  "The :attribute has a duplicate value."
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // $validator->errors()->add("custom", "STOP!");
        });
    }
}
