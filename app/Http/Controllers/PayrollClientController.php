<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;
use App\Models\PayrollClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollClientController extends Controller
{
  public function index(Request $request)
  {
    $status =  $request['status'];
    $rows =  (empty($request['rows']))?10:(int)$request['rows'];
    $search =  $request['search'];
    $paginate = (isset($request['paginate']))? $request['paginate']:$paginate = 1;
    
    $payroll_client = PayrollClient::withTrashed()
    ->where(function ($query) use ($status){
      return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
    })
    ->where('client', 'like', '%'.$search.'%')
    ->latest('updated_at');
    
    if ($paginate == 1){
      $payroll_client = $payroll_client
      ->paginate($rows);
    }else if ($paginate == 0){
      $payroll_client = $payroll_client
      ->get(['id','client as name']);
      if(count($payroll_client)==true){
          $payroll_client = array("payroll_clients"=>$payroll_client);;
      }
    }
    
    if(count($payroll_client)==true){
      return $this->resultResponse('fetch','Payroll Client',$payroll_client);
    }
    return $this->resultResponse('not-found','Payroll Client',[]);
  }
        
  public function store(Request $request)
  {
      $fields = $request->validate([
        'client' => ['required','string']
      ]);

      $validateDuplicatePayrollClient = PayrollClient::withTrashed()->firstWhere('client', $fields['client']);
      if (!empty($validateDuplicatePayrollClient))
        return $this->resultResponse('registered','Payroll Client',["error_field" => "client"]);
        
        $payroll_client = PayrollClient::create($fields);
        return $this->resultResponse('save','Payroll Client',$payroll_client);
  }

  public function change_status(Request $request,$id){
    $status = $request['status'];
    $model = new PayrollClient();
    return $this->change_masterlist_status($status,$model,$id,'Payroll client');
  }

  public function update(Request $request, $id)
  {
    $model = new PayrollClient();
    $fields = $request->validate([
      'client' => ['required','string']
    ]);

    $payroll_client = PayrollClient::withTrashed()->find($id);
    $is_unique = $this->isUnique($model,'Payroll client',['client'],[$fields['client']],$id);
    if(!empty($payroll_client) == true){
      $payroll_client->client = $fields['client'];
      return $this->validateIfNothingChangeThenSave($payroll_client,'Payroll client');
    }
    return $this->resultResponse('not-found','Payroll Client',[]);
  }
}
