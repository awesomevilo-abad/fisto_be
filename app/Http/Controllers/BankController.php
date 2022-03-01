<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;

use App\Models\Bank;
use App\Models\AccountTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{
  public function index(Request $request,$status,$rows)
    {
      $rows = (int)$rows;
      $status = (bool)$status;

      $banks = DB::table('banks as B')
      ->join('account_titles as AT1', 'B.account_title_1', 'AT1.id')
      ->join('account_titles as AT2', 'B.account_title_2', 'AT2.id')
      ->select(
        'B.id',
        'B.code',
        'B.branch',
        'B.account_no',
        'B.location',
        'AT1.id as account_title_1_id',
        'AT1.title as account_title_1',
        'AT2.id as account_title_2_id',
        'AT2.title as account_title_2',
        'B.updated_at',
        'B.deleted_at'
      )
      ->where(function ($query) use ($status) {
        if ($status == true) $query->whereNull('B.deleted_at');
        else $query->whereNotNull('B.deleted_at');
      })
      ->latest('B.updated_at')
      ->paginate($rows);

      if (count($banks) == true) {
        $result = [
          "code" => 200,
          "message" => "Banks has been fetched.",
          "result" => $banks
        ];
        
        return response($result);
      }
      else
        throw new FistoException("No records found.", 404, NULL, []);
    }

  public function all(Request $request,$status)
    {
      $status = (bool)$status;

      $banks = Bank::latest('name')
        ->where(function ($query) use ($status) {
          if ($status == true) $query->whereNull('deleted_at');
          else $query->whereNotNull('deleted_at');
        })
        ->get(['id','name']);

      if (count($banks) == true) {
        $result = [
          "code" => 200,
          "message" => "Banks has been fetched.",
          "result" => $banks
        ];
        
        return response($result);
      }
      else
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

  public function search(Request $request,$status,$rows)
    {
      $rows = (int)$rows;
      $status = (bool)$status;
      $value = $request['value'];

      $banks = DB::table('banks as B')
        ->join('account_titles as AT1', 'B.account_title_1', 'AT1.id')
        ->join('account_titles as AT2', 'B.account_title_2', 'AT2.id')
        ->select(
          'B.id',
          'B.code',
          'B.branch',
          'B.account_no',
          'B.location',
          'AT1.id as account_title_1_id',
          'AT1.title as account_title_1',
          'AT2.id as account_title_2_id',
          'AT2.title as account_title_2',
          'B.updated_at',
          'B.deleted_at'
        )
        ->where(function ($query) use ($status) {
          if ($status == true) $query->whereNull('B.deleted_at');
          else $query->whereNotNull('B.deleted_at');
        })
        ->where(function ($query) use ($value) {
          $query->where('B.code', 'like', '%'.$value.'%')
          ->orWhere('B.name', 'like', '%'.$value.'%')
          ->orWhere('B.branch', 'like', '%'.$value.'%')
          ->orWhere('B.account_no', 'like', '%'.$value.'%')
          ->orWhere('B.location', 'like', '%'.$value.'%');
        })
        ->latest('B.updated_at')
        ->paginate($rows);

      if (count($banks) == true) {
        $result = [
          "code" => 200,
          "message" => "Banks has been fetched.",
          "result" => $banks
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
            'code' => ['unique:banks,code,' . $id],
            'name' => ['required'],
            'branch' => ['unique:banks,branch,' . $id],
            'account_no' => ['unique:banks,account_no,' . $id],
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
            $specific_bank->code = $request->get('code');
            $specific_bank->name = $request->get('name');
            $specific_bank->branch = $request->get('branch');
            $specific_bank->account_no = $request->get('account_no');
            $specific_bank->location = $request->get('location');
            $specific_bank->account_title_1 = $request->get('account_title_1');
            $specific_bank->account_title_2 = $request->get('account_title_2');
            $specific_bank->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Updated",
                "data" => $specific_bank,
            ];

        }
        return response($response);

    }
    
    public function archive(Request $request,$id)
    {
      $softDeleteBank = Bank::where('id',$id)->delete();

      if ($softDeleteBank == true) {
        $result = [
          "code" => 200,
          "message" => "Bank has been archived.",
          "result" => []
        ];
        
        return response($result);
      }
      else
        throw new FistoException("No records found.", 404, NULL, []);
    }

    public function restore(Request $request,$id)
    {
      $softRestoreBank = Bank::onlyTrashed()->where('id',$id)->restore();

      if ($softRestoreBank == true) {
        $result = [
          "code" => 200,
          "message" => "Bank has been restored.",
          "result" => []
        ];
        
        return response($result);
      }
      else
        throw new FistoException("No records found.", 404, NULL, []);
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
              "error_type" => "duplicate",
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
              "error_type" => "duplicate",
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
              "error_type" => "duplicate",
              "line" => $index,
              "description" => "Bank Account Number: ".$account_no. " is already registered."
            ];
        }
        
        
        if (!empty($account_title_1)) {
          if(!in_array($account_title_1,$account_title_titles)){
            $errorBag[] = (object) [
              "error_type" => "unregistered account title",
              "line" => $index,
              "description" => "Account Title 1: ".$account_title_1. " is not registered."
            ];
          };
        }

        if (!empty($account_title_2)) {
          if(!in_array($account_title_2,$account_title_titles)){
            $errorBag[] = (object) [
              "error_type" => "unregistered account title",
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
        if((empty($data_validation_fields[$line]['code']))){

        }else{
          $errorBag[] = [
            "error_type" => "excel duplicate",
            "line" => $line,
            "description" =>  $data_validation_fields[$line]['code'].' code has a duplicate in your excel file.'
          ];
        }
      }

      $duplicate_branch = array_values(array_diff($original_lines,array_keys($this->unique_multidim_array($data_validation_fields,'branch'))));
      foreach($duplicate_branch as $line){
        if((empty($data_validation_fields[$line]['branch']))){
        }else{
          $errorBag[] = [
            "error_type" => "excel duplicate",
            "line" => $line,
            "description" =>  $data_validation_fields[$line]['branch'].' Branch has a duplicate in your excel file.'
          ];
        }
      }
      
      $duplicate_account_no = array_values(array_diff($original_lines,array_keys($this->unique_multidim_array($data_validation_fields,'account_no'))));
      foreach($duplicate_account_no as $line){
        if((empty($data_validation_fields[$line]['account_no']))){
        }else{
          $errorBag[] = [
            "error_type" => "excel duplicate",
            "line" => $line,
            "description" =>  $data_validation_fields[$line]['account_no'].' Account Number has a duplicate in your excel file.'
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
        return $this->result(201,'Bank has been imported.',$inputted_fields);
      }
      else
        throw new FistoException("No Banks were imported. Kindly check the errors!.", 409, NULL, $errorBag);
    }
}
