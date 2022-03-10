<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;

use App\Models\User;
use App\Models\Document;
use App\Models\Category;
use App\Models\UserDocument;
use App\Models\UserDocumentCategory;
use App\Models\Permission;
use App\Http\Requests\UserControllerRequest;

use App\Methods\GenericMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;



class UserController extends Controller
{
    public function index(Request $request)
    {
        $status =  $request['status'];
        $rows =  (empty($request['rows']))?10:(int)$request['rows'];
        $search =  $request['search'];

        $categories = Category::all();
        $documents = Document::all();
        $permissions = Permission::all();
        
        $users = User::withTrashed()
        ->where(function ($query) use ($status){
          ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
        })
        ->where(function ($query) use ($search) {
            $query->where('id_prefix', 'like', '%' . $search . '%')
            ->orWhere('id_no', 'like', '%' . $search . '%')
            ->orWhere('first_name', 'like', '%' . $search . '%')
            ->orWhere('middle_name', 'like', '%' . $search . '%')
            ->orWhere('last_name', 'like', '%' . $search . '%')
            ->orWhere('suffix', 'like', '%' . $search . '%')
            ->orWhere('department', 'like', '%' . $search . '%')
            ->orWhere('position', 'like', '%' . $search . '%')
            ->orWhere('username', 'like', '%' . $search . '%')
            ->orWhere('role', 'like', '%' . $search . '%');
        })
        ->latest('updated_at')
        ->paginate($rows);
        
        if(count($users)!=true){
            throw new FistoException("No records found.", 404, NULL, []);
        }

        foreach($users as $user)
        {
            $permission_list = [];   
            $new_permissions = [];
            foreach($user['permissions'] as $permission)
            {
                if(count(($permissions->where('id',$permission)))>0)
                {
                    $permission_list['permission_id'] = $permission;
                    $permission_list['permission_description'] = $permissions->where('id',$permission)->first()->name;
                    array_push($new_permissions,$permission_list);
                }
            }
            $user['permissions'] = ($new_permissions);
            $new_document_type_list = [];
            $new_document_types = [];
            foreach($user['document_types'] as $document_type)
            {
                $new_category_list = [];
                $new_categories = [];

                if(count(($documents->where('id',$document_type['document_id'])))>0)
                {
                
                    $document_description = $documents->where('id',$document_type['document_id']);
                    $category_ids = $document_type['category_ids'];
                    if(count($category_ids)>0)
                    {
                        foreach($category_ids as $category_id)
                        {
                            if(count(($categories->where('id',$category_id)))>0)
                            {
                                $category_description = $categories->where('id',$category_id)->first()->name;
                                $new_category_list['category_id'] = $category_id;
                                $new_category_list['category_name'] = $category_description;
                                array_push($new_categories,$new_category_list);
                            }
    
                        }
                    }
                    $new_document_type_list['document_id'] = ($document_description->values()->first()->id);
                    $new_document_type_list['document_description'] = ($document_description->values()->first()->document_type);
                    $new_document_type_list['document_categories'] = $new_categories;
                    array_push($new_document_types,$new_document_type_list);
                }

            }
            $user['document_types'] =  $new_document_types;
        }
        return $this->result(200,"Users has been fetched.",$users);
    }

    public function store(UserControllerRequest $request)
    {
        $fields = $request->validated();
        $existing_user =   User::withTrashed()->where('id_prefix',$fields['id_prefix'])->where('id_no',$fields['id_no'])->first();
        if(!empty($existing_user)){
            throw new FistoException("User already registered.",409,NULL,$fields['id_prefix'].'-'.$fields['id_no']);
        }
        $document_types =  $fields['document_types'];
        $document_ids = array_column($document_types,'document_id');
        $fields['password'] = bcrypt(strtolower($fields['username']));
        
        foreach($document_types as $document_type)
        {
            $document_model = new Document();
            $category_model = new Category();
            $document_type_object= $this->validateIfObjectExist($document_model,$document_type['document_id'],'Document');

            $categories= $document_type['category_ids'];
            $this->validateIfObjectsExist($category_model,$categories,'Category');

            $new_user = User::create($fields);
            $new_user->documents()->attach($document_ids);
            $document_type_object->document_categories()->attach($categories,['user_id' => $new_user->id]);
        }
        
        return $this->result(201,"New user has been saved.",$new_user);
    }

