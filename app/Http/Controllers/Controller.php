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
      return $category_id = $masterlist->firstWhere('category',$category)['id'];
    }

    public function validateDuplicateInDBFrom2Params($param1,$param2_id,$param2_description,$table,$index){
      $duplicates=[];
      if (!empty($param1)) {
        $duplicateAccountNo = $table->filter(function ($query) use ($param1,$param2_id){
          return (($query['account_no'] == $param1) && ($query['category_id'] ==  $param2_id)); 
        });
        if ($duplicateAccountNo->count() > 0)
          $duplicates[] = (object) [
            "error_type" => "duplicate",
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
            "error_type" => "unregistered location",
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
            "error_type" => "unregistered category",
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
          return (strtolower($query["supplier_name"]) == strtolower($param1)); 
        });
        if ($existingSuppliers->count() == 0)
          $existings[] = (object) [
            "error_type" => "unregistered supplier",
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
            "error_type" => "excel duplicate",
            "line" => (string) $duplicate_lines,
            "description" =>  $object[$firstDuplicateLine]['account_no'].' with '.strtolower($object[$firstDuplicateLine]['category']).' category has a duplicate in your excel file.'
          ];
        }
      }
      return $excelDuplicates;
    }

    public function change_masterlist_status($status,$model,$id){
      if($status == 1){
        $softDelete = $model::where('id',$id)->delete();
        if($softDelete == 1){
            return $this->result(200,"Succefully Archived",[]);
        }
        throw new FistoException("No records found.", 404, NULL, []);
    }else {
        $restore = $model::onlyTrashed()->where('id',$id)->restore();
        if($restore == 1){
            return $this->result(200,"Succefully Restored",[]);
        }
        throw new FistoException("No records found.", 404, NULL, []);
    }
    }
}
