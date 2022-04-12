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
            , "requestor.middle_name" => 'required'
            , "requestor.last_name" => 'required'
            , "requestor.suffix" => 'nullable'
            , "requestor.department" => 'required'
           
            , "document.id" => 'required'
            , "document.name" => 'required'
            , "document.payment_type" => 'required'
            , "document.no" => 'required_if:document.id,1,5,2|unique:transactions,document_no'
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

            , "po_group.*.no" => 'required|numeric'
            , "po_group.*.amount" => 'required|numeric'
            , "po_group.*.rr_no" => 'required'
            
            , "document.from" => 'required_if:document.id,6'
            , "document.to" => 'required_if:document.id,6'
            , "document.account_no" => 'required_if:document.id,6'
            , "document.consumption" => 'nullable'
            , "document.receipt_no" => 'nullable'
            , "document.to" => 'required_if:document.id,6'
            , "document.utility_category.id" => 'required_if:document.id,6'
            , "document.utility_category.name" => 'required_if:document.id,6'

            , "document.pcf_batch.name" => 'required_if:document.id,8'
            , "document.pcf_batch.letter" => 'required_if:document.id,8'
            , "document.pcf_batch.date" => 'required_if:document.id,8'

            , "document.from" => 'required_if:document.id,7'
            , "document.to" => 'required_if:document.id,7'
            , "document.payroll.clients.*.id" => 'required_if:document.id,7'
            , "document.payroll.clients.*.name" => 'required_if:document.id,7'
            , "document.payroll.type" => 'required_if:document.id,7'
            , "document.payroll.category.id" => 'required_if:document.id,7'
            , "document.payroll.category.name" => 'required_if:document.id,7'
            
            , "reference.id" => 'required_if:document.id,4'
            , "reference.type" => 'required_if:document.id,4'
            , "reference.no" => 'required_if:document.id,4'
            , "reference.amount" => 'required_if:document.id,4'
            // // SELECTED CATEGORY (CONDITIONAL)
            // , "category_id" => 'nullable'
            // , "category" => 'nullable'

            // // PAYMENT TYPE BASED ON FE SELECTED
            // , "payment_type" => 'required'

            // // SELECTED COMPANY
            // , "company_id" => 'required'
            // , "company" => 'required'

            // // INPUTTED DOCUMENT NO
            // , "document_no" => 'nullable'

            // // SELECTED SUPPLIER
            // , "supplier_id" => 'required'
            // , "supplier" => 'required'

            // // INPUTTED DOCUMENT DATE & AMOUNT
            // , "document_date" => 'nullable'
            // , "document_amount" => 'nullable'

            // // OPTIONAL(ADD REMARKS)
            // , "remarks" => 'nullable'

            // // CREATE PO GROUP BATCH ID (LINK TO PO BATCHES TABLE WITH AMOUNT)
            // , "po_group" => 'nullable'

            // // CREATE REF GROUP BATCH ID (LINK TO REF BATCHES TABLE WITH AMOUNT)
            // , "referrence_group" => 'nullable'
            // , "reason_id" => 'nullable'
            // , "reason" => 'nullable'
            // , "pcf_date" => 'nullable'
            // , "pcf_letter" => 'nullable'
            // , "utilities_from" => 'nullable'
            // , "utilities_to" => 'nullable'

            // // Additionals
            // ,"po_total_amount"=> 'nullable'
            // ,"po_total_qty"=> 'nullable'
            // ,"rr_total_qty"=> 'nullable'
            // ,"referrence_total_amount"=> 'nullable'
            // ,"referrence_total_qty"=> 'nullable'
            // ,"balance_document_po_amount"=> 'nullable'
            // ,"balance_document_ref_amount"=> 'nullable'
            // ,"balance_po_ref_amount"=> 'nullable'
            // ,"balance_po_ref_qty"=> 'nullable'

            // ,"tagging_request_id"=> 'nullable'
            // ,"utilities_category"=> 'nullable'
            // ,"utilities_account_no"=> 'nullable'
            // ,"utilities_consumption"=> 'nullable'
            // ,"utilities_uom"=> 'nullable'
            // ,"utilities_receipt_no"=> 'nullable'
            // ,"payroll_client"=> 'nullable'
            // ,"payroll_category"=> 'nullable'
            // ,"payroll_type"=> 'nullable'
            // ,"payroll_from"=> 'nullable'
            // ,"payroll_to"=> 'nullable'
            // ,"is_allowable"=> 'nullable'

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
            , "document.account_no" => 'Account number'
            , "document.consumption" => 'Consumption'
            , "document.receipt_no" => 'Receipt number'
            , "document.utility_category.id" => 'Utility Category ID'
            , "document.utility_category.name" => 'Utility Category Name'
            
            , "document.pcf_batch.letter" => 'PCF batch letter'
            , "document.pcf_batch.date" => 'PCF batch date'
            , "document.pcf_batch.name" => 'PCF batch name'
            
            , "document.from" => 'from'
            , "document.to" => 'to'
            , 'document.payroll.clients.*.id' => 'Payroll ID'
            , 'document.payroll.clients.*.namr' => 'Payroll name'
            , "document.payroll.type" => 'Payroll type'
            , "document.payroll.category.id" => 'Payroll category id'
            , "document.payroll.category.name" => 'Payroll category'

            , "reference.id" => 'Reference id'
            , "reference.type" => 'Reference type'
            , "reference.no" => 'Reference number'
            , "reference.amount" => 'Reference amount'
            
            ,'po_group.*.no' => 'PO number'
            ,'po_group.*.amount' => 'PO amount'
            ,'po_group.*.rr_no' => 'RR number'
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
