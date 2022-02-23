<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;
use App\Models\PayrollClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollClientController extends Controller
{
  public function index(Request $request,bool $status,int $rows)
  {
    $payroll_client = PayrollClient::withTrashed()
    ->where(function ($query) use ($status){
      return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
    })
    ->latest('updated_at')
    ->paginate($rows);
    
    if(count($payroll_client)==true){
      return $this->result(200,"Payroll Client has been fetched.",$payroll_client);
    }
    throw new FistoException("No records found.", 404, NULL, []);
  }
  
  public function all(Request $request,$status)
  {
    $status = (bool)$status;

    $payroll_clients = DB::table('payroll_clients')
      ->select(['id', 'client'])
      ->where(function ($query) use ($status) {
        if ($status == true) $query->whereNull('deleted_at');
        else  $query->whereNotNull('deleted_at');
      })
      ->latest('client')
      ->get();

    if (count($payroll_clients) == true) {
      $result = [
        "code" => 200,
        "message" => "Payroll clients has been fetched.",
        "result" => $payroll_clients
      ];
          
      return response($result);
    }
    else
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

  public function search(Request $request,bool $status,int $rows)
  {
    $value = $request['value'];
    $payroll_client = PayrollClient::withTrashed()
    ->where(function ($query) use ($status){
      return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
    })
    ->where('client', 'like', '%'.$value.'%')
    ->latest('updated_at')
    ->paginate($rows);
    
    if(count($payroll_client)==true){
      return $this->result(200,"Payroll Client has been fetched.",$payroll_client);
    }
    throw new FistoException("No records found.", 404, NULL, []);
  }

  public function archive(Request $request,$id)
  {
    $softDeletePayrollClient = PayrollClient::where('id', $id)->delete();
    if ($softDeletePayrollClient == true) {
      return $this->result(200,"Payroll Client has been archived",[]);
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }
  
  public function restore(Request $request, $id)
  {
      if(!PayrollClient::onlyTrashed()->find($id)){
          throw new FistoException("No records found.", 404, NULL, []);
      }
      $restoreSoftDelete = PayrollClient::onlyTrashed()->find($id)->restore();
      if ($restoreSoftDelete == 1) {
          return $this->result(200,"Succefully Restored",[]);
      }
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

  public function update(Request $request, $id)
  {
    $fields = $request->validate([
      'client' => ['required','string']
    ]);

    $payroll_client = PayrollClient::withTrashed()->find($id);
    if(!empty($payroll_client) == true){
      $payroll_client->client = $fields['client'];
      $payroll_client->save();
      return $this->result(200,"Payroll Client has been updated",$payroll_client);
    }
    throw new FistoException("No records found.", 404, NULL, []);
  }
}
