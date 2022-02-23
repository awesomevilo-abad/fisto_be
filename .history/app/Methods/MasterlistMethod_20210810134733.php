<?php

namespace App\Methods;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterlistMethod{

    public static function restore($table,$id){

        
        $specific_data = DB::table($table)
        ->where('id', $id)
        ->update(['is_active' => 1]);

        if(!$specific_data > 0){
            return [
                'error_message' => 'Data Not Found',
            ];
        }

    }
}
