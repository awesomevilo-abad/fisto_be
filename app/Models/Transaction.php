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
        , 'capex_no'
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
        ,"tag_no"

        ,"utilities_category_id"
        ,"utilities_category"
        ,"utilities_location_id"
        ,"utilities_location"
        ,"utilities_account_no_id"
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

        ,"referrence_type"
        ,"referrence_no"
        ,"referrence_amount"
        ,"referrence_qty"
        ,"referrence_id"
        ,"period_covered"
        ,"prm_multiple_from" 
        ,"prm_multiple_to"
        ,"cheque_date"
        ,"gross_amount"
        ,"witholding_tax"
        ,"net_amount"
        ,"total_gross"
        ,"total_cwt"
        ,"total_net"
        
        ,"release_date"
        ,"batch_no"
        ,"amortization"
        ,"interest"
        ,"cwt"
        ,"dst"
        ,"principal"

    ];


    

    protected $attributes = [
        "status"=>"Pending",
        "state"=>"pending"
    ];

    protected $casts = [
        // 'po_group' => 'array',
        'referrence_group' => 'array',
        'payroll_client' => 'array',
    ];

    public function po_details(){
        return $this->hasMany(POBatch::class,'request_id','request_id');
    }

    public function users(){
        return $this->belongsTo(User::class,'users_id','id');
    }

    public function supplier(){
        return $this->belongsTo(Supplier::class,'supplier_id','id')->select(['id', 'supplier_type_id', 'name']);
    }

    public function auto_debit(){
        return $this->hasMany(DebitBatch::class,'request_id','request_id')->select(['request_id','pn_no','interest_from','interest_to','outstanding_amount','interest_rate','no_of_days','principal_amount','interest_due','cwt','dst']);
    }

    public function cheque(){
        return $this->hasMany(Cheque::class,'transaction_id','transaction_id')->latest();
    }

    public function transaction_voucher(){
        return $this->hasMany(Associate::class,'transaction_id','transaction_id')
        ->where('status','voucher-voucher')
        ->select('transaction_id','tag_id','id',
        'receipt_type','percentage_tax','witholding_tax','net_amount','approver_id','approver_name','date_status as date','status','reason_id','remarks')->latest();
    }
    
    public function transaction_cheque(){
        return $this->hasMany(Treasury::class,'transaction_id','transaction_id')->select('transaction_id','tag_id','id',
       'date_status as date','status','reason_id','remarks')->where('status','cheque-cheque')->latest();
    }

    public function clear(){
        return $this->hasMany(Clear::class,'tag_id','tag_no')->select('tag_id','id',
        'date_status as date','status','date_cleared')->latest();
    }

    // Transaction Flow

    public function tag(){
        return $this->hasMany(Tagging::class,'request_id','request_id')->select('request_id','tag_id','transaction_id','date_status as date','status','distributed_id','distributed_name','reason_id','remarks')->latest()->limit(1);
    }

    public function voucher(){
        return $this->hasMany(Associate::class,'tag_id','tag_no')->select('transaction_id','tag_id','id',
        'receipt_type','percentage_tax','witholding_tax','net_amount','approver_id','approver_name','date_status as date','status','reason_id','remarks')->latest()->limit(1);
    }

    public function approve(){
        return $this->hasMany(Approver::class,'tag_id','tag_no')->select('transaction_id','tag_id','id',
        'distributed_id','distributed_name','date_status as date','status','reason_id','remarks')->latest()->limit(1);
    }


    public function cheques(){
        return $this->hasMany(Treasury::class,'tag_id','tag_no')->select('transaction_id','tag_id','id',
        'date_status as date','status','reason_id','remarks')->latest()->limit(1);
    }
    
    public function transmit(){
        return $this->hasMany(Transmit::class,'tag_id','tag_no')->select('transaction_id','tag_id','id',
        'date_status as date','status')->latest()->limit(1);
    }
    
    public function release(){
        return $this->hasMany(Release::class,'tag_id','tag_no')->select('transaction_id','tag_id','id',
        'distributed_id','distributed_name','date_status as date','status','reason_id','remarks')->latest()->limit(1);
    }

    public function file(){
        return $this->hasMany(File::class,'tag_id','tag_no')->select('transaction_id','tag_id','id',
        'receipt_type','percentage_tax','witholding_tax','net_amount','approver_id','approver_name','date_status as date','status','reason_id','remarks')->latest()->limit(1);
    }

    public function reverse(){
        return $this->hasMany(Reverse::class,'tag_id','tag_no')->select('transaction_id','tag_id','id',
        'user_role','user_id','user_name','date_status as date','status','reason_id','remarks','distributed_id','distributed_name')->latest()->limit(1);
    }

    public function transfer_voucher(){
        return $this->hasMany(Transfer::class,'tag_id','tag_no')->where('process','voucher')->latest()->limit(1);
    }
    public function transfer_transmit(){
        return $this->hasMany(Transfer::class,'tag_id','tag_no')->where('process','transmit')->latest()->limit(1);
    }
    public function transfer_file(){
        return $this->hasMany(Transfer::class,'tag_id','tag_no')->where('process','file')->latest()->limit(1);
    }


}
