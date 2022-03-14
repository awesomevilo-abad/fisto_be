<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;

use App\Models\Bank;
use App\Models\AccountTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{
  public function index(Request $request)
  {
    $status =  $request['status'];
    $rows =  (empty($request['rows']))?10:(int)$request['rows'];
    $search =  $request['search'];
    
    $banks = Bank::withTrashed()
    ->with('AccountTitleOne')
    ->with('AccountTitleTwo')
    ->where(function ($query) use ($status){
      return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
    })
    ->where(function ($query) use ($search) {
      $query->where('banks.code', 'like', '%'.$search.'%')
      ->orWhere('banks.name', 'like', '%'.$search.'%')
      ->orWhere('banks.branch', 'like', '%'.$search.'%')
      ->orWhere('banks.account_no', 'like', '%'.$search.'%')
      ->orWhere('banks.location', 'like', '%'.$search.'%');
    })
    ->latest('updated_at')
    ->paginate($rows);
    
    if(count($banks)==true){
      return $this->result(200,"Banks has been fetched.",$banks);
    }
    throw new FistoException("No records found.", 404, NULL, []);
  }

  public function show($id)
  {
    $bank = Bank::find($id);

    if (!empty($bank)) {
      $result = [
        "code" => 200,
        "message" => "Bank has been fetched.",
        "result" => $bank
      ];
      
      return response($result);
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }

  public function store(Request $request)
  {
    $fields = $request->validate([
      'code' => 'required|string',
      'name' => 'required|string',
      'branch' => 'required|string',
      'account_no' => 'required|string',
      'location' => 'required|string',
      'account_title_1' => 'required|numeric',
      'account_title_2' => 'required|numeric'
    ]);
    
    $bank_validateDuplicate = Bank::withTrashed()->where('code', $fields['code'])
    ->orWhere('branch',$fields['branch'])
    ->orWhere('account_no',$fields['account_no'])->first();

    if (empty($bank_validateDuplicate)) {
      $new_bank = Bank::create($fields);
      return $this->result(201,"New Bank has been saved.",$new_bank);
    }
    else
      throw new FistoException("Bank already registered.", 409, NULL, []);
  }
    
  public function update(Request $request, $id)
  {
      $specific_bank = Bank::find($id);

      $fields = $request->validate([
          'code' => ['required'],
          'name' => ['required'],
          'branch' => ['required'],
          'account_no' => ['required'],
          'location' => ['required'],
          'account_title_1' => ['required'],
          'account_title_2' => ['required']
      ]);

     
      if (!$specific_bank) {
          $response = [
              "code" => 404,
              "message" => "Data Not Found!",
              "data" => $specific_bank,
          ];
      } else {

          $model = new Bank();
          $this->isUnique($model,'Bank',['code'],[$fields['code']],$id,1);
          $this->isUnique($model,'Bank',['branch'],[$fields['branch']],$id,1);
          $this->isUnique($model,'Bank',['account_no'],[$fields['account_no']],$id,1);

          $specific_bank->code = $request->get('code');
          $specific_bank->name = $request->get('name');
          $specific_bank->branch = $request->get('branch');
          $specific_bank->account_no = $request->get('account_no');
          $specific_bank->location = $request->get('location');
          $specific_bank->account_title_1 = $request->get('account_title_1');
          $specific_bank->account_title_2 = $request->get('account_title_2');
          return $this->validateIfNothingChangeThenSave($specific_bank,'Bank');
      }
  }
    
  public function change_status(Request $request,$id){
    $status = $request['status'];
    $model = new Bank();
    return $this->change_masterlist_status($status,$model,$id,'Bank');
  }

  public function import(Request $request)
  {
    $bank_masterlist = Bank::withTrashed()->get();
    $account_title_masterlist = AccountTitle::withTrashed()->get(); 
    $account_title_masterlist_array = $account_title_masterlist->toArray();
    $account_title_titles =  array_column($account_title_masterlist_array,'title');
    $timezone = "Asia/Dhaka";
    date_default_timezone_set($timezone);
    $date = date("Y-m-d H:i:s", strtotime('now'));

    $errorBag = [];
    $data = $request->all();
    $data_validation_fields = $request->all();
    $index = 2;

    $headers = 'Code, Name, Branch, Account No, Location, Account Title 1, Account Title 2';
    $template = ['code','name','branch','account_no','location','account_title_1','account_title_2'];
    $keys = array_keys(current($data));
    $this->validateHeader($template,$keys,$headers);

    foreach ($data as $bank) {
      $code = $bank['code'];
      $name = $bank['name'];
      $branch = $bank['branch'];
      $account_no = $bank['account_no'];
      $location = $bank['location'];
      $account_title_1 = $bank['account_title_1'];
      $account_title_2 = $bank['account_title_2'];
      foreach($bank as $key=>$value){
        if(empty($value)){
          $errorBag[] = [
            "error_type" => "empty",
            "line" => $index,
            "description" => $key." is empty."
          ];
        }
      }
      if (!empty($code)) {
        $duplicateCode = $bank_masterlist->filter(function ($query) use ($code){
          return ($query['code'] == $code) ; 
        });
        if ($duplicateCode->count() > 0)
          $errorBag[] = (object) [
            "error_type" => "existing",
            "line" => $index,
            "description" => "Bank Code: ".$code. " is already registered."
          ];
      }
      if (!empty($branch)) {
        $duplicateBranch = $bank_masterlist->filter(function ($query) use ($branch){
          return ($query['branch'] == $branch) ; 
        });
        if ($duplicateBranch->count() > 0)
          $errorBag[] = (object) [
            "error_type" => "existing",
            "line" => $index,
            "description" => "Bank Branch: ".$branch. " is already registered."
          ];
      }
      if (!empty($account_no)) {
        $duplicateAccountNo = $bank_masterlist->filter(function ($query) use ($account_no){
          return ($query['account_no'] == $account_no) ; 
        });
        if ($duplicateAccountNo->count() > 0)
          $errorBag[] = (object) [
            "error_type" => "existing",
            "line" => $index,
            "description" => "Bank Account Number: ".$account_no. " is already registered."
          ];
      }
      
      
      if (!empty($account_title_1)) {
        if(!in_array($account_title_1,$account_title_titles)){
          $errorBag[] = (object) [
            "error_type" => "unregistered",
            "line" => $index,
            "description" => "Account Title 1: ".$account_title_1. " is not registered."
          ];
        };
      }

      if (!empty($account_title_2)) {
        if(!in_array($account_title_2,$account_title_titles)){
          $errorBag[] = (object) [
            "error_type" => "unregistered",
            "line" => $index,
            "description" => "Account Title 2: ".$account_title_2. " is not registered."
          ];
        };
      }

      
      $index++;
    }
      
    $original_lines = array_keys($data_validation_fields);
    
    $duplicate_code = array_values(array_diff($original_lines,array_keys($this->unique_multidim_array($data_validation_fields,'code'))));
    foreach($duplicate_code as $line){
      
      $input_code = $data_validation_fields[$line]['code'];
      $duplicate_data =  array_filter($data_validation_fields, function ($query) use($input_code){
        return ($query['code'] == $input_code);
      }); 
      $duplicate_lines =  implode(",",array_map(function($query){
        return $query+2;
      },array_keys($duplicate_data)));
      $firstDuplicateLine =  array_key_first($duplicate_data);

      if((empty($data_validation_fields[$line]['code']))){

      }else{
        $errorBag[] = [
          "error_type" => "duplicate",
          "line" => (string) $duplicate_lines,
          "description" =>  $data_validation_fields[$firstDuplicateLine]['code'].' code has a duplicate in your excel file.'
        ];
      }
    }

    $duplicate_branch = array_values(array_diff($original_lines,array_keys($this->unique_multidim_array($data_validation_fields,'branch'))));
    foreach($duplicate_branch as $line){
      $input_branch = $data_validation_fields[$line]['branch'];
      $duplicate_data =  array_filter($data_validation_fields, function ($query) use($input_branch){
        return ($query['branch'] == $input_branch);
      }); 
      $duplicate_lines =  implode(",",array_map(function($query){
        return $query+2;
      },array_keys($duplicate_data)));
      $firstDuplicateLine =  array_key_first($duplicate_data);

      if((empty($data_validation_fields[$line]['branch']))){
      }else{
        $errorBag[] = [
          "error_type" => "duplicate",
          "line" => (string) $duplicate_lines,
          "description" =>  $data_validation_fields[$firstDuplicateLine]['branch'].' Branch has a duplicate in your excel file.'
        ];
      }
    }

    $errorBag = array_values(array_unique($errorBag,SORT_REGULAR));
    
    $duplicate_account_no = array_values(array_diff($original_lines,array_keys($this->unique_multidim_array($data_validation_fields,'account_no'))));
    foreach($duplicate_account_no as $line){

      $input_account_no = $data_validation_fields[$line]['account_no'];
      $duplicate_data =  array_filter($data_validation_fields, function ($query) use($input_account_no){
        return ($query['account_no'] == $input_account_no);
      }); 
      $duplicate_lines =  implode(",",array_map(function($query){
        return $query+2;
      },array_keys($duplicate_data)));
      $firstDuplicateLine =  array_key_first($duplicate_data);

      if((empty($data_validation_fields[$line]['account_no']))){
      }else{
        $errorBag[] = [
          "error_type" => "duplicate",
          "line" => (string) $duplicate_lines,
          "description" =>  $data_validation_fields[$firstDuplicateLine]['account_no'].' Account Number has a duplicate in your excel file.'
        ];
      }
    }

    if (empty($errorBag)) {
      foreach ($data as $bank) {
        $fields = [
          'code' => $bank['code'],
          'name' => $bank['name'],
          'branch' => $bank['branch'],
          'account_no' => $bank['account_no'],
          'location' => $bank['location'],
          'account_title_1' => AccountTitle::firstWhere('title',$bank['account_title_1'])->id,
          'account_title_2' => AccountTitle::firstWhere('title',$bank['account_title_2'])->id,
          'created_at' => $date,
          'updated_at' => $date,
        ];

        $inputted_fields[] = $fields;
      }
      $inputted_fields = collect($inputted_fields);
      $chunks = $inputted_fields->chunk(100);
      foreach($chunks as $chunk)
      {
        Bank::insert($chunk->toArray()) ;
      }
      return $this->result(201,'Banks has been imported.',$inputted_fields);
    }
    else
      throw new FistoException("No Banks were imported. Kindly check the errors!.", 409, NULL, $errorBag);
  }
}
