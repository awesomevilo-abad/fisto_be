<?php
namespace App\Methods;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterlistMethod{

    public static function restore($table){
        $specific_data = DB::table($table)->where('id',$id);

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
