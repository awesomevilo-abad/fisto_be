<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;

use App\Models\AccountTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountTitleController extends Controller
{
  public function index(Request $request)
  {
    
    $status =  $request['status'];
    $rows =  $request['rows'];
    $search =  $request['search'];
    
    $account_titles = AccountTitle::withTrashed()
    ->where(function ($query) use ($status){
      return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
    })
    ->where(function ($query) use ($search) {
      $query->where('code', 'like', '%'.$search.'%')
        ->orWhere('title', 'like', '%'.$search.'%')
        ->orWhere('category', 'like', '%'.$search.'%');
    })
    ->latest('updated_at')
    ->paginate($rows);
    
    if(count($account_titles)==true){
      return $this->result(200,"Account Title has been fetched.",$account_titles);
    }
    throw new FistoException("No records found.", 404, NULL, []);
    
  }
    
  public function show($id)
  {
    $account_title = AccountTitle::find($id);

    if (!empty($account_title)) {
      $result = [
        "code" => 404,
        "message" => "Account title has been fetched.",
        "data" => $account_title,
      ];
    
      return response($result);
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }

  public function store(Request $request)
  {
    $fields = $request->validate([
      'code' => ['required','string'],
      'title' => ['required','string'],
      'category' => ['required','string']
    ]);

    $account_title_validateCodeDuplicate = AccountTitle::withTrashed()->firstWhere('code', $fields['code']);

    if (!empty($account_title_validateCodeDuplicate)) {
      throw new FistoException("Code already registered.", 409, NULL, [
        "error_field" => "code"
      ]);
    }
    
    $account_title_validateTitleDuplicate = AccountTitle::withTrashed()->firstWhere('title', $fields['title']);

    if (!empty($account_title_validateTitleDuplicate)) {
      throw new FistoException("Title already registered.", 409, NULL, [
        "error_field" => "title"
      ]);
    }
    
    $new_account_title = AccountTitle::create($fields);

    $result = [
      "code" => 200,
      "message" => "New account title has been saved.",
      "result" => $new_account_title
    ];
    
    return response($result);
  }

  public function update(Request $request,$id)
  {
    $account_title = AccountTitle::find($id);

    $fields = $request->validate([
      'code' => ['required','string'],
      'title' => ['required','string'],
      'category' => ['required','string']
    ]);

    if (!empty($account_title)) {
      $account_title_validateCodeDuplicate = AccountTitle::withTrashed()->firstWhere([['id', '<>', $id],['code', $fields['code']]]);

      if (!empty($account_title_validateCodeDuplicate)) {
        throw new FistoException("Code already registered.", 409, NULL, [
          "error_field" => "code"
        ]);
      }
      
      $account_title_validateTitleDuplicate = AccountTitle::withTrashed()->firstWhere([['id', '<>', $id],['title', $fields['title']]]);

      if (!empty($account_title_validateTitleDuplicate)) {
        throw new FistoException("Title already registered.", 409, NULL, [
          "error_field" => "title"
        ]);
      }

      $account_title->code = $fields['code'];
      $account_title->title = $fields['title'];
      $account_title->category = $fields['category'];
      $account_title->save();

      $result = [
        "code" => 200,
        "message" => "Account title has been updated.",
        "result" => $account_title
      ];
          
      return response($result);
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }
    
  public function change_status(Request $request,$id)
  {
    $status = $request['status'];
    $model = new AccountTitle();
    return $this->change_masterlist_status($status,$model,$id);
  }

  public function import(Request $request)
  {
    $account_title_masterlist = AccountTitle::withTrashed()->get();
    $timezone = "Asia/Dhaka";
    date_default_timezone_set($timezone);
    $date = date("Y-m-d H:i:s", strtotime('now'));

    $errorBag = [];
    $data = $request->all();
    $data_validation_code = $request->all();
    $data_validation_title = $request->all();
    $index = 2;

    $categories = ['asset','capital','expense','income','payable'];
    $headers = 'Code, Title, Category';
    $template = ['code','title','category'];
    $keys = array_keys(current($data));
    $this->validateHeader($template,$keys,$headers);

    foreach ($data as $account_title) {
      $code = $account_title['code'];
      $title = $account_title['title'];
      $category = $account_title['category'];
      foreach($account_title as $key=>$value){
        if(empty($value)){
          $errorBag[] = [
            "error_type" => "empty",
            "line" => (string) $index,
            "description" => $key." is empty."
          ];
        }
      }
      if (!empty($code)) {
        $duplicateCode = $account_title_masterlist->filter(function ($query) use ($code){
          return ($query['code'] == $code) ; 
        });
        if ($duplicateCode->count() > 0)
          $errorBag[] = (object) [
            "error_type" => "existing",
            "line" => (string) $index,
            "description" => "Account Code: ".$code. " is already registered."
          ];
      }
      if (!empty($title)) {
        $duplicateTitle = $account_title_masterlist->filter(function ($query) use ($title){
          return ($query['title'] == $title) ; 
        });
        if ($duplicateTitle->count() > 0)
          $errorBag[] = (object) [
            "error_type" => "existing",
            "line" => (string) $index,
            "description" => "Account Title: ".$title. " is already registered."
          ];
      }
      if (!empty($category)) {
        if(!in_array($category,$categories)){
          $errorBag[] = (object) [
            "error_type" => "unregistered",
            "line" => (string) $index,
            "description" => "Category: ".$category. " is not registered."
          ];
        };
      }
      $index++;
    }
      
    foreach ($data_validation_code as $key => $subArr) {
      unset($subArr['category']);
      unset($subArr['title']);
      $data_validation_code[$key] = $subArr;  
    }

    $original_lines = array_keys($data_validation_code);
    $unique_lines = array_keys(array_unique($data_validation_code,SORT_REGULAR));
    $duplicate_lines = array_values(array_diff($original_lines,$unique_lines));

    foreach($duplicate_lines as $line){
      $input_code = $data_validation_code[$line]['code'];

      $duplicate_data =  array_filter($data_validation_code, function ($query) use($input_code){
        return ($query['code'] == $input_code);
      }); 
      $duplicate_lines_imploded =  implode(",",array_map(function($query){
        return $query+2;
      },array_keys($duplicate_data)));

      $firstDuplicateLine =  array_key_first($duplicate_data);
      
      if((empty($data_validation_code[$line]['code']))){

      }else{
        $errorBag[] = (object) [
          "error_type" => "duplicate",
          "line" => (string) $duplicate_lines_imploded,
          "description" =>  $data_validation_code[$firstDuplicateLine]['code'].' code has a duplicate in your excel file.'
        ];
      }
    }
    
    foreach ($data_validation_title as $key => $subArr) {
      unset($subArr['category']);
      unset($subArr['code']);
      $data_validation_title[$key] = $subArr;  
    }

    $original_lines_title = array_keys($data_validation_title);
    $unique_lines_title = array_keys(array_unique($data_validation_title,SORT_REGULAR));
    $duplicate_lines_title = array_values(array_diff($original_lines_title,$unique_lines_title));
    
    foreach($duplicate_lines_title as $line){
      $input_title = $data_validation_title[$line]['title'];

      $duplicate_data =  array_filter($data_validation_title, function ($query) use($input_title){
        return ($query['title'] == $input_title);
      }); 
      $duplicate_lines_imploded =  implode(",",array_map(function($query){
        return $query+2;
      },array_keys($duplicate_data)));

      $firstDuplicateLine =  array_key_first($duplicate_data);
      
      if((empty($data_validation_title[$line]['title']))){

      }else{
        $errorBag[] = (object) [
          "error_type" => "duplicate",
          "line" => (string) $duplicate_lines_imploded,
          "description" =>  $data_validation_title[$firstDuplicateLine]['title'].' title has a duplicate in your excel file.'
        ];
      }
    }
     
    $errorBag = array_values(array_unique($errorBag,SORT_REGULAR));

    if (empty($errorBag)) {
      foreach ($data as $account_title) {
        $fields = [
          'code' => $account_title['code'],
          'title' => $account_title['title'],
          'category' => $account_title['category'],
          'created_at' => $date,
          'updated_at' => $date,
        ];

        $inputted_fields[] = $fields;
      }
      $inputted_fields = collect($inputted_fields);
      $chunks = $inputted_fields->chunk(100);
      foreach($chunks as $chunk)
      {
        AccountTitle::insert($chunk->toArray()) ;
      }
      return $this->result(201,'Account Title has been imported.',$inputted_fields);
    }
    else
      throw new FistoException("No Account Title were imported. Kindly check the errors!.", 409, NULL, $errorBag);
  }
}
