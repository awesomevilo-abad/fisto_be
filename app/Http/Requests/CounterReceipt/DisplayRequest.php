<?php

namespace App\Http\Requests\CounterReceipt;

use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class DisplayRequest extends FormRequest
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
        "status" => "nullable",
        "search" => "nullable",

        "paginate" => ["nullable", "boolean"],
        "page" => "numeric",
        "rows" => "numeric",

        "from" => "date",
        "to" => "date",

        "suppliers" => "array",
        "suppliers.*" => ["required", "exists:suppliers,id"],
        "departments" => "array",
        "departments.*" => ["required", "exists:departments,id"],

        "state" => ["nullable", "in:Processed,Unprocessed"]
      ];
    }

    public function attributes()
    {
      return [
        "suppliers.*" => "supplier",
        "departments.*" => "department"
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
