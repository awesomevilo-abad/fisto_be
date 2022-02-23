<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Methods\MasterlistMethod;

class MasterlistController extends Controller
{
    public function restore(Request $request){
        $tableName = $request->table;
        $tableid = $request->id;
        return MasterlistMethod::restore($tableName,$tableid);
    }

    public function categoryPerDocument(Request $request){
        $fields = $request->validate(['document_id'=>'required']);

        $categories = DB::table('categories')
        ->leftJoin('document_categories','categories.id','=','document_categories.category_id')
        ->where('document_categories.document_id',$fields['document_id'])
        ->get(['categories.id','categories.name']);

        $categories_list = [];
        foreach($categories as $specific_categories){
            array_push($categories_list,array($specific_categories->id =>$specific_categories->name));
        }
        return $categories;
    }

    public function getUserDocumentCategory(){
        // $user = auth()->user();
        return Auth::user()->id;
    }

}
