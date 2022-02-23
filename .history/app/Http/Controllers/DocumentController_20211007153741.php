<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $is_active = $request->get('is_active');

        if ($is_active == 'true') {
            $documents = DB::table('documents AS d')
                ->select('d.id AS did')
                ->where('d.is_active', '=', 1)
                ->latest()
                ->groupBy('did')
                ->get();

        } elseif ($is_active == 'false') {
            $documents = DB::table('documents AS d')
                ->select('d.id AS did')
                ->where('d.is_active', '=', 0)
                ->latest()
                ->groupBy('did')
                ->get();

        } else {
            $documents = DB::table('documents AS d')
                ->select('d.id AS did')
                ->latest()
                ->groupBy('did')
                ->get();
        }

        $document_ids = $documents->pluck('did');

        $categories = $documents->pluck('categories');

        $category_ids = collect();
        foreach ($document_ids as $doc_id) {
            $result = DB::table('documents AS d')
                ->select('c.id AS categories')
                ->join('document_categories AS dc', 'd.id', '=', 'dc.document_id')
                ->join('categories AS c', 'dc.category_id', '=', 'c.id')
                ->where('d.id', '=', $doc_id)
                ->where('dc.is_active', '=', 1)
                ->get();
            $category_ids->push(['doc_id' => $doc_id, 'cat_id' => $result->pluck('categories')]);

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
                "document_id" => $document_details_array_id
                , "document_type" => $document_details_array_document_type
                , "document_description" => $document_details_array_document_description
                , "is_active" => $document_details_array_document_is_active
                , "categories" => $cat_id,
            ]));

        }
        foreach ($document_categories as $doc_cate) {
            array_push($new_document_categories, $doc_cate[0]);
        }
        return $new_document_categories;

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $fields = $request->validate([
            'document_type' => 'required|string|unique:documents,document_type',
            'document_description' => 'required|string|unique:documents,document_description',
            'is_active' => 'required',

        ]);

        // VALIDATION IF CATEGORY IS EXISTING OR NOT

        $document_categories = DB::table('categories AS c')
            ->where('c.is_active', '1')
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
            echo $unregistered_category . ' is not existing in the category table or disabled';
        } else {

            // INSERT DOCUMENT

            $new_document = Document::create([
                'document_type' => $fields['document_type']
                , 'document_description' => $fields['document_description']
                , 'is_active' => $fields['is_active'],
            ]);

            $category_ids = $request['categories'];
            $new_document->categories()->attach($category_ids);

            return [
                'success_message' => 'Succesfully Created!',
            ];
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $category_ids = collect();
        $result = DB::table('documents AS d')
            ->select('c.id AS categories')
            ->leftJoin('document_categories AS dc', 'd.id', '=', 'dc.document_id')
            ->leftJoin('categories AS c', 'dc.category_id', '=', 'c.id')
        // ->where('d.is_active', '=', 1)
            ->where('d.id', '=', $id)
            ->get();

        if ($result->isEmpty()) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }

        $category_ids->push(['doc_id' => $id, 'cat_id' => $result->pluck('categories')]);

        $document_categories = [];
        $new_document_categories = [];
        foreach ($category_ids as $specific_document) {
            $doc_id = $specific_document['doc_id'];
            $cat_id = $specific_document['cat_id'];

            $document_details = DB::table('documents AS d')
                ->select('id', 'document_type', 'document_description', 'is_active')
            // ->where('is_active', '=', 1)
                ->where('id', '=', $doc_id)
                ->get();

            // dd($cat_id);
            // $document_details['categories'] = $cat_id;
            // array_push($document_categories, $document_details);

            $document_details_array = $document_details->toArray();
            $document_details_array_id = $document_details_array[0]->id;
            $document_details_array_document_type = $document_details_array[0]->document_type;
            $document_details_array_document_description = $document_details_array[0]->document_description;
            $document_details_array_document_is_active = $document_details_array[0]->is_active;

            array_push($document_categories, array([
                "document_id" => $document_details_array_id
                , "document_type" => $document_details_array_document_type
                , "document_description" => $document_details_array_document_description
                , "is_active" => $document_details_array_document_is_active
                , "categories" => $cat_id,
            ]));

        }
        foreach ($document_categories as $doc_cate) {
            array_push($new_document_categories, $doc_cate[0]);
        }
        return $new_document_categories;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $specific_document = Document::find($id);

        $fields = $request->validate([
            'document_type' => ['unique:documents,document_type,' . $id],
            'document_description' => ['unique:documents,document_description,' . $id],

        ]);

        if (!$specific_document) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }

        $specific_document->document_type = $request->get('document_type');
        $specific_document->document_description = $request->get('document_description');

        $category_ids = $request['categories'];
        $specific_document->categories()->detach();
        $specific_document->categories()->attach($category_ids);

        $specific_document->save();

        return [
            'success_message' => 'Succesfully Updated!',
        ];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    #_________________________SPECIAL CASE________________________________
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function archive(Request $request, $id)
    {
        $specific_document = Document::find($id);

        if (!$specific_document) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }

        $specific_document->is_active = 0;
        $specific_document->save();

        $specific_document_category_details = DB::table('document_categories')
            ->where('document_id', '=', $id)
            ->update(['is_active' => 0]);

        $users = DB::table('users')->latest()->get();

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

        return [
            'success_message' => 'Succesfully Archived! & User`s Masterlist was modified',
        ];

    }

    public function search(Request $request)
    {

        $value = $request['value'];

        if (isset($request['is_active'])) {
            if ($request['is_active'] == 'active') {

                $is_active = 1;
            } else {

                $is_active = 0;
            }
        } else {
            $is_active = 1;
        }

        $category_ids = collect();
        $result = DB::table('documents AS d')
            ->select('d.id AS did', 'c.id AS categories')
            ->leftjoin('document_categories AS dc', 'd.id', '=', 'dc.document_id')
            ->leftjoin('categories AS c', 'dc.category_id', '=', 'c.id')
            ->where('d.is_active', '=', $is_active)
            ->where(function ($query) use ($value) {
                $query->where('d.document_type', 'like', '%' . $value . '%')
                    ->orWhere('d.document_description', 'like', '%' . $value . '%');
            })
            ->get();

        if ($result->isEmpty()) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }

        foreach ($result as $specific_result) {
            $category_ids->push(['doc_id' => $specific_result->did, 'cat_id' => $result->pluck('categories')]);
        }

        $document_categories = [];
        $document_categories_no_duplicates = [];
        foreach ($category_ids as $specific_document) {
            $doc_id = $specific_document['doc_id'];
            $cat_id = $specific_document['cat_id'];

            $document_details = DB::table('documents AS d')
                ->select('id', 'document_type', 'document_description', 'is_active')
                ->where('is_active', '=', 1)
                ->where('id', '=', $doc_id)
                ->get();

            // dd($cat_id);
            $document_details['categories'] = $cat_id;
            array_push($document_categories, $document_details);

        }
        $document_categories_no_duplicates = array_unique($document_categories);
        return response()->json([
            'search_result' => $document_categories_no_duplicates,
        ]);
    }

    public function documents(Request $request)
    {
        $is_active = $request->get('is_active');

        if ($is_active == 'active') {
            $documents = DB::table('documents AS d')
                ->select('d.id AS did')
                ->where('d.is_active', '=', 1)
                ->latest()
                ->groupBy('did')
                ->get();

        } elseif ($is_active == 'inactive') {
            $documents = DB::table('documents AS d')
                ->select('d.id AS did')
                ->where('d.is_active', '=', 0)
                ->latest()
                ->groupBy('did')
                ->get();

        } else {
            $documents = DB::table('documents AS d')
                ->select('d.id AS did')
                ->latest()
                ->groupBy('did')
                ->get();
        }

        $document_ids = $documents->pluck('did');

        $categories = $documents->pluck('categories');

        $category_ids = collect();
        foreach ($document_ids as $doc_id) {
            $result = DB::table('documents AS d')
                ->select('c.id AS categories')
                ->join('document_categories AS dc', 'd.id', '=', 'dc.document_id')
                ->join('categories AS c', 'dc.category_id', '=', 'c.id')
                ->where('d.id', '=', $doc_id)
                ->get();
            $category_ids->push(['doc_id' => $doc_id, 'cat_id' => $result->pluck('categories')]);

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
                "document_id" => $document_details_array_id
                , "document_type" => $document_details_array_document_type
                , "document_description" => $document_details_array_document_description
                , "is_active" => $document_details_array_document_is_active
                , "categories" => $cat_id,
            ]));

        }
        foreach ($document_categories as $doc_cate) {
            array_push($new_document_categories, $doc_cate[0]);
        }
        return $new_document_categories;
    }
}
