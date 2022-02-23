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

        if($table == 'categories'){

                // UPDATE DOCUMENT CATEGORY
                $updated_user = DB::table('document_categories')
                ->where('category_id', '=', $id)
                ->update(['is_active' => 1]);

                // UPDATE USER DOCUMENT CATEGORY
                $updated_user_document_category = DB::table('user_document_category')
                ->where('category_id', '=', $id)
                ->update(['is_active' => 1]);

                // SELECT USER DOCUMENT CATEGORY
                $get_user_document_categories = DB::table('user_document_category')
                ->where('category_id', '=', $id)
                ->get();

                // return $get_user_document_categories;

                // UPDATE USERS
                $users = DB::table('users')
                ->where('id',7)
                ->get();

                // return $users;

                foreach($get_user_document_categories as $specific_user_document_categories){

                    foreach ($users as $specific_user) {
                        $document_types = json_decode($specific_user->document_types);
                        print_r()
                        // foreach ($document_types as $key => $value) {
                        //     // echo $specific_user_document_categories->document_id;
                        //     // array_push($document_types[$key]->categories,  $id);
                        //     print_r($document_types[$key]->categories);

                        // }
                        $specific_user->document_types = json_encode($document_types);
                        // print_r($specific_user->document_types);
                    }
                }

                // return $users;
                // return [
                // 'success_message' => 'Succesfully Archived! & User`s Masterlist was modified',
                // ];
    }

        // if($specific_data == 0){
        //     $response = [
        //         'message' => 'Data Not Found',
        //     ];
        // }else{
        //     $response = [
        //         'message' => 'Succesfully Added',
        //     ];

        // }

        // return $response;

    }
}
