<?php

namespace App\Methods;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterlistMethod{

    public static function restore($table,$id){

        $specific_data = DB::table($table)
        ->where('id',$id)
        ->where('is_active',0)
        ->get();

        $specific_data = DB::table('subject_user')->where('user_id', $value)->update(['auth_teacher' => 1]);


        if ($specific_data->isEmpty()) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }


        return [
            'success_message' => 'Succesfully Restored!',
        ];
    }
}
