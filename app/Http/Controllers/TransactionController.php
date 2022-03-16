<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exceptions\FistoException;
use App\Methods\TransactionValidationMethod;

class TransactionController extends Controller
{
    public function index()
    {
    }

    public function store(Request $request)
    {
        $fields = $request->all();
        $errorMessages = [];

        $modelNames = ['Document','Supplier'];
        $tables = ['documents','suppliers'];
        $basis = [$fields['document']['id'],$fields['document']['supplier']['id']];
        
        $documentValidation =  $this->getMultipleFieldExist($modelNames,$tables,$basis,$errorMessages);
        if($documentValidation){
            throw new FistoException("Document details not registered.", 404, NULL, $documentValidation);
        }
   
        $date_requested = date('Y-m-d H:i:s');
        $transaction_id = $this->getTransactionID($fields['requestor']['department']);
        $transaction_id = $this->getTransactionCode($fields['requestor']['department'], $transaction_id);

        $fields['document']['amount'] = $this->convertToFloat($fields['document']['amount']);

        if(($fields['document']['id'] == 7)){
            $response =   TransactionValidationMethod::padValidation($fields,$date_requested,$transaction_id);

        }
   
   
   
           return $response;
    }

}
