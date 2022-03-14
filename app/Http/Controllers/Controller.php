<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Exceptions\FistoException;

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
          throw new FistoException($modelName." not registered or inactive.",404,NULL,$modelName." ID: ".$param);
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

    public function validateIfNothingChangeThenSave($model,$modelName){
      if($model->isClean()){
        return $this->result(200,"Nothing has changed.",[]);
      }else{
          $model->save();
          return $this->result(200,$modelName." has been updated.",$model);
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
        throw new FistoException("Invalid excel template, it should be ".$headers, 406, NULL, []);
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
      return $catego11ry_id = $masterlist->firstWhere('category',$category)['id'];
    }

    public function validateDuplicateInDBFrom2Params($param1,$param2_id,$param2_description,$table,$index){
      $duplicates=[];
      if (!empty($param1)) {
        $duplicateAccountNo = $table->filter(function ($query) use ($param1,$param2_id){
          return (($query['account_no'] == $param1) && ($query['category_id'] ==  $param2_id)); 
        });
        if ($duplicateAccountNo->count() > 0)
          $duplicates[] = (object) [
            "error_type" => "existing",
            "line" => (string) $index,
            "description" => "Category: ".$param2_description. ", Account No.: ".$param1. " is already registered."
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
            "description" => "Location: ".$param1. " is not registered."
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
            "description" => "Category: ".$param1. " is not registered."
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
            "description" => "Supplier: ".$param1. " is not registered."
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
            return $this->result(200,$modelName." has been archived.",[]);
        }
        throw new FistoException("No records found.", 404, NULL, []);
      }else {
          $restore = $model::onlyTrashed()->where('id',$id)->restore();
          if($restore == 1){
              return $this->result(200,$modelName." has been restored.",[]);
          }
          throw new FistoException("No records found.", 404, NULL, []);
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
         $count =  $query->firstWhere("$params[$k]", $fields[$k]);
         if($count){
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
}
