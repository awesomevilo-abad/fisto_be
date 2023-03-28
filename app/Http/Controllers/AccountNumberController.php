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
  public function index(Request $request)
  {
    $status =  $request['status'];
    $rows =  (empty($request['rows']))?10:(int)$request['rows'];
    $search =  $request['search'];
    $paginate = (isset($request['paginate']))? $request['paginate']:$paginate = 1;
    
    $account_number = AccountNumber::withTrashed()
    ->when($paginate,function($q) use ($search){
      $q->with('location')
      ->with('category')
      ->with('supplier')
      ->where(function ($query) use ($search) {
        $query->where('account_no', 'like', '%'.$search.'%')
        ->orWhereHas ('location',function($q)use($search){$q->where('location', 'like', '%'.$search.'%');})
        ->orWhereHas ('category',function($q)use($search){$q->where('category', 'like', '%'.$search.'%');})
        ->orWhereHas ('supplier',function($q)use($search){$q->where('name', 'like', '%'.$search.'%');});
      });
    },function($q){
      $q->with(['location'=>function($q){
        $q->select('id');
      }])
      ->with(['category'=>function($q){
        $q->select('id');
      }])
      ->with(['supplier'=>function($q){
        $q->select('id');
      }]);
    })
    ->where(function ($query) use ($status){
      return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
    })
    ->latest('updated_at');

    if ($paginate == 1){
      $account_number = $account_number
      ->paginate($rows);
    }else if ($paginate == 0){
      $account_number = $account_number
      ->get(['id','location_id','category_id','supplier_id','account_no as no']);
      if(count($account_number)==true){
          $account_number = array("account_numbers"=>$account_number);;
      }
    }
    
    if(count($account_number)==true){
      return $this->resultResponse('fetch','Account Number',$account_number);
    }
    return $this->resultResponse('not-found','Account Number',[]);
  }
    
  public function store(AccountNumberRequest $request)
  {
    $fields = $request->validated();

    $account_number_validateDuplicate = AccountNumber::withTrashed()->firstWhere([['supplier_id', $fields['supplier_id']],['location_id', $fields['location_id']],['account_no', $fields['account_no']],['category_id', $fields['category_id']]]);
    if (empty($account_number_validateDuplicate)) {
      $new_account_numbers = AccountNumber::create($fields);
      return $this->resultResponse('save','Account Number',$new_account_numbers);
    }
    else
      return $this->resultResponse('registered','Account Number',[]);
  }
    
  public function update(AccountNumberRequest $request,$id)
  {
    $model = new AccountNumber();
    $account_number = AccountNumber::find($id);
    $fields = $request->validated();

    if (empty($account_number)) 
      return $this->resultResponse('not-found','Account Number',[]);
      // $is_unique = $this->isUnique($model,'Account number',['account_no','category_id'],[$fields['account_no'],$fields['category_id']],$id);

      $account_number_validateDuplicate = AccountNumber::withTrashed()->firstWhere([['id', '<>', $id],['supplier_id', $fields['supplier_id']],['location_id', $fields['location_id']],['account_no', $fields['account_no']],['category_id', $fields['category_id']]]);
      if (!empty($account_number_validateDuplicate)) 
      return $this->resultResponse('registered','Account Number',[]);
      
      $account_number->account_no = $fields['account_no'];
      $account_number->location_id = $fields['location_id'];
      $account_number->category_id = $fields['category_id'];
      $account_number->supplier_id = $fields['supplier_id'];

      return $this->validateIfNothingChangeThenSave($account_number,'Account number');
  }

  public function change_status(Request $request,$id)
  {
    $status = $request['status'];
    $model = new AccountNumber();
    return $this->change_masterlist_status($status,$model,$id,'Account number');
  }

  public function import(Request $request)
  {
    $timezone = "Asia/Dhaka";
    date_default_timezone_set($timezone);
    $date = date("Y-m-d H:i:s", strtotime('now'));

    $data = $request->all();
    $data_validation_fields = $request->all();
    $account_number_masterlist = AccountNumber::withTrashed()->get();
    $utility_location_masterlist = UtilityLocation::withTrashed()->get();
    $utility_category_masterlist = UtilityCategory::withTrashed()->get();
    $supplier_masterlist = Supplier::get();
    $errorBag = [];
    $index = 2;
    $template = ['account_no','location','supplier','category','status'];
    $headers = 'Account No, Location, Supplier, Category, Status';
    $keys = array_keys(current($data));
    $this->validateHeader($template,$keys,$headers);
    foreach($data as $account_number){
      $account_no = $account_number['account_no'];
      $category = $account_number['category'];
      $location = $account_number['location'];
      $supplier = $account_number['supplier'];

      foreach($account_number as $key=>$value){
        if(empty($value)){
          $errorBag[] = [
            "error_type" => "empty",
            "line" => $index,
            "description" => $key." is empty."
          ];
        }
      }

      $category_id = $utility_category_masterlist->filter(function ($query) use ($category){
        return ((strtolower($query['category']) == strtolower($category))); 
      });

      if(count($category_id)>0){
        $category_id = $category_id->first()->id;
      }else{
        $category_id  = 0;
      }

      // if (!empty($account_no)) {
      //   $duplicateAccountNo = $account_number_masterlist->filter(function ($query) use ($account_no,$category_id){
      //     return ((strtolower($query['account_no']) == strtolower($account_no)) && (strtolower($query['category_id']) == strtolower($category_id))) ; 
      //   });
      //   if ($duplicateAccountNo->count() > 0)
      //     $errorBag[] = (object) [
      //       "error_type" => "existing",
      //       "line" => $index,
      //       "description" => $category. ", with ".$account_no. " account number is already registered."
      //     ];
      // }


      if (!empty($location)) {
        $existingLocation = $utility_location_masterlist->filter(function ($query) use ($location){
          return (strtolower($query['location']) == strtolower($location)); 
        });
        if ($existingLocation->count() == 0)
          $errorBag[] = (object) [
            "error_type" => "unregistered",
            "line" => $index,
            "description" => $location. " is not registered."
          ];
      }


      if (!empty($category)) {
        $existingCagtegory= $utility_category_masterlist->filter(function ($query) use ($category){
          return (strtolower($query['category']) == strtolower($category)); 
        });
        if ($existingCagtegory->count() == 0)
          $errorBag[] = (object) [
            "error_type" => "unregistered",
            "line" => $index,
            "description" => $category. " is not registered."
          ];
      }


      if (!empty($supplier)) {
        $existingSupplier = $supplier_masterlist->filter(function ($query) use ($supplier){
          return (strtolower($query['name']) == strtolower($supplier)); 
        });
        if ($existingSupplier->count() == 0)
          $errorBag[] = (object) [
            "error_type" => "unregistered",
            "line" => $index,
            "description" => $supplier. " is not registered."
          ];
      }
      $index++;
    }
    foreach ($data_validation_fields as $key => $subArr) {
      unset($subArr['location']);
      unset($subArr['supplier']);
      $data_validation_fields[$key] = $subArr;  
    }

    $original_lines = array_keys($data_validation_fields);
    $unique_lines = array_keys(array_unique($data_validation_fields,SORT_REGULAR));
    $duplicate_lines = array_values(array_diff($original_lines,$unique_lines));

    foreach($duplicate_lines as $line){
      $input_account_no= $data_validation_fields[$line]['account_no'];
      $input_category= $data_validation_fields[$line]['category'];

      $duplicate_data =  array_filter($data_validation_fields, function ($query) use($input_account_no,$input_category){
        return (($query['account_no'] == $input_account_no) && ($query['category'] == $input_category));
      }); 
      $duplicate_lines_imploded =  implode(",",array_map(function($query){
        return $query+2;
      },array_keys($duplicate_data)));

      $firstDuplicateLine =  array_key_first($duplicate_data);
      

      if((empty($data_validation_fields[$line]['account_no'])) || (empty($data_validation_fields[$line]['category']))){
      }else{
        $errorBag[] = [
          "error_type" => "duplicate",
          "line" => (string) $duplicate_lines_imploded,
          "description" =>  $data_validation_fields[$line]['account_no'].' with '.strtolower($data_validation_fields[$line]['category']).' category has a duplicate in your excel file.'
        ];
      }
    }

    $errorBag = array_values(array_unique($errorBag,SORT_REGULAR));
    if(empty($errorBag)){
      foreach($data as $account_no){
        $status_date = (strtolower($account_no['status'])=="active"?NULL:$date);
        $inputted_supplier = $account_no['supplier'];
        $inputted_location = $account_no['location'];
        $inputted_category = $account_no['category'];

        $location = $utility_location_masterlist->filter(function ($query) use ($inputted_location){
          return (strtolower($query['location']) == strtolower($inputted_location)); 
        });

        if(count($location)>0){
          $location = $location->first()->id;
        }else{
          $location  = 0;
        }

        $category = $utility_category_masterlist->filter(function ($query) use ($inputted_category){
          return (strtolower($query['category']) == strtolower($inputted_category)); 
        });

        if(count($category)>0){
          $category = $category->first()->id;
        }else{
          $category  = 0;
        }

        $supplier = $supplier_masterlist->filter(function ($query) use ($inputted_supplier){
          return (strtolower($query['name']) == strtolower($inputted_supplier)); 
        });

        if(count($supplier)>0){
          $supplier = $supplier->first()->id;
        }else{
          $supplier  = 0;
        }

        $fields = [
          "account_no"=>$account_no['account_no'],
          "location_id"=>$location,
          "category_id"=>$category,
          "supplier_id"=>$supplier,
          "created_at"=>\Carbon\Carbon::now(),
          "updated_at"=>\Carbon\Carbon::now(),
          'deleted_at' => $status_date
        ];
        $inputted_fields[] = $fields; 
      }
      $inputted_fields = collect($inputted_fields);
      $chunks = $inputted_fields->chunk(100);
      $count_upload = count($inputted_fields);
      $active =  $inputted_fields->filter(function ($q){
        return $q['deleted_at']==NULL;
      })->count();

      $inactive =  $inputted_fields->filter(function ($q){
        return $q['deleted_at']!=NULL;
      })->count();

      foreach($chunks as $chunk)
      {
        AccountNumber::insert($chunk->toArray()) ;
      }
      return $this->resultResponse('import','Account Number',$count_upload,$active,$inactive);
    }
    else
    return $this->resultResponse('import-error','Account Number',$errorBag);
  }
}
