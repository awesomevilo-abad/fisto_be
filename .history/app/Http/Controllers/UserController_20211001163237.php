<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDocumentCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use App\Methods\GenericMethod;

class UserController extends Controller
{


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (Auth::check()) {
             Auth::id();
        }

        $is_active = $request->get('is_active');

        if ($is_active == 'active') {
            $users = DB::table('users')
                ->where('is_active', '=', 1)
                ->orderBy('id')
                ->paginate(10);

        } elseif ($is_active == 'inactive') {
            $users = DB::table('users')
                ->where('is_active', '=', 0)
                ->orderBy('id')
                ->paginate(10);

        } else {
            $users = DB::table('users')
                ->orderBy('id')
                ->paginate(10);
        }

        if (!$users || $users->isEmpty()) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }

        return $users;
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
        //Store User
        $fields = $request->validate([
            'id_prefix' => 'string|required'
            , 'id_no' => 'required'
            , 'role' => 'required|string'
            , 'first_name' => 'required|string'
            , 'middle_name' => 'required|string'
            , 'last_name' => 'required|string'
            , 'suffix' => 'nullable'
            , 'department' => 'required|string'
            , 'position' => 'required|string'
            , 'permissions' => 'required'
            , 'document_types' => 'nullable'
            , 'username' => 'required|string'
            , 'password' => 'required|string|confirmed'
            , 'is_active' => 'required',
        ]);

        $duplicate_id = DB::table('users')
            ->where('id_prefix', $fields['id_prefix'])
            ->where('id_no', $fields['id_no'])
            ->get();

        $duplicate_name = DB::table('users')
            ->where('first_name', $fields['first_name'])
            ->where('middle_name', $fields['middle_name'])
            ->where('last_name', $fields['last_name'])
            ->where('suffix', $fields['suffix'])
            ->get();

        $duplicate_username = DB::table('users')
            ->where('username', $fields['username'])
            ->get();

        if ($duplicate_id->count()) {
            throw ValidationException::withMessages([
                'error_message' => ['ID already registered'],
            ]);
        }

        if ($duplicate_name->count()) {
            throw ValidationException::withMessages([
                'error_message' => ['Name already registered'],
            ]);
        }

        if ($duplicate_username->count()) {
            throw ValidationException::withMessages([
                'error_message' => ['Username already registered'],
            ]);
        }

        // // ___________DOCUMENT CATEGORY TRANSFORMATION IN JSON___________________

        $created_document_categories = [];
        $document = [];
        $document_types_transformed = [];

        $document_categories = $fields['document_types'];

        if (empty($document_categories)) {
            // echo "Wala Laman";
        } else {
            foreach ($document_categories as $specific_document_categories) {
                $document_ids = array_unique(array_column($document_categories, "document_id"));
            }
            foreach ($document_ids as $specific_doc_id) {
                $categories = [];

                foreach ($document_categories as $doc_category_id) {
                    if ($specific_doc_id == $doc_category_id["document_id"]) {
                        array_push($categories, $doc_category_id["category_id"]);
                    }
                }
                array_push($created_document_categories, array("document_id" => $specific_doc_id, "categories" => $categories));
                $fields['document_types'] = $created_document_categories;
            }
        }
        $new_user = User::create([
            'id_prefix' => $fields['id_prefix']
            , 'id_no' => $fields['id_no']
            , 'role' => $fields['role']
            , 'first_name' => $fields['first_name']
            , 'middle_name' => $fields['middle_name']
            , 'last_name' => $fields['last_name']
            , 'suffix' => $fields['suffix']
            , 'department' => $fields['department']
            , 'position' => $fields['position']
            , 'permissions' => $fields['permissions']
            , 'document_types' => $fields['document_types']
            , 'username' => $fields['username']
            , 'password' => bcrypt($fields['password'])

            , 'is_active' => $fields['is_active'],
        ]);


        $user =  User::orderBy('id', 'DESC')->get('id')->first();
        $user_id = $user->id;


        // INSERT THIRD TABLE LOG FOR USER DOCUMENT CATEGORY
        foreach($fields['document_types'] as $specific_document_type){

            if(!isset($specific_document_type['document_id'])){
                $document_id = 0;
            }else{
                $document_id = ($specific_document_type['document_id']);
            }

            if(!isset($specific_document_type['categories'])){
                $categories = 0;
            }else{
                $categories = ($specific_document_type['categories']);
            }

            foreach($categories as $category_id){
                $new_user_document_category = UserDocumentCategory::create([
                    'user_id' =>$user_id,
                    'document_id' =>$document_id,
                    'category_id' =>$category_id,
                    'is_active' => 1,
                ]);
            }
        }

        $response = [
            "user_details" => $new_user,
        ];

        return response($response, 201);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
       return GenericMethod::getUserDetailsById($id);
     }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // $specific_user = User::find($id);

        // return $specific_user;
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

        $specific_user = User::find($id);

        if (!$specific_user) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }

        // ADD TO USER DOCUMENT CATEGORY
        $document_types =  $request['document_types'];
        foreach($document_types as $specific_document_type){
            $inputted_document_types[] =  $specific_document_type['document_id'];
        }

        $get_document_ids =  DB::table('user_document_category')->where('user_id',$id)->get();

        foreach($get_document_ids as $specific_get_document_ids){
            $registered_document_ids[] =  $specific_get_document_ids->document_id;
        }
        $registered_document_ids = array_unique($registered_document_ids);
        $registered_document_ids =  array_values($registered_document_ids);

        $additional_document_ids = array_merge(array_diff($registered_document_ids, $inputted_document_types), array_diff($inputted_document_types, $registered_document_ids));


        foreach($registered_document_ids as $specific_doc_id){
            // Existing Document ID with New Categories
            foreach($registered_document_ids as $specific_document_id){

                $document_type_details = GenericMethod::getCategoriesByUserAndDocID($id,$specific_document_id);
                foreach($document_type_details as $specific_document_type){
                    if(!isset($specific_document_type->category_id)){
                        $registered_category_ids=[];
                    }
                    $registered_category_ids[] =$specific_document_type->category_id;
                }
                $document_type = GenericMethod::where($document_types,'document_id',$specific_document_type->document_id,'categories');

                print_r($document_type);
                // if(count($document_type)> 0){
                //     if(isset($document_type[0]['categories'])){
                //         $categories = $document_type[0]['categories'];
                //     }else{
                //         $categories = [];
                //     }

                //     $inputted_categories = $categories;
                //     $additional_category_ids = array_merge(array_diff($registered_category_ids, $inputted_categories), array_diff($inputted_categories, $registered_category_ids));

                //     if(isset($additional_category_ids)){
                //         foreach($additional_category_ids as $specific_category_id){
                //             if($specific_category_id != 0){
                //                 GenericMethod::addToUserDocumentCategory($id,$specific_document_id,$specific_category_id);
                //             }
                //         }
                        $registered_category_ids = [];
                //     }

                // }
            }
        }

        foreach($additional_document_ids as $specific_doc_id){

            if(in_array($specific_doc_id,$registered_document_ids)){
                echo "untagged";
                // $untagged_document = DB::table('user_document_category')
                // ->where('document_id', $specific_doc_id)
                // ->update(['is_active' => 0]);
            }else{
                echo "tagged";
                //  // TAGGING

                // // Existing Document ID with New Categories
                // foreach($registered_document_ids as $specific_document_id){

                //     $document_type_details = GenericMethod::getCategoriesByUserAndDocID($id,$specific_document_id);
                //     foreach($document_type_details as $specific_document_type){
                //         if(!isset($specific_document_type->category_id)){
                //             $registered_category_ids=[];
                //         }
                //         $registered_category_ids[] =$specific_document_type->category_id;
                //     }
                //     print_r($document_type_details);
                //     // $document_type = GenericMethod::where($document_types,'document_id',$specific_document_type->document_id,'categories');

                //     // if(count($document_type)> 0){
                //     //     if(isset($document_type[0]['categories'])){
                //     //         $categories = $document_type[0]['categories'];
                //     //     }else{
                //     //         $categories = [];
                //     //     }

                //     //     $inputted_categories = $categories;
                //     //     $additional_category_ids = array_merge(array_diff($registered_category_ids, $inputted_categories), array_diff($inputted_categories, $registered_category_ids));

                //     //     if(isset($additional_category_ids)){
                //     //         foreach($additional_category_ids as $specific_category_id){
                //     //             if($specific_category_id != 0){
                //     //                 GenericMethod::addToUserDocumentCategory($id,$specific_document_id,$specific_category_id);
                //     //             }
                //     //         }
                //     //         $registered_category_ids = [];
                //     //     }

                //     // }
                // }

                // // New Document ID with or without categories
                // if(isset($additional_document_ids)){
                //     $new_document_type = collect();
                //     $new_document_type_with_no_category = array();
                //     foreach($additional_document_ids as  $specific_additional_document_id){
                //         foreach($document_types as $specific_document_type){

                //             if($specific_document_type['document_id']  == $specific_additional_document_id){
                //                 $document_type = GenericMethod::where($document_types,'document_id',$specific_additional_document_id,'categories');

                //                 if(count($document_type[0]['categories'])==0){
                //                     array_push($new_document_type_with_no_category,0);
                //                 }

                //                 if(isset($document_type)){
                //                     if(isset($new_document_type_with_no_category)){
                //                         if(empty($new_document_type_with_no_category)){
                //                             foreach($document_type[0]['categories'] as $specific_category_id){
                //                                 GenericMethod::addToUserDocumentCategory($id,$document_type[0]['id'],$specific_category_id);
                //                             }
                //                         }else{
                //                             GenericMethod::addToUserDocumentCategory($id,$document_type[0]['id'],0);
                //                         }

                //                     }
                //                 }

                //             }

                //         };
                //     }
                // }

            }
        }



        // UNTAGGED
        // DOCUMENT

        // CATEGORIES


        // ___________DOCUMENT CATEGORY TRANSFORMATION___________________
        // return $request->get('document_types');

        $created_document_categories = [];
        $document = [];
        $document_types_transformed = [];

        // $document_categories = $request['document_types'];

        // foreach ($document_categories as $specific_document_categories) {
        //     $document_ids = array_unique(array_column($document_categories, "document_id"));
        // }
        // foreach ($document_ids as $specific_doc_id) {
        //     $categories = [];

        //     foreach ($document_categories as $doc_category_id) {
        //         if ($specific_doc_id == $doc_category_id["document_id"]) {
        //             array_push($categories, $doc_category_id["category_id"]);
        //         }
        //     }
        //     array_push($created_document_categories, array("document_id" => $specific_doc_id, "categories" => $categories));
        //     $request['document_types'] = $created_document_categories;
        // }

        $specific_user->id_prefix = $request->get('id_prefix');
        $specific_user->id_no = $request->get('id_no');
        $specific_user->role = $request->get('role');
        $specific_user->first_name = $request->get('first_name');
        $specific_user->middle_name = $request->get('middle_name');
        $specific_user->last_name = $request->get('last_name');
        $specific_user->suffix = $request->get('suffix');

        $specific_user->department = $request->get('department');
        $specific_user->position = $request->get('position');
        $specific_user->permissions = $request->get('permissions');
        $specific_user->document_types = $request->get('document_types');
        $specific_user->username = $request->get('username');
        $specific_user->save();

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
        $specific_user = User::find($id);

        if (!$specific_user) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }
        $specific_user->destroy();

        return "Succesfully Deleted";

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

        $result = User::where('is_active', $is_active)
            ->where(function ($query) use ($value) {

                $query->where('id_prefix', 'like', '%' . $value . '%')
                    ->orWhere('id_no', 'like', '%' . $value . '%')
                    ->orWhere('first_name', 'like', '%' . $value . '%')
                    ->orWhere('middle_name', 'like', '%' . $value . '%')
                    ->orWhere('last_name', 'like', '%' . $value . '%')
                    ->orWhere('suffix', 'like', '%' . $value . '%')
                    ->orWhere('department', 'like', '%' . $value . '%')
                    ->orWhere('position', 'like', '%' . $value . '%')
                    ->orWhere('permissions', 'like', '%' . $value . '%')
                    ->orWhere('document_types', 'like', '%' . $value . '%')
                    ->orWhere('username', 'like', '%' . $value . '%')
                    ->orWhere('is_active', 'like', '%' . $value . '%');
            })

            ->get();

        if ($result->isEmpty()) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }

        return response()->json([
            'search_result' => $result,
        ]);

    }

    /**
     * Update the User Password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function change_password(Request $request, $id)
    {
        $fields = $request->validate([
            'password' => 'required|confirmed',
            'old_password' => 'required',
        ]);
        $specific_user = User::find($id);

        if (!$specific_user) {
            return [
                'error_message' => 'Data Not Found',
            ];
        }
        $get_old_password = DB::table('users')
            ->select('password')
            ->where('id', $id)
            ->get();

        // Check Password
        $old_password = $get_old_password[0]->password;
        if (Hash::check($fields['old_password'], $old_password)) {

            $specific_user->password = bcrypt($fields['password']);
            $specific_user->save();

            return "Password Changed Succesfully!";

        } else {
            return [
                'error_message' => 'Data Not Found',
            ];
        }

    }

    public function login(Request $request)
    {
        // return $request;
        if(Auth::attempt($request->only('username', 'password'))){
            $user = Auth::user();
            $user= User::where('username', $request->username)->first();
            $token = $user->createToken('my-app-token')->plainTextToken;


            $token_collection = collect(["token"=>$token]);
            $result =  $user->push($token_collection);
            return $result;

            foreach($users as $user)
            {
                $leaders->push([
                    'name' => $user->name,
                    'username' => $user->username,
                ])
            }


            $response = [
                'user' => $user,
                // 'token' => $token,
            ];

            $cookie = cookie('sanctum', $token, 3600);

            return response($response, 201)->withCookie($cookie);
        }
        return response ([
            'error' => 'Invalid Credentials',
        ], Response::HTTP_UNAUTHORIZED);


    }

    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();

        return [
            'message' => 'Logged out',
        ];
    }

    public function username_validation(Request $request)
    {
        $username = $request->get('username');
        // $id_prefix = $request->get('id_prefix');
        // $id_no = $request->get('id_no');

        $result = DB::table('users')
            ->where('username', '=', $username)
            ->get();

        // if ($result->isEmpty()) {
        //     return [
        //         'error_message' => 'Data Not Found',
        //     ];
        // }

        return $result;

    }

    public function id_validation(Request $request)
    {
        // $id_prefix = $request->get('id_prefix');
        $id_no = $request->get('id_no');

        $result = DB::table('users')
            ->where('id_no', '=', $id_no)
            ->get();

        // if ($result->isEmpty()) {
        //     return [
        //         'error_message' => 'Data Not Found',
        //     ];
        // }

        return $result;

    }

}
