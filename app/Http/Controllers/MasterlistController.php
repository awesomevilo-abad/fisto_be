<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;
use App\Http\Resources\ChargingResource; 

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

  public function associateDropdown(){
    $data =  array("associates"=>User::where('role','AP Associate')->whereNull('deleted_at')->get(['id',DB::raw("CONCAT(users.first_name,' ',users.last_name)  AS name")]));
    return $this->resultResponse('fetch','AP Associate',$data);
  }

  
  public function chargingDropdown(){


    $company =  DB::table('companies')
    ->get(['id','company']);
    $company =  ChargingResource::collection($company);
    $company =  collect(['companies' => $company]);
    return $this->resultResponse('fetch','Charging',$company);
  }

}
