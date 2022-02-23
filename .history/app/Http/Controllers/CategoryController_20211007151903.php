<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\UserDocumentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
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
            $categories = DB::table('categories')
                ->where('is_active', '=', 1)
                ->latest()
                ->paginate(10);

        } elseif ($is_active == 'inactive') {
            $categories = DB::table('categories')
                ->where('is_active', '=', 0)
                ->latest()
                ->paginate(10);

        } else {
            $categories = DB::table('categories')
                ->latest()
                ->paginate(10);
        }

        if (!$categories || $categories->isEmpty()) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }
        return $categories;
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
            'name' => 'required|string',
            'is_active' => 'required',

        ]);

        $duplicate_category = DB::table('categories')
            ->where('name', $fields['name'])
            ->where('is_active', 1)
            ->get();

        $duplicate_category_inactive = DB::table('categories')
            ->where('name', $fields['name'])
            ->where('is_active', 0)
            ->get();

        if ($duplicate_category->count()) {
            throw ValidationException::withMessages([
                'error_message' => ['Category already registered'],
            ]);
        } elseif ($duplicate_category_inactive->count()) {
            throw ValidationException::withMessages([
                'error_message' => ['Category already registered but inactive'],
            ]);
        }

        $new_category = Category::create([
            'name' => $fields['name']
            , 'is_active' => $fields['is_active'],
        ]);

        return [
            'success_message' => 'Succesfully Created!',
        ];
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $result = Category::find($id);
        if (!$result) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }
        return $result;
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
        $specific_category = Category::find($id);

        $fields = $request->validate([
            'name' => ['unique:categories,name,' . $id],

        ]);

        if (!$specific_category) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }

        $specific_category->name = $request->get('name');
        $specific_category->save();

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
        // UPDATE CATEGORY
        $specific_category = DB::table('categories')
        ->where('id', '=', $id)
        ->where('is_active', '=', 1)
        ->update(['is_active' => 0]);


        if ($specific_category == 0) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }else{

            $updated_user_document_category = DB::table('user_document_category')
                ->where('category_id', '=', $id)
                ->update(['is_active' => 0]);

            // UPDATE DOCUMENT CATEGORY
            $updated_user = DB::table('document_categories')
                ->where('category_id', '=', $id)
                ->update(['is_active' => 0]);

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

        $result = Category::where('name', 'like', '%' . $value . '%')
            ->where('is_active', $is_active)
            ->paginate(10);

        if ($result->isEmpty()) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }
        return response($result);

        // return $result;
    }

    public function categories()
    {
        $categories = DB::table('categories')
            ->where('is_active', '=', 1)
            ->get();

        if (!$categories || $categories->isEmpty()) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }
        return $categories;
    }

}
