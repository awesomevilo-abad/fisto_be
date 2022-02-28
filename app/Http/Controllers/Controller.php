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

    public function getEmptyErrorBag($tableName,$index,$errorBag) {
        foreach($tableName as $key=>$value){
            if(empty($value)){
              $errorBag[] = [
                "error_type" => "empty",
                "line" => $index,
                "description" => $key." is empty."
              ];
            }
          }
    }

    public function getUnregisteredErrorBag(){
        
    }
}
