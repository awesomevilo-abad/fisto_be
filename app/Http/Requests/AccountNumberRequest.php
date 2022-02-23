<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccountNumberRequest extends FormRequest
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
            "account_no" => ['required','string'],
            "location_id" => ['required','numeric'],
            "category_id" => ['required','numeric'],
            "supplier_id" => ['required','numeric'],
            
        ];
    }

    public function messages()
    {
        return [
            "account_no.required"=>"Account number field is required",
            "account_no.string"=>'Account number must be string',
            "location_id.required"=>'Location ID must be in number format',
            "category_id.required"=>'Category ID must be in number format',
            "supplier_id.required"=>'Supplier ID must be in number format'
        ];
    }
}
