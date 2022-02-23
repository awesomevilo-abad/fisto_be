<?php
namespace App\CustomClass;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use App\t_log;
use App\User;
class record_log
{
    public static function  save_log($token, $ket_log, $post_log)
    {
      try{
        $getuser = User::where('api_token', $token)->first();
        if(!$getuser){
          $id = 0;
        }else{
          $id =   $getuser->id_user;
        }
        try {
            $data =  t_log::create([
              'id_user'=> $id,
              'ket_log'=> $ket_log,
              'post_log'=> $post_log,
            ]);
        } catch (\Illuminate\Database\QueryException $ex) {
          //dd ($ex->getMessage());
        }
      } catch(\Illuminate\Database\QueryException $ex){
          //dd ($ex->getMessage());
      }

    }
}
