<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;

use Spatie\Activitylog\Models\Activity;

use App\Models\User;
use App\Models\Document;
use App\Models\Department;
use App\Models\Category;
use App\Models\UserDocument;
use App\Models\UserDocumentCategory;
use App\Models\Permission;
use App\Models\Transaction;
use App\Models\OrganizationDepartment;
use App\Http\Requests\UserControllerRequest;

use App\Methods\GenericMethod;
use App\Methods\UserMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;



class UserController extends Controller
{
    public function departmentValidation(Request $request){
        $department = Auth::user()->department[0]['name'];
        if(isset($request->department)){
            $department = $request->department;
        }

        if(!OrganizationDepartment::where('name',$department)->exists()){
            return $this->resultResponse('not-exist-department','Department',collect(['error_field'=>'department']));
        }
        
        return $this->resultResponse('available','Department',[]);
    }

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
            ->orWhereRaw("concat(first_name, ' ', last_name) like '%" .$search. "%' ")
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
            return $this->resultResponse('not-found','User',[]);
        }

        foreach($users as $user)
        {
            $permission_list = [];   
            $new_permissions = [];
            foreach($user['permissions'] as $permission)
            {
                if(count(($permissions->where('id',$permission)))>0)
                {
                    $permission_list['id'] = $permission;
                    $permission_list['description'] = $permissions->where('id',$permission)->first()->name;
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

                if(count($documents->where('id',$document_type['id']))>0)
                {
                
                    $document_description = $documents->where('id',$document_type['id']);
                    $category_ids = $document_type['categories'];
                    if(count($category_ids)>0)
                    {
                        foreach($category_ids as $category_id)
                        {
                            if(count(($categories->where('id',$category_id)))>0)
                            {
                                $category_description = $categories->where('id',$category_id)->first()->name;
                                $new_category_list['id'] = $category_id;
                                $new_category_list['name'] = $category_description;
                                array_push($new_categories,$new_category_list);
                            }
    
                        }
                    }
                    $new_document_type_list['id'] = ($document_description->values()->first()->id);
                    $new_document_type_list['type'] = ($document_description->values()->first()->type);
                    $new_document_type_list['categories'] = $new_categories;
                    array_push($new_document_types,$new_document_type_list);
                }

            }
            $user['document_types'] =  $new_document_types;
        }
        return $this->resultResponse('fetch','User',$users);
    }

    public function store(UserControllerRequest $request)
    {
        $fields = $request->validated();
        $existing_user =   User::withTrashed()->where('id_prefix',$fields['id_prefix'])->where('id_no',$fields['id_no'])->first();
        if(!empty($existing_user)){
            return $this->resultResponse('registered','User',$fields['id_prefix'].'-'.$fields['id_no']);
        }
        $document_types =  $fields['document_types'];
        $document_ids = array_column($document_types,'id');
        $fields['password'] = bcrypt(strtolower($fields['username']));
        
        $new_user = User::create($fields);

        foreach($document_types as $document_type)
        {
            $document_model = new Document();
            $category_model = new Category();
            $document_type_object= $this->validateIfObjectExist($document_model,$document_type['id'],'Document');

            $categories= $document_type['categories'];
            $this->validateIfObjectsExist($category_model,$categories,'Category');
            $new_user->documents()->attach($document_ids);
            $document_type_object->document_categories()->attach($categories,['user_id' => $new_user->id]);
        }
        return $this->resultResponse('save','User',$new_user);
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
        return $this->resultResponse('fetch','User',$user[0]);
    }

    public function update(Request $request, $id)
    {
        $specific_user = $request;
        $user = User::withTrashed()->find($id);
        $new_user = User::withTrashed()->with('documents.document_categories')->where('id',$id)->first();
        $document_types =  $specific_user['document_types'];
        $document_ids = array_column($document_types,'document_id');
        
        UserMethod::validateIfExist($user,$id);
        UserMethod::synchWithSedarValidation($specific_user,$id);
        $user =  UserMethod::redefinedUserForSaving($user,$specific_user);

        if((!in_array(1,$specific_user['permissions'])) && (!in_array(2,$specific_user['permissions']))  ){
            $user->document_types = [];
            $specific_user['document_types'] = [];
        }
        $is_tagged_array_modified = 0;
        if(!empty($specific_user['document_types'])){
            $is_tagged_array_modified_document = $this->isTaggedArrayModified($document_ids,  $new_user->documents()->get(),'document_id');
            $new_user->documents()->detach();
            $new_user->documents()->attach($document_ids);
            foreach($document_types as $document_type)
            {
                $document_model = new Document();
                $category_model = new Category();
                $document_type_object= $this->validateIfObjectExist($document_model,$document_type['id'],'Document');
                
                $categories= $document_type['categories'];
                $this->validateIfObjectsExist($category_model,$categories,'Category');
                $is_tagged_array_modified_category = $this->isTaggedArrayModified($document_type['categories'],  $document_type_object->document_categories()->get(),'id');
                
                $document_type_object->document_categories()->detach();
                $document_type_object->document_categories()->attach($categories,['user_id' => $new_user->id]);
            }
            $user->document_types = $specific_user['document_types'];
            $is_tagged_array_modified = $this->isMultipleTaggedArrayModified($is_tagged_array_modified_document,$is_tagged_array_modified_category);
        }
        return $this->validateIfNothingChangeThenSave($user,'User',$is_tagged_array_modified);
    }

    public function change_status(Request $request,$id){
        $status = $request['status'];
        $model = new User();
        $is_exist = UserMethod::validateIfTransactionExist($id);
        return $this->change_masterlist_status_user($is_exist,$status,$model,$id,'User');
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
            return $this->resultResponse('password-changed','User',[]);
        }
        else{
            return $this->resultResponse('password-incorrect','User',["error_field"=>"current_password"]);
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
                "result"=>$user,
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
        return $this->resultResponse('logout','User',[]);
      }
      else
        return $this->resultResponse('logout-again','User',[]);
    }

    public function username_validation(Request $request)
    {
        $fields = $request->validate(['username'=>['required','string']]);
        $user = User::withTrashed()->firstWhere('username',$fields['username']);
        if(!empty($user))
        {
            return $this->resultResponse('registered','Username',["error_field" => "username"]);
        }
        return $this->resultResponse('available','Username',[]);
    }

    public function id_validation(Request $request)
    {
        $fields = $request->validate([
            "id_prefix"=>['required','string'],
            "id_no"=>['required','string']
        ]);

        $user = User::withTrashed()->firstWhere([
            "id_prefix"=>$fields['id_prefix'],
            "id_no"=>$fields["id_no"]
        ]);

        if(!empty($user))
        {
            return $this->resultResponse('registered','Employee ID',["error_field" => "id_no"]);
        }else{
            return $this->resultResponse('available','Employee ID',[]);
        }
    }

    public function reset($id)
    {
        
        if( Auth::user()->role == "Administrator")
        {
           $user = User::find($id);
           if(empty($user)){
            return $this->resultResponse('not-found','User',[]);
            }

           $user->password =bcrypt(strtolower($user->username));
           $user->save();
            
           return $this->resultResponse('password-reset','User',[]);
        }  
        return $this->resultResponse('password-error-cred','User',[]);
    }
}
