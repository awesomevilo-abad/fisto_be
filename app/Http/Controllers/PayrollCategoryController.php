<?php

namespace App\Http\Controllers;

use App\Models\PayrollCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Routing\ResponseFactory;
use App\Exceptions\FistoException;

class PayrollCategoryController extends Controller
{
    
  public function index(Request $request)
  {
    $status =  $request['status'];
    $rows =  (empty($request['rows']))?10:(int)$request['rows'];
    $search =  $request['search'];
    
    $payroll_category = PayrollCategory::withTrashed()
    ->where(function ($query) use ($status){
      return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
    })
    ->where('category', 'like', '%'.$search.'%')
    ->latest('updated_at')
    ->paginate($rows);
    
    if(count($payroll_category)==true){
      return $this->result(200,"Payroll Category has been fetched.",$payroll_category);
    }
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
    $model = new PayrollCategory();
    $fields = $request->validate([
      'category' => ['required','string']
    ]);
    
    $payroll_category = PayrollCategory::withTrashed()->find($id);
    $is_unique = $this->isUnique($model,'Payroll Category',['category'],[$fields['category']],$id);

    if(!empty($payroll_category) == true){
      $payroll_category->category = $fields['category'];
      return $this->validateIfNothingChangeThenSave($payroll_category,'Payroll Category');
    }
    throw new FistoException("No records found.", 404, NULL, []);
  }

  public function change_status(Request $request,$id){
    $status = $request['status'];
    $model = new PayrollCategory();
    return $this->change_masterlist_status($status,$model,$id,'Payroll Category');
  }

}
