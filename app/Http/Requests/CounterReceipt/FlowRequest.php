<?php

namespace App\Http\Requests\CounterReceipt;

use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class FlowRequest extends FormRequest
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
        "process" => "required",
        "subprocess" => "required",
        "reason" => ["nullable", "required_if:subprocess,return|void"],
        "reason.id" => ["required_if:subprocess,void,return", "exists:reasons,id"],
        "reason.description" => "required_if:subprocess,void,return",
        "reason.remarks" => "nullable"
      ];
    }

    public function attributes()
    {
      return [
        "reason.id" => "reason",
        "reason.description" => "description"
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
