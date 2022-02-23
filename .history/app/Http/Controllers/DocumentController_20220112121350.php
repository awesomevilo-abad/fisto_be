<?php

namespace App\Http\Controllers;

use App\Methods\GenericMethod;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Facades\Route;

class DocumentController extends Controller
{
    public function index(Request $request,$status,$tableRows)
    {
        $tableRows = (int)$tableRows;
        $is_active = $status;
        if ($is_active == 1) {
            $documents = DB::table('documents AS d')
                ->select('d.id AS did')
                ->whereNull('deleted_at')
                ->orderBy('updated_at','desc')
                ->groupBy('did')
                ->get();
        } elseif ($is_active == 0) {
            $documents = DB::table('documents AS d')
                ->select('d.id AS did')
                ->whereNotNull('deleted_at')
                ->orderBy('updated_at','desc')
                ->groupBy('did')
                ->get();
        } else {
            $documents = DB::table('documents AS d')
                ->select('d.id AS did')
                ->orderBy('updated_at','desc')
                ->groupBy('did')
                ->get();
        }
        $document_ids = $documents->pluck('did');
        $document_details = collect();
        $category_object = collect();

        foreach ($document_ids as $doc_id) {
            $result = DB::table('documents AS d')
                ->select('d.updated_at','d.deleted_at','c.id AS cat_id','c.name AS cat_name','d.id as docid','d.document_type','d.document_description','d.is_active')
                ->join('document_categories AS dc', 'd.id', '=', 'dc.document_id')
                ->join('categories AS c', 'dc.category_id', '=', 'c.id')
                ->where('d.id', '=', $doc_id)
                ->get();

                foreach($result as  $specific_result){
                    $category_object->push(['id'=>$specific_result->cat_id,
                    'name'=>$specific_result->cat_name]);
                }

                if(isset($result[0])){
                    $docid= $result[0]->docid;
                    $document_type= $result[0]->document_type;
                    $document_description= $result[0]->document_description;
                    $updated_at= $result[0]->updated_at;
                    $deleted_at= $result[0]->deleted_at;

                    $document_details->push(['id' => $docid,
                    'document_type' => $document_type,
                    'document_description' => $document_description,
                    'categories' => $category_object->unique()->values(),
                    'updated_at' => $updated_at,
                    'deleted_at' => $deleted_at]);
                }else{
                    $document_with_no_category = DB::table('documents AS d')
                    ->select('d.id as docid','d.document_type','d.document_description','d.is_active','d.updated_at','d.deleted_at')
                    ->where('d.id', '=', $doc_id)
                    ->get();

                    $document_details->push(['id' => $document_with_no_category[0]->docid,
                    'document_type' => $document_with_no_category[0]->document_type,
                    'document_description' => $document_with_no_category[0]->document_description,
                    'categories' => [],
                    'updated_at' => $document_with_no_category[0]->updated_at,
                    'deleted_at' => $document_with_no_category[0]->deleted_at]);
                }
        }

       $document_with_pagination= GenericMethod::paginateme($document_details,$tableRows);
        if (!$document_details || $document_details->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = [];
        }else{
            $code =    200;
            $message = "Succefully Retrieved";
            $data = $document_with_pagination;
        }

        return $this->result($code,$message,$data);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'document_type' => 'required|string|unique:documents,document_type',
            'document_description' => 'required|string|',
        ]);

        $document_categories = DB::table('categories AS c')
        ->whereNull('c.deleted_at')
            ->select('c.name', 'c.id AS cid')
            ->get();
        $active_categories = $document_categories->pluck('cid');

        $category_details = collect();
        foreach ($request['categories'] as $cat) {

            if ($active_categories->contains($cat)) {
                $category_details->push(['category_is_active' => 1, 'cat_id' => $cat]);
            } else {
                $category_details->push(['category_is_active' => 0, 'cat_id' => $cat]);
            }
        }

        $unregistered_category_detail = [];
        foreach ($category_details as $specific_category_detail) {
            if ($specific_category_detail['category_is_active'] == 0) {
                array_push($unregistered_category_detail, $specific_category_detail['cat_id']);
            }
        }


        if ($unregistered_category_detail) {
            $unregistered_category = implode(',', $unregistered_category_detail);
            $code = 404;
            $message = "Category Not Registered!";
            $data = $unregistered_category;
        } else {
            $new_document = Document::create([
                'document_type' => $fields['document_type']
                , 'document_description' => $fields['document_description']
                , 'is_active' => 1,
            ]);
            $category_ids = $request['categories'];
            $new_document->categories()->attach($category_ids);
            $code =    200;
            $message = "Succefully Created";
            $data = $new_document;
         }
        return $this->result($code,$message,$data);
    }

    public function show($id)
    {
        $category_ids = collect();
        $category_object = collect();

        $result = DB::table('documents AS d')
        ->select('d.updated_at','d.deleted_at','c.id AS cat_id','c.name AS cat_name','d.id as docid','d.document_type','d.document_description','d.is_active')
        ->leftJoin('document_categories AS dc', 'd.id', '=', 'dc.document_id')
        ->leftJoin('categories AS c', 'dc.category_id', '=', 'c.id')
        ->where('d.id', '=', $id)
        ->get();

        if(isset($result[0]->cat_id)){

            foreach($result as  $specific_result){
                if(isset($specific_result->cat_id)){
                    $category_object->push(['id'=>$specific_result->cat_id,
                    'name'=>$specific_result->cat_name]);
                }
            }

        }else{
            $category_object = collect();
        }
        if ($result->isEmpty()) {
            $code = 404;
            $message = "Data Not Found!";
            $data = [];

            return $this->result($code,$message,$data);
        }

        $docid= $result[0]->docid;
        $document_type= $result[0]->document_type;
        $document_description= $result[0]->document_description;
        $is_active= $result[0]->is_active;
        $updated_at= $result[0]->updated_at;
        $deleted_at= $result[0]->deleted_at;

        $category_ids->push(['document_id' => $docid,
        'document_type' => $document_type,
        'document_description' => $document_description,
        'is_active' => $is_active,
        'categories' => $category_object,
        'updated_at' => $updated_at,
        'deleted_at' => $deleted_at]);

         $code = 200;
         $message = "Succesfully Retrieved";
         $data = $category_ids;
         return $this->result($code,$message,$data);
    }

    public function update(Request $request, $id)
    {
        $specific_document = Document::find($id);

        $fields = $request->validate([
            'document_type' => ['unique:documents,document_type,' . $id],
            'document_description' => ['unique:documents,document_description,' . $id],

        ]);

        if (!$specific_document) {
            $code = 404;
            $message = "Data Not Found!";
            $data = [];
            return $this->result($code,$message,$data);
        }

        $specific_document->document_type = $request->get('document_type');
        $specific_document->document_description = $request->get('document_description');

        $category_ids = $request['categories'];
        $specific_document->categories()->detach();
        $specific_document->categories()->attach($category_ids);
        $specific_document->save();

        $code =200;
        $message = "Succefully Updated";
        $data = $specific_document;
        return $this->result($code,$message,$data);
    }

    public function archive(Request $request, $id)
    {
        $softDeletePayrollCategory = Document::where('id',$id)->delete();

        if ($softDeletePayrollCategory == 0) {
            $code = 403;
            $data = [];
            $message = "Data Not Found";
            return $this->result($code,$message,$data);
        }

        $specific_document_category_details = DB::table('document_categories')
            ->where('document_id', '=', $id)
            ->update(['is_active' => 0]);

        $users = DB::table('users')->orderBy('updated_at','desc')->get();

        foreach ($users as $specific_user) {

            $document_types = json_decode($specific_user->document_types);
            foreach ($document_types as $key => $value) {
                if ($document_types[$key]->document_id == $id) {
                    unset($document_types[$key]);
                }
            }

            $document_types = json_encode(array_values($document_types));
            $updated_user = DB::table('users')
                ->where('id', '=', $specific_user->id)
                ->update(['document_types' => $document_types]);
        }

        $code =200;
        $message = "Succefully Archived";
        $data = [];
        return $this->result($code,$message,$data);

    }

    public function restore(Request $request, $id)
    {
        $restoreSoftDelete = Document::onlyTrashed()->find($id)->restore();
        if ($restoreSoftDelete == 1) {
            $code = 200;
            $data = [];
            $message = "Succefully Restored";
        }else{
            $code = 403;
            $data = [];
            $message = "Data Not Found";
        }
        return $this->result($code,$message,$data);
    }


    public function search(Request $request,$status,$tableRows)
    {

        $tableRows = (int)$tableRows;
        $value = $request['value'];

        $category_ids = collect();
        if($status == 1){
            $result = DB::table('documents AS d')
            ->select('d.id AS did','d.updated_at','d.deleted_at','c.id AS cat_id','c.name AS cat_name')
            ->leftjoin('document_categories AS dc', 'd.id', '=', 'dc.document_id')
            ->leftjoin('categories AS c', 'dc.category_id', '=', 'c.id')
            ->whereNull('d.deleted_at')
            ->where(function ($query) use ($value) {
                $query->where('d.document_type', 'like', '%' . $value . '%')
                    ->orWhere('d.document_description', 'like', '%' . $value . '%');
            })
            ->get();
        }else{
            $result = DB::table('documents AS d')
            ->select('d.id AS did','d.updated_at','d.deleted_at','c.id AS cat_id','c.name AS cat_name')
            ->leftjoin('document_categories AS dc', 'd.id', '=', 'dc.document_id')
            ->leftjoin('categories AS c', 'dc.category_id', '=', 'c.id')
            ->whereNotNull('d.deleted_at')
            ->where(function ($query) use ($value) {
                $query->where('d.document_type', 'like', '%' . $value . '%')
                    ->orWhere('d.document_description', 'like', '%' . $value . '%');
            })
            ->get();
        }

        if ($result->isEmpty()) {
            $code = 404;
            $message = "Data Not Found";
            $data = [];
            return $this->result($code,$message,$data);
        }
        if(isset($result[0]->cat_id)){

            $category_object = collect();
            $all_did = [];
            foreach($result as  $specific_result){
                array_push($all_did,$specific_result->did);
            }

            $unique_all_did= array_values(array_unique($all_did));
            foreach($unique_all_did as $specific_unique_all_did){
                $getCategories = DB::table('document_categories AS dc')
                ->select('c.id AS cat_id','c.name AS cat_name')
                ->leftjoin('categories AS c', 'dc.category_id', '=', 'c.id')
                ->where('dc.document_id', '=', $specific_unique_all_did)
                ->get();



                foreach($getCategories as $specific_getCategories){
                    $category_object->push(['id'=>$specific_result->cat_id,
                    'name'=>$specific_result->cat_name]);
                    $getCategories->cat_id;
                    $getCategories->cat_name;
                }
            }
        }else{
            $category_object = collect();
        }

        foreach ($result as $specific_result) {
            $category_ids->push(['doc_id' => $specific_result->did, 'cat_id' => $category_object]);
        }

        $document_categories = [];
        $document_categories_no_duplicates = [];
        $documents = collect();
        foreach ($category_ids as $specific_document) {
            $doc_id = $specific_document['doc_id'];
            $cat_id = $specific_document['cat_id'];

            $document_details = DB::table('documents AS d')
                ->select('id', 'document_type', 'document_description', 'is_active')
                ->where('is_active', '=', 1)
                ->where('id', '=', $doc_id)
                ->get();

                $documents->push(["id"=>$document_details[0]->id,
                "document_type"=>$document_details[0]->document_type,
                "document_description"=>$document_details[0]->document_description,
                "is_active"=>$document_details[0]->is_active,
                "categories"=>$cat_id->unique()->values()]);


        }
        $unique_documents= $documents->unique()->values()->sortByDesc('id');
        $document_with_pagination= GenericMethod::paginateme($unique_documents,$tableRows);
        if (!$document_details || $document_details->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = [];
        }else{
            $code =    200;
            $message = "Succefully Retrieved";
            $data = $document_with_pagination;
        }

        return $this->result($code,$message,$data);

    }

    public function documents(Request $request,$status)
    {
        $is_active = $status;

        if ($is_active == 1) {
            $documents = DB::table('documents AS d')
                ->select('d.id AS did')
                ->whereNull('deleted_at')
                ->orderBy('updated_at','desc')
                ->groupBy('did')
                ->get();

        } elseif ($is_active == 0) {
            $documents = DB::table('documents AS d')
                ->select('d.id AS did')
                ->whereNotNull('deleted_at')
                ->orderBy('updated_at','desc')
                ->groupBy('did')
                ->get();

        } else {
            $documents = DB::table('documents AS d')
                ->select('d.id AS did')
                ->orderBy('updated_at','desc')
                ->groupBy('did')
                ->get();
        }

        $document_ids = $documents->pluck('did');
        $categories = $documents->pluck('categories');

        $category_object = collect();
        $category_ids = collect();

        foreach ($document_ids as $doc_id) {
            $result = DB::table('documents AS d')
                ->select('c.id AS cat_id','c.name as cat_name')
                ->join('document_categories AS dc', 'd.id', '=', 'dc.document_id')
                ->join('categories AS c', 'dc.category_id', '=', 'c.id')
                ->where('d.id', '=', $doc_id)
                ->get();

                foreach($result as  $specific_result){
                    $category_object->push(['id'=>$specific_result->cat_id,
                    'name'=>$specific_result->cat_name]);
                }

                if(isset($result[0])){
                    $category_ids->push(['doc_id' => $doc_id, 'cat_id' => $category_object->unique()->values()]);
                }else{
                    $category_ids->push(['doc_id' => $doc_id, 'cat_id' => []]);
                }

        }

        $document_categories = [];
        $new_document_categories = [];
        foreach ($category_ids as $specific_document) {
            $doc_id = $specific_document['doc_id'];
            $cat_id = $specific_document['cat_id'];

            $document_details = DB::table('documents AS d')
                ->select('id', 'document_type', 'document_description', 'is_active')
                ->where('id', '=', $doc_id)
                ->get();

            $document_details_array = $document_details->toArray();
            $document_details_array_id = $document_details_array[0]->id;
            $document_details_array_document_type = $document_details_array[0]->document_type;
            $document_details_array_document_description = $document_details_array[0]->document_description;
            $document_details_array_document_is_active = $document_details_array[0]->is_active;

            array_push($document_categories, array([
                "id" => $document_details_array_id
                , "document_type" => $document_details_array_document_type
                , "document_description" => $document_details_array_document_description
                , "categories" => $cat_id,
            ]));

        }
        foreach ($document_categories as $doc_cate) {
            array_push($new_document_categories, $doc_cate[0]);
        }

        if (!$document_details || $document_details->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = [];
        }else{
            $code =    200;
            $message = "Succefully Retrieved";
            $data = $new_document_categories;
        }

        return $this->result($code,$message,$data);
    }
}
