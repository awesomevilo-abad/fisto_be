<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller as BaseController;
use App\Exceptions\FistoException;
use App\Exceptions\FistoLaravelException;
use App\Methods\GenericMethod;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function result($code,$message,$data){
        $arrayResponse = [
            "code" => $code,
            "message" =>$message,
            "result" => $data,
        ];
        return response($arrayResponse,$code);
    }

    public function validateIfObjectExist($model,$param,$modelName){
      $modelObject = $model::where('id',$param)->whereNull('deleted_at')->first();
      if(empty($modelObject)){
        return $this->resultResponse('not-registered',$modelName,$modelName." ID: ".$param);
      }
      return $modelObject;
    }

    public function validateIfObjectsExist($model,$arrParam,$modelName){
      
      $unregisteredObjects = [];
      foreach($arrParam as $param){
        $modelObject = $model::withTrashed()->whereNull('deleted_at')->where('id',$param)->first();
        if(empty($modelObject)){
            $unregisteredObjects[] = $param;
        }
      }
      if(!empty($unregisteredObjects)){
        throw new FistoException($modelName." not registered or inactive.",404,NULL,$modelName." IDs: ".implode(',',$unregisteredObjects));
      }
    }
    
    public function validateIfObjectsExistByLocationStore($model,$arrParam,$modelName){
      
      $unregisteredObjects = [];
      foreach($arrParam as $param){
        $department_name= $model::withTrashed()->firstWhere('id',$param)->department;
        $modelObject = $model::withTrashed()->whereNull('deleted_at')->where('id',$param)->first();
        if(empty($modelObject)){
            $unregisteredObjects[] = $department_name;
        }
      }
      if(!empty($unregisteredObjects)){
        throw new FistoException(GenericMethod::addAND($unregisteredObjects)." is not registered.",404,NULL,collect(["error_field"=>"departments"]));
      }
    }

    public function validateIfObjectsExistByLocation($model,$arrParam,$modelName){
      
      $unregisteredObjects = [];
      $errorBag=[];
      $index = 2;

      foreach($arrParam as $param){
        $modelObject = $model::withTrashed()->where('department',$param)->first();
        if(empty($modelObject)){

          $errorBag[] = (object) [
            "error_type" => "unregistered",
            "line" => $index,
            "description" => $param . " is not registered."
            ];
            $unregisteredObjects[] = $param;
        }
        $index++;
      }
      return $errorBag;
      // if(!empty($errorBag)){
      //   throw new FistoException("No locations were imported. Kindly check the errors.",409,NULL,$errorBag);
      // }
    }

    public function isTaggedArrayModified($inpputedArrayField, $modelObject,$field){
      $previousArray = array_column($modelObject->toArray(),$field);
      $is_tagged_array_modified = count(array_merge(array_diff($previousArray, $inpputedArrayField), array_diff($inpputedArrayField, $previousArray)));
      return $is_tagged_array_modified;
    }

    public function isMultipleTaggedArrayModified($tagged_array_1,$tagged_array_2){
      if(($tagged_array_1 == 1) || ($tagged_array_2 == 1)){
        $is_tagged_array_modified = 1;
      }else{
          $is_tagged_array_modified = 0;
      }
      return $is_tagged_array_modified;
    }

    public function validateIfNothingChangeThenSave($model,$modelName,$is_tagged_array_modified=0){
      // return $model->isClean().'&&'.$is_tagged_array_modified;
      if($model->isClean() && $is_tagged_array_modified == 0){
        return $this->resultResponse('nothing-has-changed',$modelName,[]);
      }else{
          $model->save();
          return $this->resultResponse('update',$modelName,[]);
      }
    }
    
    public function unique_multidim_array($array, $key) {
      $temp_array = array();
      $i = 0;
      $key_array = array();
     
      foreach($array as $val) {
          if (!in_array($val[$key], $key_array)) {
              $key_array[$i] = $val[$key];
              $temp_array[$i] = $val;
          }
          $i++;
      }
      return $temp_array;
    }

    public function validateHeader($template,$keys,$headers){
      if(count(array_diff($template,$keys))){
        return $this->resultResponse('import-format',$headers,[]);
        // throw new FistoException("Invalid excel template, it should be ".$headers, 406, NULL, []);
      }
    }

    public function validateEmptyCells($data,$index){
      $emptyCells = [];
      foreach($data as $key=>$value){
        if(empty($value)){
          $emptyCells[] = [
            "error_type" => "empty",
            "line" => (string) $index,
            "description" => $key." is empty."
          ];
        }
      }
      return $emptyCells;
    }

    public function getCategoryId($category,$masterlist){
       $category_id = $masterlist->filter(function ($query) use ($category){
        return (strtolower($query["category"]) == strtolower($category)); 
      });
      return $category_id;
    }

    public function validateDuplicateInDBFrom2Params($param1,$param2_id,$param2_description,$table,$index){
      $duplicates=[];
      if (!empty($param1)) {
        $duplicateAccountNo = $table->filter(function ($query) use ($param1,$param2_id){
          return (($query['account_no'] == $param1)  && ($query['category_id'] ==  $param2_id)); 
        });

        if ($duplicateAccountNo->count() > 0)
          $duplicates[] = (object) [
            "error_type" => "existing",
            "line" => (string) $index,
            "description" => $param2_description. ", Account No.: ".$param1. " is already registered."
          ];
      }
      return $duplicates;

    }

    public function validateExistingLocation($param1,$table,$index){
      $existings=[];
      if (!empty($param1)) {
        $existingLocations = $table->filter(function ($query) use ($param1){
          return (strtolower($query["location"]) == strtolower($param1)); 
        });
        if ($existingLocations->count() == 0)
          $existings[] = (object) [
            "error_type" => "unregistered",
            "line" =>(string) $index,
            "description" => $param1. " is not registered."
          ];
      }
      return $existings;
    }

    public function validateExistingCategory($param1,$table,$index){
      $existings=[];
      if (!empty($param1)) {
        $existingLocations = $table->filter(function ($query) use ($param1){
          return (strtolower($query["category"]) == strtolower($param1)); 
        });
        if ($existingLocations->count() == 0)
          $existings[] = (object) [
            "error_type" => "unregistered",
            "line" =>(string) $index,
            "description" => $param1. " is not registered."
          ];
      }
      return $existings;
    }

    public function validateExistingSupplier($param1,$table,$index){
      $existings=[];
      if (!empty($param1)) {
        $existingSuppliers = $table->filter(function ($query) use ($param1){
          return (strtolower($query["name"]) == strtolower($param1)); 
        });
        if ($existingSuppliers->count() == 0)
          $existings[] = (object) [
            "error_type" => "unregistered",
            "line" =>(string) $index,
            "description" => $param1. " is not registered."
          ];
      }
      return $existings;
    }

    public function removeFieldInArrayOfObjects($arrOfObjects,$fields){

      foreach ($arrOfObjects as $key => $subArr) {
        unset($subArr["$fields[0]"]);
        unset($subArr["$fields[1]"]);
        $data_validation_fields[$key] = $subArr;  
      }

      return $data_validation_fields;
    }

    public function getDuplicateLines($object){
      $original_lines = array_keys($object);
      $unique_lines = array_keys(array_unique($object,SORT_REGULAR));
      return $duplicate_lines = array_values(array_diff($original_lines,$unique_lines));
    }
    
    public function validateDuplicatesInAccountNumberExcel($duplicate_lines,$object){
      $excelDuplicates=[];
      foreach($duplicate_lines as $line){
        $input_account_no = $object[$line]['account_no'];
        $input_category = $object[$line]['category'];
        $duplicate_data =  array_filter($object, function ($query) use($input_account_no, $input_category){
          return ($query['account_no'] == $input_account_no) && ($query['category'] == $input_category);
        }); 
        $duplicate_lines =  implode(",",array_map(function($query){
          return $query+2;
        },array_keys($duplicate_data)));
        $firstDuplicateLine =  array_key_first($duplicate_data);
  
        if((empty($object[$line]['account_no'])) || (empty($object[$line]['category']))){
        }else{
          $excelDuplicates[] = [
            "error_type" => "duplicate",
            "line" => (string) $duplicate_lines,
            "description" =>  $object[$firstDuplicateLine]['account_no'].' with '.strtolower($object[$firstDuplicateLine]['category']).' category has a duplicate in your excel file.'
          ];
        }
      }
      return $excelDuplicates;
    }

    public function change_masterlist_status($status,$model,$id,$modelName){
      if($status == 1){
        $softDelete = $model::where('id',$id)->delete();
        if($softDelete == 1){
          return $this->resultResponse('archive',$modelName,[]);
        }
        return $this->resultResponse('not-found',$modelName,[]);
      }else {
          $restore = $model::onlyTrashed()->where('id',$id)->restore();
          if($restore == 1){
              return $this->resultResponse('restore',$modelName,[]);
          }
          return $this->resultResponse('not-found',$modelName,[]);
     }
    }

    public function change_masterlist_status_user($transaction_exist,$status,$model,$id,$modelName){
      if($status == 1){

        if($transaction_exist){
          throw new FistoException("Cannot archive user with pending or hold transactions.", 409, NULL, []);
         }
         
        $softDelete = $model::where('id',$id)->delete();
        if($softDelete == 1){
          return $this->resultResponse('archive',$modelName,[]);
        }
        return $this->resultResponse('not-found',$modelName,[]);
      }else {
          $restore = $model::onlyTrashed()->where('id',$id)->restore();
          if($restore == 1){
              return $this->resultResponse('restore',$modelName,[]);
          }
          return $this->resultResponse('not-found',$modelName,[]);
     }
    }
    
    public function isUnique($model,$modelName,$params,$fields,$id,$per_field=0)
    {
      $param_is_exist = [];
      if(count($params) != count($fields)){
        throw new FistoException($modelName. ": field count and paramater count are not equal.", 202, NULL, []);
      }else{
        $query = $model->withTrashed()->get();

        foreach($fields as $k=>$v){
          $param1= $params[$k];
          $field1 = $fields[$k];

          $count = $query->filter(function ($q) use ($param1,$field1,$id){
            return ((strtolower($q["$param1"]) == strtolower($field1)) && ($q['id'] != $id)); 
           });

         if($count->values()->first()){
          $param_is_exist["$params[$k]"] =  1;
         }else{
          $param_is_exist["$params[$k]"] =  0;
         }
        
        }
        $duplicate_params = array_keys(array_filter($param_is_exist, function($param){return $param !== 0;}));
        if(count($param_is_exist) == array_sum($param_is_exist))
            
            if($per_field == 0){
              throw new FistoException(ucfirst(strtolower($modelName)). " already registered.", 409, NULL, []);
            }else{
              throw new FistoException(ucfirst(strtolower(implode(",",$params))). " already registered.", 409, NULL, []);
            }
      }
    }
    
    public function ifExistInTable($modelName,$table,$id){
      $table = DB::table("$table")->where('id', $id)->exists();
      if(empty($table)){
        return (ucfirst(strtolower($modelName))." not registered.");
      }
      
    }

    public function getMultipleFieldExist($modelNames,$tables,$fields,$errorMessages){
      
      if ((count($modelNames) != count($tables)) || (count($tables) != count($fields)) || (count($modelNames) != count($fields))) {
        throw new FistoException("Model name, field and table counts are not equal.", 202, NULL, [array(
          "Model name count: ".count($modelNames),"Table count: ".count($tables),"Field count: ".count($fields),)]);
     }
     foreach($modelNames as $k=>$v){
       $errorMessage = $this->ifExistInTable("$modelNames[$k]","$tables[$k]",$fields[$k]);
       if($errorMessage){
         $errorMessages[] =$errorMessage;
       }
     }
    
     return $errorMessages;
    }
    
    public function convertToFloat($amount){
      return floatval(str_replace(',', '',$amount));
    }

    public function resultResponse($action,$modelName,$data=[],$active=[],$inactive=[]){
      $origModelName = $modelName;
      $modelName = ucfirst(strtolower($modelName));
      switch($action){
        case('fetch'):
          return $this->result(200,Str::plural($modelName)." has been fetched.",$data);
        break;
        
        case('save'):
          return $this->result(201,"New ".strtolower($modelName)." has been saved.",$data);
        break;

        case('void'):
          return $this->result(201,strtoupper($modelName)." has been voided.",$data);
        break;

        case('import'):
          return $this->result(201,Str::plural($modelName)." has been imported, ".$active.' active rows and '.$inactive.' inactive rows were added.',[]);
        break;
        
        case('update'):
          return $this->result(200,$modelName." has been updated.",$data);
        break;
        
        case('archive'):
          return $this->result(200,$modelName." has been archived.",$data);
        break;

        case('restore'):
          return $this->result(200,$modelName." has been restored.",$data);
        break;
        
        case('no-content'):
          return $this->result(403,$modelName,[]);
        break;

        case('registered'):
          throw new FistoException($modelName." already registered.", 409, NULL, $data);
        break;
        
        case('not-registered'):
          throw new FistoException($modelName." not registered.", 409, NULL, $data);
        break;
          
        case('registered-inactive'):
          throw new FistoException($modelName." already registered but inactive.", 409, NULL, $data);
        break;
          
        case('exist'):
          throw new FistoException($modelName." already exist.", 409, NULL, $data);
        break;

        case('invalid'):
          throw new FistoLaravelException("The given data was invalid.", 422, NULL, $data);
        break;
        case('not-exist'):
          throw new FistoLaravelException($modelName." does not exist.", 404, NULL, $data);
        break;
        
        case('not-exist-department'):
          return $this->result(404,$modelName." does not exist.",$data);
        break;
        
        case('import-error'):
          throw new FistoException("No ".Str::plural(strtolower($modelName))." were imported. Kindly check the errors.", 409, NULL, $data);
        break;
        
        case('import-format'):
          throw new FistoException("Invalid excel template, it should be ".$origModelName.".", 406, NULL, []);
        break;
        
        case('nothing-has-changed'):
          return $this->result(304,"Nothing has changed.",$data);
        break;

        case('not-found'):
          throw new FistoException("No records found.", 404, NULL, $data);
        break;

        case('password-changed'):
          return $this->result(200,"Password has been changed.",$data);
        break;

        case('success-no-content'):
          return $this->result(204,"Success.",[]);
        break;

        case('password-incorrect'):
          throw new FistoException("The password you entered is incorrect.", 409, NULL, $data);
        break;

        case('password-error-cred'):
          throw new FistoException("You don't have the proper credentials to perform this action.", 401, NULL, $data);
        break;

        case('login'):
          return $this->result(200,"Succesfully login.",$data);
        break;

        case('logout'):
          return $this->result(200,"User has been logged out.",$data);
        break;

        case('logout-again'):
          throw new FistoException("User is already logged out.", 401, NULL, []);
        break;

        case('login-error'):
          throw new FistoException("Invalid username or password.", 409, NULL, $data);
        break;

        case('available'):
          return $this->result(200,$modelName." is available.",$data);
        break;

        case('password-reset'):
          return $this->result(200,"User's default password has been restored.",$data);
        break;
      }
    }

    public function getDuplicateInputs($object,$param,$dbfield){
    return $duplicatelocationCode = $object->filter(function ($q) use ($dbfield,$param){
        return strtolower((string)$q["$dbfield"]) === strtolower((string)$param);
      });
    }

  

}
