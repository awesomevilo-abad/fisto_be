<?php

namespace App\Methods;

 use App\Exceptions\FistoException;
 use Illuminate\Support\Facades\DB;
 use Illuminate\Validation\ValidationException;

 class PADValidationMethod{

    public static function validatePOFull($company_id,$po_group){
        $po_nos = array_column($po_group,'no');
        
        $transactions = DB::table('transactions')
        ->leftJoin('p_o_batches','transactions.request_id','=','p_o_batches.request_id')
        ->where('company_id',$company_id)
        ->whereIn('po_no',$po_nos);
       $validateTransactionCount = $transactions->get();
      
       if(!empty($validateTransactionCount)){
            return GenericMethod::resultLaravelFormat('po_group.no',"The PO number has already been taken.: ".$validateTransactionCount->pluck('po_no')->implode(','));
        }
    }
 }