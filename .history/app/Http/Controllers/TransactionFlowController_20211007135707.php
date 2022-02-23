<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Methods\TransactionFlow;

class TransactionFlowController extends Controller
{



    public function pullRequest(Request $request){
        $process =  $request['process'];
        $subprocess =  $request['subprocess'];
        return TransactionFlow::pullRequest($process,$subprocess,$id=0);
    }

    public function pullSingleRequest(Request $request,$id){
        $process =  $request['process'];
        $subprocess =  $request['subprocess'];
        return TransactionFlow::pullSingleRequest($process,$subprocess,$id);
    }

    public function receivedRequest(Request $request,$id){
        return TransactionFlow::receivedRequest($request, $id);
    }

    public function searchRequest(Request $request){
        $process =  $request['process'];
        $subprocess =  $request['subprocess'];
        $search =  $request['search'];
        return TransactionFlow::searchRequest($process,$subprocess,$id=0);
    }

}
