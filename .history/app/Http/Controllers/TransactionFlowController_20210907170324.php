<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Methods\TransactionFlow;

class TransactionFlowController extends Controller
{



    public function pullRequest(Request $request){
        $process =  $request['process'];
        return TransactionFlow::pullRequest($process);
    }

    public function receivedRequest(){

    }
}
