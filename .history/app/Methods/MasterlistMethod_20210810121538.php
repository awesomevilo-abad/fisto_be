<?php

namespace App\Methods;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterlistMethod{

    public static function restore($table,$id){

        $specific_data = DB::table($table)
        ->where('id',1)
        ->where('is_active',0)
        ->get();

        if ($specific_data->isEmpty()) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }
        return $specific_data;
        // $specific_data->save();

        return [
            'success_message' => 'Succesfully Restored!',
        ];
    }
}
