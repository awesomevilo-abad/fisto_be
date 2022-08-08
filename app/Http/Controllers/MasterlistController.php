<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;
use App\Http\Resources\ChargingResource; 
use App\Http\Resources\UserResource; 

use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use App\Models\Document;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\SupplierType;
use App\Models\Referrence;
use App\Models\UtilityLocation;
use App\Models\UtilityCategory;

use App\Models\AccountTitle;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MasterlistController extends Controller
{
  public function documentDropdown(){
    $data =  array("documents"=>Document::whereNull('deleted_at')->with('categories')->get(['id','type','description']));
    return $this->resultResponse('fetch','Document',$data);
  }

  public function categoryDropdown(){
    $data =  array("categories"=>Category::whereNull('deleted_at')->get(['id','name']));
    return $this->resultResponse('fetch','Category',$data);

  }

  public function supplierRefDropdown(){
    $data =  array(
      "supplier_types"=>SupplierType::whereNull('deleted_at')->get(['id','type']),
      "references"=>Referrence::whereNull('deleted_at')->get(['id','type']));
      return $this->resultResponse('fetch','Supplier and Reference',$data);
  }

  public function loccatsupDropdown(){
    $data =  array(
      "locations"=>UtilityLocation::whereNull('deleted_at')->get(['id','location']),
      "categories"=>UtilityCategory::whereNull('deleted_at')->get(['id','category']),
      "suppliers"=>Supplier::whereNull('deleted_at')->get(['id','name']));
      return $this->resultResponse('fetch','Location, Category and Supplier',$data);
  }

  public function loccatDropdown(){
    $data =  array(
      "locations"=>UtilityLocation::whereNull('deleted_at')->get(['id','location']),
      "categories"=>UtilityCategory::whereNull('deleted_at')->get(['id','category']));
      return $this->resultResponse('fetch','Location and Category',$data);
  }

  public function accountTitleDropdown(){
    $data =  array(
      "account_titles"=>AccountTitle::whereNull('deleted_at')->get(['id','title']));
      return $this->resultResponse('fetch','Account Title',$data);
  }

  public function companyDropdown(){
    $data =  array("companies"=>Company::whereNull('deleted_at')->get(['id','company']));
    return $this->resultResponse('fetch','Company',$data);
  }

  public function associateDropdown(Request $request){
    $company_id = $request['company_id'];
    $data =  array("associates"=>User::with('companies')
    ->when(isset($company_id), function ($query) use($company_id) {
      $query->whereHas('companies', function ($query) use($company_id) {
          $query->where('companies.id',$company_id);
      })
      ->without('companies');
    })
     ->where(function ($query){
      $query->where('role','AP Associate')
      ->orWhere('role','AP Specialist');
    })
    ->whereNull('deleted_at')
    ->get(['id',DB::raw("CONCAT(users.first_name,' ',users.last_name)  AS name")]));


    if(count($data['associates'])==0){
      return $this->resultResponse('not-found','',[]);
    }
    
    return $this->resultResponse('fetch','AP Associate',$data);
  }
  
  public function approverDropdown(Request $request){
    $data =  array("approvers"=>User::where('role','Approver')->get(['id',DB::raw("CONCAT(users.first_name,' ',users.last_name)  AS name")]));
    if(count($data['approvers'])==0){
      return $this->resultResponse('not-found','',[]);
    }
    
    return $this->resultResponse('fetch','Approver',$data);
  }

  
  public function chargingDropdown(){
    $company =  DB::table('companies')
    ->get(['id','company']);
   return $company =  ChargingResource::collection($company);
    $company =  collect(['companies' => $company]);
    return $this->resultResponse('fetch','Charging',$company);
  }

  public function currentUser(){
    
    $categories = Category::all();
    $documents = Document::all();
    
    $user = User::withTrashed()
    ->select('id','id_prefix','id_no','role','position','first_name','middle_name','last_name','suffix','department','document_types')
    ->where('id',Auth::id())
    ->latest('updated_at')
    ->first();
    
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
        return $this->resultResponse('fetch','User',$user);



  }

  public function departmentDropdown(Request $request){
    $departments = Department::when(isset($request['all']), function($query) {
      return $query->withTrashed();
    })
    ->get(['id','department as name']);

    $data = array(
      "departments" => $departments
    );
    
    return $this->resultResponse('fetch','Department',$data);
  }
}
