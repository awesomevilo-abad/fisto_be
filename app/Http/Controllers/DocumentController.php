<?php

namespace App\Http\Controllers;

use App\Methods\GenericMethod;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Facades\Route;
use App\Exceptions\FistoException;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $status =  $request['status'];
        $rows =  (empty($request['rows']))?10:(int)$request['rows'];
        $search =  $request['search'];

        $documents = Document::withTrashed()
        ->with('categories')
        ->where(function ($query) use ($status){
          return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
        })
        ->where(function ($query) use ($search) {
            $query->where('documents.type', 'like', '%' . $search . '%')
                ->orWhere('documents.description', 'like', '%' . $search . '%');
        })
        ->select(['id','type','description','created_at','updated_at','deleted_at'])
        ->latest('updated_at')
        ->paginate($rows);

        if(count($documents)==true){
            return $this->result(200,"Documents has been fetched.",$documents);
          }
          throw new FistoException("No records found.", 404, NULL, []);

    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'type' => 'required|string',
            'description' => 'required|string',
        ]);

        $duplicateDocumentType =  GenericMethod::validateDuplicateDocumentType($fields['type']);
        if(count($duplicateDocumentType)>0) {
            $code =403;
            $message = "Document Already Registered";
            $data = [];
            return $this->result($code,$message,$data);
        }

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
                'type' => $fields['type']
                , 'description' => $fields['description']
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
        ->select('d.updated_at','d.deleted_at','c.id AS cat_id','c.name AS cat_name','d.id as docid','d.type','d.description')
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
        $type= $result[0]->type;
        $description= $result[0]->description;
        $updated_at= $result[0]->updated_at;
        $deleted_at= $result[0]->deleted_at;

        $category_ids->push(['document_id' => $docid,
        'type' => $type,
        'description' => $description,
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
            'type' => 'required|string',
            'description' => 'required|string',

        ]);

        $validateDuplicateDocumentTypeInUpdate =  GenericMethod::validateDuplicateDocumentTypeInUpdate($fields['type'],$id);
        if(count($validateDuplicateDocumentTypeInUpdate)>0) {
            throw new FistoException("Document already registered.", 409, NULL, []);
        }

        if (!$specific_document) {
            $code = 404;
            $message = "Data Not Found!";
            $data = [];
            return $this->result($code,$message,$data);
        }
        

        $specific_document->type = $request->get('type');
        $specific_document->description = $request->get('description');

        $category_ids = $request['categories'];
        $specific_document->categories()->detach();
        $specific_document->categories()->attach($category_ids);
        return $this->validateIfNothingChangeThenSave($specific_document,'Document');
    }

    public function change_status(Request $request,$id){
        $status = $request['status'];
        $model = new Document();
        return $this->change_masterlist_status($status,$model,$id,'Document');
    }

    public function documents(Request $request,$status)
    {

        $value = $request['value'];
        $category_ids = collect();
        if($status == 1){
            $result = DB::table('documents AS d')
            ->select('d.id AS did','d.updated_at','d.deleted_at')
            ->leftjoin('document_categories AS dc', 'd.id', '=', 'dc.document_id')
            ->whereNull('d.deleted_at')
            ->where(function ($query) use ($value) {
                $query->where('d.type', 'like', '%' . $value . '%')
                    ->orWhere('d.description', 'like', '%' . $value . '%');
            })
            ->get();
        }else{
            $result = DB::table('documents AS d')
            ->select('d.id AS did','d.updated_at','d.deleted_at')
            ->leftjoin('document_categories AS dc', 'd.id', '=', 'dc.document_id')
            ->whereNotNull('d.deleted_at')
            ->where(function ($query) use ($value) {
                $query->where('d.type', 'like', '%' . $value . '%')
                    ->orWhere('d.description', 'like', '%' . $value . '%');
            })
            ->get();
        }
        if ($result->isEmpty()) {
            $code = 404;
            $message = "Data Not Found";
            $data = [];
            return $this->result($code,$message,$data);
        }
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
            $category_object = collect();
            foreach($getCategories as $specific_getCategories){
                $category_object->push(['id'=>$specific_getCategories->cat_id,
                'name'=>$specific_getCategories->cat_name]);
            }
            $category_ids->push(['doc_id' => $specific_unique_all_did, 'cat_id' => $category_object]);
        }
        $document_categories = [];
        $document_categories_no_duplicates = [];
        $documents = collect();
        foreach ($category_ids as $specific_document) {
            $doc_id = $specific_document['doc_id'];
            $cat_id = $specific_document['cat_id'];
            $document_details = DB::table('documents AS d')
                ->select('id', 'type', 'description','updated_at','deleted_at')
                ->where('id', '=', $doc_id)
                ->get();
                $documents->push(["id"=>$document_details[0]->id,
                "type"=>$document_details[0]->type,
                "description"=>$document_details[0]->description,
                "categories"=>$cat_id->unique()->values(),
                "updated_at"=>$document_details[0]->updated_at,
                "deleted_at"=>$document_details[0]->deleted_at]);
        }
        $unique_documents= $documents->unique()->values()->sortByDesc('id');
        if (!$document_details || $document_details->isEmpty()) {
            $code = 404;
            $message = "Data Not Found!";
            $data = [];
        }else{
            $code =    200;
            $message = "Succefully Retrieved";
            $data = $unique_documents->unique()->values();
        }
        return $this->result($code,$message,$data);
    }
}
