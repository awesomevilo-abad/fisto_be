<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PODetailsRequest extends FormRequest
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
            "company_id"=>'required',
            "po_no"=>'required',
            "payment_type"=>'required'
        ];
    }
    
    public function attributes()
    {
        return [
            'company_id' => 'Company ID',
            'po_no' => 'PO number',
            'payment_type' => 'Payment type',
        ];
    }
    
    public function messages(){
        return [
            'required' => ':attribute is required.',
        ];
    }
}