    public function show($id)
    {
        $user = User::withTrashed()
        ->where('id',$id)
        ->select([
            'id','id_prefix','id_no','role',
            'first_name','middle_name','role','first_name',
            'middle_name','last_name','suffix','department'
            ,'position','permissions','document_types','username'
        ])
        ->get();

        if(count($user)!=true){
            throw new FistoException("No records found.", 404, NULL, []);
        }

        return $this->result(200,"Users has been fetched.",$user);
    }

    public function update(Request $request, $id)
    {
        
        $user = User::withTrashed()->find($id);

        if (!$user) {
            throw new FistoException("No records found.", 404, NULL, []);     
        }         
        $specific_user = $request;
        $document_types =  $specific_user['document_types'];
        $document_ids = array_column($document_types,'document_id');
        $new_user = User::withTrashed()
        ->with('documents.document_categories')
        ->where('id',$id)
        ->first();
        $new_user->documents()->detach();
        $new_user->documents()->attach($document_ids);
        foreach($document_types as $document_type)
        {
            $document_model = new Document();
            $category_model = new Category();
            $document_type_object= $this->validateIfObjectExist($document_model,$document_type['document_id'],'Document');

            $categories= $document_type['category_ids'];
            $this->validateIfObjectsExist($category_model,$categories,'Category');

            $document_type_object->document_categories()->detach();
            $document_type_object->document_categories()->attach($categories,['user_id' => $new_user->id]);
        }

        $user->permissions = $specific_user['permissions'];
        $user->document_types = $specific_user['document_types'];
        return $this->validateIfNothingChangeThenSave($user,'User');
}

    public function change_status(Request $request,$id){
    $status = $request['status'];
    $model = new User();
    return $this->change_masterlist_status($status,$model,$id,'User');
  }
    public function change_password(Request $request)
    {
        $fields = $request->validate([
            'current' => ['required'],
            'password' => ['required','confirmed'],
        ]);

        $user = Auth::user();
        if(Hash::check($fields['current'],$user->password)){
            $user->password =bcrypt(strtolower($fields['password']));
            $user->save();
            return $this->result(200,"Password has been changed.",[]);
        }
        else{
            throw new FistoException('The Password you entered is incorrect',409,NULL,["error_field"=>"current_password"]);
        }
    }

    public function login(Request $request)
    {
        // return $request;
        if(Auth::attempt($request->only('username', 'password'))){
            $user = Auth::user();
            $user= User::where('username', $request->username)->first();
            $token = $user->createToken('my-app-token')->plainTextToken;

            $user['token'] = $token;
            $response = [
                "code"=>201,
                "message"=>"Succesfully Login",
                "data"=>$user,
            ];

            $cookie = cookie('sanctum', $token, 3600);

            return response($response, 200)->withCookie($cookie);
            
        }
        return response ([
            "code"=>401,
            "message"=>"Invalid Username or Password.",
            "data"=>[],
        ], Response::HTTP_UNAUTHORIZED);


    }

    public function logout()
    {
      $logout = auth()->user()->tokens()->delete();

      if ($logout == true) {
        $result = [
          "code" => 200,
          "message" => "User has been logged out.",
          "result" => []
        ];
        
        return response($result);
      }
      else
        throw new FistoException("User is already logged out.", 401, NULL, []);
    }

    public function username_validation(Request $request)
    {
        $fields = $request->validate(['username'=>['required','string']]);
        $user = User::firstWhere('username',$fields['username']);
        if(!empty($user))
        {throw new FistoException("Username is already registered.", 409, NULL, ["error_field" => "username"]);}
        return $this->result(200,"Username is available.",[]);
    }

    public function id_validation(Request $request)
    {
        $fields = $request->validate([
            "id_prefix"=>['required','string'],
            "id_no"=>['required','string']
        ]);

        $user = User::firstWhere([
            "id_prefix"=>$fields['id_prefix'],
            "id_no"=>$fields["id_no"]
        ]);

        if(!empty($user))
        {
            throw new FistoException("Employee ID is already registered.", 409, NULL, ["error_field" => "id_no"]);
        }else{
            return $this->result(200,"Employee ID is available.",[]);
        }
    }

    public function reset($id)
    {
        if( Auth::user()->role == "Administrator")
        {
           $user = User::find($id);
           $user->password =bcrypt(strtolower($user->username));
           $user->save();
            
           return $this->result(200,"User's default password has been restored.",[]);
        }  
        throw new FistoException("You don't have the proper credentials to perform this action.", 401, NULL, []);
    }
}
