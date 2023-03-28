<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class TransactionPostRequest extends FormRequest
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
            "requestor.id" => 'required'
            , "requestor.id_prefix" => 'required'
            , "requestor.id_no" => 'required'
            , "requestor.role" => 'required'
            , "requestor.position" => 'required'
            , "requestor.first_name" => 'required'
            , "requestor.middle_name" => 'nullable'
            , "requestor.last_name" => 'required'
            , "requestor.suffix" => 'nullable'
            , "requestor.department" => 'required'
           
            , "document.id" => 'required'
            , "document.capex_no" => 'required_if:document.id,5'
            , "document.name" => 'required'
            , "document.payment_type" => 'required'
            , "document.no" => 'required_if:document.id,1,2'
            , "document.date" => 'required_if:document.id,1,5,2'
            , "document.amount" => 'required_if:document.id,1,5,2,6,8,7|numeric'
            , "document.remarks" => 'nullable'
            , "document.company.id" => 'required'
            , "document.company.name" => 'required'
            , "document.department.id" => 'required'
            , "document.department.name" => 'required'
            , "document.location.id" => 'required'
            , "document.location.name" => 'required'
            , "document.supplier.id" => 'required'
            , "document.supplier.name" => 'required'
            , "document.from" => 'required_if:document.id,6,7'
            , "document.to" => 'required_if:document.id,6,7'
            , "document.category.id" => 'required_if:document.id,1,2,4,5'
            , "document.category.name" => 'required_if:document.id,1,2,4,5'

            , "po_group.*.no" => 'required'
            , "po_group.*.amount" => 'required|numeric'
            , "po_group.*.rr_no" => 'nullable'

            , "document.utility.receipt_no" => 'nullable'
            , "document.utility.consumption" => 'nullable'

            , "document.utility.location.id" => 'required_if:document.id,6'
            , "document.utility.location.name" => 'required_if:document.id,6'
            , "document.utility.category.id" => 'required_if:document.id,6'
            , "document.utility.category.name" => 'required_if:document.id,6'
            , "document.utility.account_no.id" => 'required_if:document.id,6'
            , "document.utility.account_no.no" => 'required_if:document.id,6'

            , "document.pcf_batch.name" => 'required_if:document.id,8'
            , "document.pcf_batch.letter" => 'required_if:document.id,8'
            , "document.pcf_batch.date" => 'required_if:document.id,8'

            , "document.payroll.clients.*.id" => 'required_if:document.id,7'
            , "document.payroll.clients.*.name" => 'required_if:document.id,7'
            , "document.payroll.type" => 'required_if:document.id,7'
            , "document.payroll.category.id" => 'required_if:document.id,7'
            , "document.payroll.category.name" => 'required_if:document.id,7'
            
            , "document.reference.id" => 'required_if:document.id,4'
            , "document.reference.no" => 'required_if:document.id,4'
            , "document.reference.amount" => 'nullable'
            , "document.reference.allowable" => 'nullable'
            , "document.reference.qty" => 'nullable'
            , "document.reference.type" => 'required_if:document.id,4'

            ,"prm_group.*.period_covered" => 'nullable'
            ,"prm_group.*.gross_amount" => 'nullable'
            ,"prm_group.*.wht" => 'nullable'
            ,"prm_group.*.net_of_amount" => 'nullable'
            ,"prm_group.*.cheque_date" => 'nullable'
            ,"prm_group.*.amortization" => 'nullable'
            ,"prm_group.*.interest" => 'nullable'
            ,"prm_group.*.cwt" => 'nullable'
            ,"prm_group.*.principal" => 'nullable'

            , "document.batch_no" => 'nullable'
            , "document.release_date" => 'nullable'

            ,"autoDebit_group.*.pn_no"=>'nullable'
            ,"autoDebit_group.*.interest_from"=>'nullable'
            ,"autoDebit_group.*.interest_to"=>'nullable'
            ,"autoDebit_group.*.outstanding_amount"=>'nullable'
            ,"autoDebit_group.*.interest_rate"=>'nullable'
            ,"autoDebit_group.*.no_of_days"=>'nullable'
            ,"autoDebit_group.*.principal_amount"=>'nullable'
            ,"autoDebit_group.*.interest_due"=>'nullable'
            ,"autoDebit_group.*.cwt"=>'nullable'
            ,"autoDebit_group.*.dst"=>'nullable'

        ];
    }

    public function attributes()
    {
        return [
            'requestor.id' => 'Id',
            'requestor.id_prefix' => 'Id prefix',
            'requestor.id_no' => 'User id no',
            'requestor.role' => 'Role',
            'requestor.position' => 'Position',
            'requestor.first_name' => 'First name',
            'requestor.middle_name' => 'Middle name',
            'requestor.last_name' => 'Last name',
            'requestor.suffix' => 'Suffix',
            'requestor.department' => 'Department',
            
            'document.id' => 'Document id',
            'document.capex_no' => 'CAPEX number',
            'document.name' => 'Document name',
            'document.company.id' => 'Company id',
            'document.company.name' => 'Company name',
            'document.department.id' => 'Department id',
            'document.department.name' => 'Department name',
            'document.location.id' => 'Location id',
            'document.location.name' => 'Location name',
            'document.supplier.id' => 'Supplier id',
            'document.supplier.name' => 'Supplier name',
            'document.payment_type' => 'Payment type',
            'document.no' => 'Document number',
            'document.date' => 'Document date',
            'document.amount' => 'Document amount',
            'document.remarks' => 'Remarks',
            
             "document.from" => 'From'
            , "document.to" => 'To'
            , "document.utility.consumption" => 'Consumption'
            , "documentutility.receipt_no" => 'Receipt number'
            , "document.utility.location.id" => 'Utility Location ID'
            , "document.utility.location.name" => 'Utility Location Name'
            , "document.utility.category.id" => 'Utility Category ID'
            , "document.utility.category.name" => 'Utility Category Name'
            , "document.account_no.id" => 'Account number id'
            , "document.account_no.no" => 'Account number'
            
            , "document.pcf_batch.letter" => 'PCF batch letter'
            , "document.pcf_batch.date" => 'PCF batch date'
            , "document.pcf_batch.name" => 'PCF batch name'
            
            , 'document.payroll.clients.*.id' => 'Payroll ID'
            , 'document.payroll.clients.*.namr' => 'Payroll name'
            , "document.payroll.type" => 'Payroll type'
            , "document.payroll.category.id" => 'Payroll category id'
            , "document.payroll.category.name" => 'Payroll category'

            , "document.reference.id" => 'Reference id'
            , "document.reference.type" => 'Reference type'
            
            ,'po_group.*.no' => 'PO number'
            ,'po_group.*.amount' => 'PO amount'
            ,'po_group.*.rr_no' => 'RR number'

            , "document.batch_no" => 'Batch no.'
            
            ,"autoDebit_group.*.pn_no"=>'Promisory note number'
            ,"autoDebit_group.*.interest_from"=>'Interest from'
            ,"autoDebit_group.*.interest_to"=>'Interest to'
            ,"autoDebit_group.*.outstanding_amount"=>'Outstanding amount'
            ,"autoDebit_group.*.interest_rate"=>'Interest rate'
            ,"autoDebit_group.*.no_of_days"=>'No of days'
            ,"autoDebit_group.*.principal_amount"=>'Principal amount'
            ,"autoDebit_group.*.interest_due"=>'Interest due'
            ,"autoDebit_group.*.cwt"=>'Computed witholding tax'
        ];
    }
    
    public function messages(){
        return [
            'required' => ':attribute is required.',
            'required_if' => ':attribute is required.',
            'numeric' => ':attribute must be in number format.',
            'min' => ':attribute amount may not be greater than :min.',
            'max' => ':attribute amount may not be greater than :max.'
            // "document.amount.numeric"=>"Document amount must be numeric"
        ];
    }
}
