<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exceptions\FistoException;
use App\Methods\TransactionValidationMethod;
use App\Http\Controllers\Validation\PadValidationController;

class TransactionController extends Controller
{
    public function __construct(){
        // $this->fields = $request->all();
    }

    public function index()
    {
    }

    public function store(Request $request)
    {
        $fields=$request->all();
        $date_requested = date('Y-m-d H:i:s');
        $errorMessages = [];
        $modelNames = ['Document','Supplier'];
        $tables = ['documents','suppliers'];
        $basis = [$fields['document']['id'],$fields['document']['supplier']['id']];
        
        $documentValidation =  $this->getMultipleFieldExist($modelNames,$tables,$basis,$errorMessages);
        if($documentValidation){
            $this->resultResponse('not-registered','Document details',$documentValidation);
        }
   
        $transaction_id = $this->getTransactionID($fields['requestor']['department']);

        if(($fields['document']['id'] == 7)){
           return $response =   TransactionValidationMethod::padValidation($fields,$date_requested,$transaction_id);

        }
   
   
   
           return $response;
    }

}
