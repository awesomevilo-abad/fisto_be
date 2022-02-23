<?php
namespace App\Methods;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterlistMethod{

    public static function restore($table){
        $table = DB::table($table)->where

        $specific_user = User::find($id);

        if (!$specific_user) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }

        $specific_user->is_active = 0;
        $specific_user->save();

        return [
            'success_message' => 'Succesfully Archived!',
        ];
    }
}
