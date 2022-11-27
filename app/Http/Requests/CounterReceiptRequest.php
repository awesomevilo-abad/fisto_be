<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CounterReceiptRequest extends FormRequest
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
        return [
            "supplier.id"=>"required",
            "supplier.name"=>"required",
            "counter_receipt.*.department.id"=>"required",
            "counter_receipt.*.department.name"=>"required",
            "counter_receipt.*.receipt_type"=>"required",
            "counter_receipt.*.receipt_no"=>"required",
            "counter_receipt.*.date_transaction"=>"required",
            "counter_receipt.*.amount"=>"required",
        ];
    }

    public function attributes()
    {
       return [
        "supplier.id"=>"Supplier ID",
        "supplier.name"=>"Supplier name",
        "counter_receipt.*.department.id"=>"Department ID",
        "counter_receipt.*.department.name"=>"Department name",
        "counter_receipt.*.date_transaction"=>"Transaction date",
        "counter_receipt.*.receipt_type"=>"Receipt Type",
        "counter_receipt.*.receipt_no"=>"Receipt number",
       ];
    }
    
    public function messages(){
        return [
            'required' => ':attribute is required.',
        ];
    }
}
