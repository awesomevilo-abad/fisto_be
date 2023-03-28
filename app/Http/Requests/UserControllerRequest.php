<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserControllerRequest extends FormRequest
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
            'id_prefix' => 'string|required'
            , 'id_no' => 'required'
            , 'role' => 'required|string'
            , 'first_name' => 'required|string'
            , 'middle_name' => 'nullable'
            , 'last_name' => 'required|string'
            , 'suffix' => 'nullable'
            , 'department' => 'required'
            , 'position' => 'required|string'
            , 'permissions' => 'required'
            , 'document_types' => 'nullable'
            , 'username' => 'required|string'
        ];
    }

    public function messages()
    {
        return [
            "id_prefix.required"=>"ID Prefix is required"
            ,"id_no.required"=>"ID No is required"
        ];
    }
}
