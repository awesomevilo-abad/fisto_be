<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;

use App\Models\AccountTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountTitleController extends Controller
{
  public function index(Request $request,bool $status,int $rows)
    {
      
      $account_titles = AccountTitle::withTrashed()
      ->where(function ($query) use ($status){
        return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
      })
      ->latest('updated_at')
      ->paginate($rows);
      
      if(count($account_titles)==true){
        return $this->result(200,"Account Title has been fetched.",$account_titles);
      }
      throw new FistoException("No records found.", 404, NULL, []);
    }
    
  public function all(Request $request,$status)
    {
      $status = (bool)$status;
      
      $account_titles = DB::table('account_titles')
        ->select(['id', 'code', 'title', 'category'])
        ->where(function ($query) use ($status) {
          if ($status == true) $query->whereNull('deleted_at');
          else  $query->whereNotNull('deleted_at');
        })
        ->latest('title')
        ->get();

      if (count($account_titles) == true) {
        $result = [
          "code" => 200,
          "message" => "Account titles has been fetched.",
          "result" => $account_titles
        ];
        
        return response($result);
      }
      else
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

  public function search(Request $request,$status,$rows)
    {
      $rows = (int)$rows;
      $status = (bool)$status;
      $value = $request['value'];

      $account_titles = DB::table('account_titles')
        ->select(['id', 'code', 'title', 'category', 'updated_at', 'deleted_at'])
        ->where(function ($query) use ($status) {
          if ($status == true) $query->whereNull('deleted_at');
          else $query->whereNotNull('deleted_at');
        })
        ->where(function ($query) use ($value) {
          $query->where('code', 'like', '%'.$value.'%')
            ->orWhere('title', 'like', '%'.$value.'%')
            ->orWhere('category', 'like', '%'.$value.'%');
        })
        ->latest('updated_at')
        ->paginate($rows);

      if (count($account_titles) == true) {
        $result = [
          "code" => 200,
          "message" => "Account titles has been fetched.",
          "result" => $account_titles
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
    
  public function archive(Request $request,$id)
    {
      $softDeleteAccountTitle = AccountTitle::where('id',$id)->delete();

      if ($softDeleteAccountTitle == true) {
        $result = [
          "code" => 200,
          "message" => "Account title has been archived.",
          "result" => []
        ];
        
        return response($result);
      }
      else
        throw new FistoException("No records found.", 404, NULL, []);
    }

  public function restore(Request $request,$id)
    {
      $softRestoreAccountTitle = AccountTitle::onlyTrashed()->where('id',$id)->restore();

      if ($softRestoreAccountTitle == true) {
        $result = [
          "code" => 200,
          "message" => "Account title has been restored.",
          "result" => []
        ];
        
        return response($result);
      }
      else
        throw new FistoException("No records found.", 404, NULL, []);
    }
  public function import(Request $request)
    {
      $account_title_masterlist = AccountTitle::withTrashed()->get();
      $timezone = "Asia/Dhaka";
      date_default_timezone_set($timezone);
      $date = date("Y-m-d H:i:s", strtotime('now'));
  
      $errorBag = [];
      $data = $request->all();
      $data_validation_fields = $request->all();
      $index = 2;

      $categories = ['asset','capital','expenses','income','payable'];
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
              "line" => $index,
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
              "error_type" => "duplicate",
              "line" => $index,
              "description" => "Account Code: ".$code. " is already registered."
            ];
        }
        if (!empty($title)) {
          $duplicateTitle = $account_title_masterlist->filter(function ($query) use ($title){
            return ($query['title'] == $title) ; 
          });
          if ($duplicateTitle->count() > 0)
            $errorBag[] = (object) [
              "error_type" => "duplicate",
              "line" => $index,
              "description" => "Account Title: ".$title. " is already registered."
            ];
        }
        if (!empty($category)) {
          // $existingLocation = 
          if(!in_array($category,$categories)){
            $errorBag[] = (object) [
              "error_type" => "unregistered category",
              "line" => $index,
              "description" => "Category: ".$category. " is not registered."
            ];
          };
        }



        $index++;
      }
        
      foreach ($data_validation_fields as $key => $subArr) {
        unset($subArr['category']);
        $data_validation_fields[$key] = $subArr;  
      }

      $original_lines = array_keys($data_validation_fields);
      $unique_lines = array_keys(array_unique($data_validation_fields,SORT_REGULAR));
      $duplicate_lines = array_values(array_diff($original_lines,$unique_lines));
      foreach($duplicate_lines as $line){
        $errorBag[] = [
          "error_type" => "excel duplicate",
          "line" => $line,
          "description" =>  $data_validation_fields[$line]['code'].' with '.strtolower($data_validation_fields[$line]['title']).' account title has a duplicate in your excel file.'
        ];
      }
  
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
        throw new FistoException("No Account Title were imported. Please correct the errors in the excel file.", 409, NULL, $errorBag);
    }
}
