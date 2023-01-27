<?php

namespace App\Http\Requests\CounterReceipt;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CheckRequest extends FormRequest
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
        "id" => "nullable",
        "receipt_no" => [
          "required",
          $this->get("id")
            ? Rule::unique("counter_receipts", "receipt_no")->ignore("counter-void", "state")
                                                            ->where(function ($query) {
                                                              return $query->where("id", "<>", $this->get("id"))->where("supplier_id", $this->get("supplier_id"))->where("deleted_at", NULL);
                                                            })
            : Rule::unique("counter_receipts", "receipt_no")->ignore("counter-void", "state")
                                                            ->where(function ($query) {
                                                              return $query->where("supplier_id", $this->get("supplier_id"))->where("deleted_at", NULL);
                                                            })
        ],
        "supplier_id" => ["required", "exists:suppliers,id"]
      ];
    }

    public function attributes()
    {
      return [
        "receipt_no" => "receipt no.",
        "supplier_id" => "supplier",
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
