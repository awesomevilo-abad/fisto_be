<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Methods\MasterlistMethod;

class MasterlistController extends Controller
{
    public function restore(Request $request){
        $tableName = $request->table;
        $tableid = $request->id;
        MasterlistMethod::restore($tableName,$tableid);

        if($tableName == 'categories'){

            // UPDATE DOCUMENT CATEGORY
                $updated_user = DB::table('document_categories')
                ->where('category_id', '=', $id)
                ->update(['is_active' => 1]);

            // UPDATE USERS
            $users = DB::table('users')->latest()->get();

            foreach ($users as $specific_user) {

                $document_types = json_decode($specific_user->document_types);

                foreach ($document_types as $key => $value) {
                    $document_types[$key]->categories;
                    $categories_per_doc_id = $document_types[$key]->categories;
                    $untag_id_position = array_search($id, $categories_per_doc_id);

                    unset($categories_per_doc_id[$untag_id_position]);

                    $document_types[$key]->categories = $categories_per_doc_id;

                    $document_types[$key]->categories = array_values($document_types[$key]->categories);

                    // $document_types[$key]->categories->save();
                }
                $specific_user->document_types = json_encode($document_types);
                $document_types;
                // $specific_user->save();

                $updated_user = DB::table('users')
                    ->where('id', '=', $specific_user->id)
                    ->update(['document_types' => $document_types]);

            }
            return [
                'success_message' => 'Succesfully Archived! & User`s Masterlist was modified',
            ];

        }


    }
}
