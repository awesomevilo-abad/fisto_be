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
    
    $payroll_client = PayrollClient::withTrashed()
    ->where(function ($query) use ($status){
      return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
    })
    ->where('client', 'like', '%'.$search.'%')
    ->latest('updated_at')
    ->paginate($rows);
    
    if(count($payroll_client)==true){
      return $this->result(200,"Payroll Client has been fetched.",$payroll_client);
    }
    throw new FistoException("No records found.", 404, NULL, []);
  }
    
  public function show(Request $request,$id)
  {
    $payroll_client = PayrollClient::withTrashed()
    ->where('id',$id)
    ->get();

    if(count($payroll_client)==true){
      return $this->result(200,"Payroll Client has been fetched",$payroll_client);
    }
    throw new FistoException("No records found.", 404, NULL, []);
  }
    
  public function store(Request $request)
  {
      $fields = $request->validate([
        'client' => ['required','string']
      ]);

      $validateDuplicatePayrollClient = PayrollClient::withTrashed()->firstWhere('client', $fields['client']);

      if (!empty($validateDuplicatePayrollClient))
        throw new FistoException("Payroll Client already registered.", 409, NULL, [
          "error_field" => "client"
        ]);
        $payroll_client = PayrollClient::create($fields);
        return $this->result(201,"Payroll Client has been saved.",$payroll_client);

  }

  public function change_status(Request $request,$id){
    $status = $request['status'];
    $model = new PayrollClient();
    return $this->change_masterlist_status($status,$model,$id,'Payroll Client');
  }

  public function update(Request $request, $id)
  {
    $model = new PayrollClient();
    $fields = $request->validate([
      'client' => ['required','string']
    ]);

    $payroll_client = PayrollClient::withTrashed()->find($id);
    $is_unique = $this->isUnique($model,'Payroll Client',['client'],[$fields['client']],$id);
    if(!empty($payroll_client) == true){
      $payroll_client->client = $fields['client'];
      return $this->validateIfNothingChangeThenSave($payroll_client,'Payroll Client');
    }
    throw new FistoException("No records found.", 404, NULL, []);
  }
}
