<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Methods\TransactionFlow;

class TransactionFlowController extends Controller
{



    public function pullRequest(Request $request){
        $process =  $request['process'];
        return TransactionFlow::pullRequest($process,$id=0);
    }

    public function pullSingleRequest(Request $request,$id){
        $process =  $request['process'];
        return TransactionFlow::pullSingleRequest($process,$id);
    }

    public function receivedRequest(Request $request,$id){
        $process =  $request['process'];
        $description = $request['description'];
        $reason_id = $request['reason_id'];
        $remarks = $request['remarks'];
        if(!isset($date_received)){
            $date_received = date('Y-m-d H:i:s');
         }
        return TransactionFlow::receivedRequest($process, $id, $description, $reason_id, $remarks);
    }
}
