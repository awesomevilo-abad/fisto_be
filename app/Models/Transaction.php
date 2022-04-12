<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'users_id'
        , 'id_prefix'
        , 'id_no'
        , 'first_name'
        , 'middle_name'
        , 'last_name'
        , 'suffix'
        , 'department_details'
        , 'transaction_id'
        , 'request_id'
        , 'document_id'
        , 'document_type'
        , 'document_date'
        , 'category_id'
        , 'category'
        , 'company_id'
        , 'company'
        , 'department_id'
        , 'department'
        , 'location_id'
        , 'location'
        , 'supplier_id'
        , 'supplier'
        , 'po_total_amount'
        , 'po_total_qty'
        , 'rr_total_qty'
        , 'referrence_total_amount'
        , 'referrence_total_qty'
        , 'date_requested'
        , 'remarks'
        , 'payment_type'
        , 'status'
        , 'state'
        , 'reason_id'
        , 'reason'
        , 'document_no'
        , 'document_amount'
        , 'pcf_name'
        , 'pcf_date'
        , 'pcf_letter'
        , 'utilities_from'
        , 'utilities_to'

        ,"po_total_amount"
        ,"po_total_qty"
        ,"rr_total_qty"
        ,"referrence_total_amount"
        ,"referrence_total_qty"
        ,"balance_document_po_amount"
        ,"balance_document_ref_amount"
        ,"balance_po_ref_amount"
        ,"balance_po_ref_qty"
        ,"tagging_tag_id"

        ,"utilities_category"
        ,"utilities_account_no"
        ,"utilities_consumption"
        ,"utilities_uom"
        ,"utilities_receipt_no"
        ,"payroll_client"
        ,"payroll_category_id"
        ,"payroll_category"
        ,"payroll_type"
        ,"payroll_from"
        ,"payroll_to"

    ];

    protected $attributes = [
        "status"=>"Pending",
        "state"=>"request"
    ];

    protected $casts = [
        // 'po_group' => 'array',
        'referrence_group' => 'array',
        'payroll_client' => 'array',
    ];


}
