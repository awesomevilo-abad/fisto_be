<?php

namespace App\Http\Controllers;

use App\Models\PayrollCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Routing\ResponseFactory;
use App\Exceptions\FistoException;

class PayrollCategoryController extends Controller
{
    
  public function index(Request $request,bool $status,int $rows)
  {
    $payroll_category = PayrollCategory::withTrashed()
    ->where(function ($query) use ($status){
      return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
    })
    ->latest('updated_at')
    ->paginate($rows);
    
    if(count($payroll_category)==true){
      return $this->result(200,"Payroll Category has been fetched.",$payroll_category);
    }
    throw new FistoException("No records found.", 404, NULL, []);
  }
  
  public function all(Request $request,$status)
  {
    $status = (bool)$status;

    $payroll_categories = DB::table('payroll_categories')
      ->select(['id', 'category'])
      ->where(function ($query) use ($status) {
        if ($status == true) $query->whereNull('deleted_at');
        else  $query->whereNotNull('deleted_at');
      })
      ->latest('category')
      ->get();

    if (count($payroll_categories) == true) {
      $result = [
        "code" => 200,
        "message" => "Payroll Categories has been fetched.",
        "result" => $payroll_categories
      ];
          
      return response($result);
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }
    
  public function show(Request $request,$id)
  {
    $payroll_category = PayrollCategory::withTrashed()
    ->where('id',$id)
    ->get();

    if(count($payroll_category)==true){
      return $this->result(200,"Payroll Category has been fetched",$payroll_category);
    }
    throw new FistoException("No records found.", 404, NULL, []);
  }

  public function search(Request $request,bool $status,int $rows)
  {
    $value = $request['value'];
    $payroll_category = PayrollCategory::withTrashed()
    ->where(function ($query) use ($status){
      return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
    })
    ->where('category', 'like', '%'.$value.'%')
    ->latest('updated_at')
    ->paginate($rows);
    
    if(count($payroll_category)==true){
      return $this->result(200,"Payroll Category has been fetched.",$payroll_category);
    }
    throw new FistoException("No records found.", 404, NULL, []);
  }

  public function archive(Request $request,$id)
  {
    $softDeletePayrollCategory = PayrollCategory::where('id', $id)->delete();
    if ($softDeletePayrollCategory == true) {
      return $this->result(200,"Payroll Category has been archived",[]);
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }

  public function restore(Request $request, $id)
  {
      if(!PayrollCategory::onlyTrashed()->find($id)){
          throw new FistoException("No records found.", 404, NULL, []);
      }
      $restoreSoftDelete = PayrollCategory::onlyTrashed()->find($id)->restore();
      if ($restoreSoftDelete == 1) {
          return $this->result(200,"Succefully Restored",[]);
      }
  }
  
    
  public function store(Request $request)
  {
      $fields = $request->validate([
        'category' => ['required','string']
      ]);

      $validateDuplicatePayrollCategory = PayrollCategory::withTrashed()->firstWhere('category', $fields['category']);

      if (!empty($validateDuplicatePayrollCategory))
        throw new FistoException("Payroll Category already registered.", 409, NULL, [
          "error_field" => "category"
        ]);
        $payroll_category = PayrollCategory::create($fields);
        return $this->result(201,"Payroll Category has been saved.",$payroll_category);

  }

  public function update(Request $request, $id)
  {
    $fields = $request->validate([
      'category' => ['required','string']
    ]);
    
    $payroll_category = PayrollCategory::withTrashed()->find($id);
    if(!empty($payroll_category) == true){
      $payroll_category->category = $fields['category'];
      $payroll_category->save();
      return $this->result(200,"Payroll Category has been updated",$payroll_category);
    }
    throw new FistoException("No records found.", 404, NULL, []);
  }
}
