<?php

namespace App\Methods;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterlistMethod{

    public static function restore($table,$id){

        $specific_data = DB::table($table)
        ->where('id', $id)
        ->where('is_active', 0)
        ->update(['is_active' => 1]);

        $specific_data

        if($specific_data == 0){
            $response = [
                'message' => 'Data Not Found',
            ];
        }else{
            $response = [
                'message' => 'Succesfully Added',
            ];

        }

        return $response;

    }
}
