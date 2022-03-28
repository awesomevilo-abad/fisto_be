<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class PODuplicateFull implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $validateTransaction = $transactions = DB::table('transactions')
        ->leftJoin('p_o_batches','transactions.tag_id','=','p_o_batches.tag_id')
        ->where('company_id',1)
        ->where('po_no','10002');
       $validateTransactionCount = $transactions->count();
        
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Transaction already exist';
    }
}
