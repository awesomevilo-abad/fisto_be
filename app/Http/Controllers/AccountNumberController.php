<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;

use App\Models\AccountNumber;
use App\Models\UtilityLocation;
use App\Models\UtilityCategory;
use App\Models\Supplier;
use App\Http\Requests\AccountNumberRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccountNumberController extends Controller
{
  public function index(Request $request,bool $status,int $rows)
  {
    $account_numbers = DB::table('account_numbers as AN')
      ->join('utility_locations as UL', 'AN.location_id', 'UL.id')
      ->join('utility_categories as UC', 'AN.category_id', 'UC.id')
      ->join('suppliers as SU', 'AN.supplier_id', 'SU.id')
      ->select(
        'AN.id',
        'AN.account_no',
        'AN.location_id',
        'UL.location as location_name',
        'AN.category_id',
        'UC.category as category_name',
        'AN.supplier_id',
        'SU.supplier_name',
        'AN.updated_at',
        'AN.deleted_at'
      )
      ->where(function ($query) use ($status){
        return ($status==true)?$query->whereNull('AN.deleted_at'):$query->whereNotNull('AN.deleted_at');
      })
      ->latest('AN.updated_at')
      ->paginate($rows);

    if (count($account_numbers) == true) {
      return $this->result(200,"Account numbers has been fetched.",$account_numbers);
    }
    else{
      throw new FistoException("No records found.", 404, NULL, []);
    }
  }

  public function all(Request $request,$status)
    {
      $status = (bool)$status;

      $account_numbers = DB::table('account_numbers')
        ->select(['id','account_no'])
        ->where(function ($query) use ($status) {
          if ($status == true) $query->whereNull('deleted_at');
          else  $query->whereNotNull('deleted_at');
        })
        ->latest('account_no')
        ->get();

      if (count($account_numbers) == true) {
        $result = [
          "code" => 200,
          "message" => "Account numbers has been fetched.",
          "result" => $account_numbers
        ];
        
        return response($result);
      }
      else
        throw new FistoException("No records found.", 404, NULL, []);
    }
    
  public function show(Request $request,$id)
  {
    $account_number = AccountNumber::find($id);
    if (!empty($account_number)) {
      return $this->result(200,"Account number has been fetched.",$account_number);
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }

  public function search(Request $request,bool $status,int $rows)
  {
    $value = $request['value'];

    $account_numbers = DB::table('account_numbers as AN')
      ->join('utility_locations as UL', 'AN.location_id', 'UL.id')
      ->join('utility_categories as UC', 'AN.category_id', 'UC.id')
      ->join('suppliers as SU', 'AN.supplier_id', 'SU.id')
      ->select(
        'AN.id',
        'AN.account_no',
        'AN.location_id',
        'UL.location as location_name',
        'AN.category_id',
        'UC.category as category_name',
        'AN.supplier_id',
        'SU.supplier_name',
        'AN.updated_at',
        'AN.deleted_at'
      )
      ->where(function ($query) use ($status) {
        ($status == true)? $query->whereNull('AN.deleted_at'):$query->whereNotNull('AN.deleted_at');
      })
      ->where(function ($query) use ($value) {
        $query->where('AN.account_no', 'like', '%'.$value.'%')
        ->orWhere('UL.location', 'like', '%'.$value.'%')
        ->orWhere('UC.category', 'like', '%'.$value.'%')
        ->orWhere('SU.supplier_name', 'like', '%'.$value.'%');
      })
      ->latest('AN.updated_at')
      ->paginate($rows);

    if (count($account_numbers) == true) {
      return $this->result(200,"Account number has been fetched.",$account_numbers);
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }

  public function store(AccountNumberRequest $request)
  {
    $fields = $request->validated();

    $account_number_validateDuplicate = AccountNumber::withTrashed()->firstWhere([['account_no', $fields['account_no']],['category_id', $fields['category_id']]]);
    if (empty($account_number_validateDuplicate)) {
      $new_account_numbers = AccountNumber::create($fields);
      return $this->result(201,"New account number has been saved.",$new_account_numbers);
    }
    else
      throw new FistoException("Account number already registered.", 409, NULL, []);
  }
    
  public function update(AccountNumberRequest $request,$id)
  {
    $account_number = AccountNumber::find($id);
    
    $fields = $request->validated();

    if (!empty($account_number)) {
      $account_number_validateDuplicate = AccountNumber::withTrashed()->firstWhere([['id', '<>', $id],['account_no', $fields['account_no']],['category_id', $fields['category_id']]]);
      if (empty($account_number_validateDuplicate)) {
        $account_number->account_no = $fields['account_no'];
        $account_number->location_id = $fields['location_id'];
        $account_number->category_id = $fields['category_id'];
        $account_number->supplier_id = $fields['supplier_id'];
        $account_number->save();
        return $this->result(200,"Account number has been updated.",$account_number);
      }
      else
        throw new FistoException("Account number already registered.", 409, NULL, []);
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }

  public function archive(Request $request,$id)
  {
    $softDeleteAccountNumber = AccountNumber::where('id',$id)->delete();
    if ($softDeleteAccountNumber == true) {
      return $this->result(200,"Account number has been archived.",[]);
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }

  public function restore(Request $request,$id)
  {
    $softRestoreAccountNumber = AccountNumber::onlyTrashed()->where('id',$id)->restore();
    if ($softRestoreAccountNumber == true) {
      return $this->result(200,"Account number has been restored.",[]);
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }

  public function import(Request $request)
  {
    $data = $request->all();
    $data_validation_fields = $request->all();

    $account_number_masterlist = AccountNumber::withTrashed()->get();
    $utility_location_masterlist = UtilityLocation::withTrashed()->get();
    $utility_category_masterlist = UtilityCategory::withTrashed()->get();
    $supplier_masterlist = Supplier::get();

    $errorBag = [];
    $index = 2;
    $template = ['account_no','location','supplier','category'];
    $headers = 'Account No, Location, Supplier, Category';
    $keys = array_keys(current($data));

    $this->validateHeader($template,$keys,$headers);

    foreach($data as $account_number){
      $account_no = $account_number['account_no'];
      $category = $account_number['category'];
      $location = $account_number['location'];
      $supplier = $account_number['supplier'];
      
      $emptyCells= $this->validateEmptyCells($account_number,$index);

      $category_id = $this->getCategoryId($category,$utility_category_masterlist);
      $duplicates = $this->validateDuplicateInDBFrom2Params($account_no,$category_id,$category,$account_number_masterlist,$index);
      $existingLocations = $this->validateExistingLocation($location,$utility_location_masterlist,$index);
      $existingCategories = $this->validateExistingCategory($category,$utility_category_masterlist,$index);
      $existingSuppliers = $this->validateExistingSupplier($supplier,$supplier_masterlist,$index);
      
      (!empty(current($emptyCells)))?array_push($errorBag,current($emptyCells)):"none";
      (!empty(current($duplicates)))?array_push($errorBag,current($duplicates)):"none";
      (!empty(current($existingLocations)))?array_push($errorBag,current($existingLocations)):"none";
      (!empty(current($existingSuppliers)))?array_push($errorBag,current($existingSuppliers)):"none";
      (!empty(current($existingCategories)))?array_push($errorBag,current($existingCategories)):"none";
      $index++;
    }
    
    $data_validation_fields =  $this->removeFieldInArrayOfObjects($data_validation_fields,['location','supplier']);
    $duplicate_lines =  $this->getDuplicateLines($data_validation_fields);
    $excelDuplicates = $this->validateDuplicatesInAccountNumberExcel($duplicate_lines,$data_validation_fields);
    (!empty(current($excelDuplicates)))?array_push($errorBag,current($excelDuplicates)):"none";
    $errorBag = array_values(array_unique($errorBag,SORT_REGULAR));

    if(empty($errorBag)){
      foreach($data as $account_no){
        $inputted_supplier = $account_no['supplier'];
        $inputted_location = $account_no['location'];
        $inputted_category = $account_no['category'];

        $location = $utility_location_masterlist->filter(function ($query) use ($inputted_location){
          return (strtolower($query['location']) == strtolower($inputted_location)); 
        })->first()['id'];
        $category = $utility_category_masterlist->filter(function ($query) use ($inputted_category){
          return (strtolower($query['category']) == strtolower($inputted_category)); 
        })->first()['id'];
        $supplier = $supplier_masterlist->filter(function ($query) use ($inputted_supplier){
          return (strtolower($query['supplier_name']) == strtolower($inputted_supplier)); 
        })->first()['id'];

        $fields = [
          "account_no"=>$account_no['account_no'],
          "location_id"=>$location,
          "category_id"=>$category,
          "supplier_id"=>$supplier,
          "created_at"=>\Carbon\Carbon::now(),
          "updated_at"=>\Carbon\Carbon::now()
        ];
        $inputted_fields[] = $fields; 
      }
      
      $inputted_fields = collect($inputted_fields);
      $chunks = $inputted_fields->chunk(100);
      foreach($chunks as $chunk)
      {
        AccountNumber::insert($chunk->toArray()) ;
      }
      return $this->result(201,'Account Number has been imported.',$inputted_fields);
    }
    else
      throw new FistoException("No Account Number were imported. Kindly check the errors!.", 409, NULL, $errorBag);
  }
}
