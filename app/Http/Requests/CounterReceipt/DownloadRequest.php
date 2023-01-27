<?php

namespace App\Http\Requests\CounterReceipt;

use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class DownloadRequest extends FormRequest
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
        "with_memo" => ["required", "boolean"],
        "counter_receipts" => ["nullable", "required_if:with_memo,1", "array"],
        "counter_receipts.*.id" => ["required", "exists:counter_receipts,id"],
        "counter_receipts.*.receiver" => ["nullable"],
        "counter_receipts.*.counter_receipt_no" => ["required", "exists:counter_receipts,counter_receipt_no"]
      ];
    }

    public function attributes()
    {
      return [
        "with_memo" => "issued memo",
        "counter_receipts" => "counter receipts",
        "counter_receipts.*.id" => "counter receipt",
        "counter_receipts.*.receiver" => "receiver",
        "counter_receipts.*.counter_receipt_no" => "counter receipt no."
      ];
    }
    
    public function messages()
    {
      return [
        "required" => ":attribute is required.",
        "exists" => ":Attribute is not registered.",
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
