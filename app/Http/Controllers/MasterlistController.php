<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;

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
    return $this->result(200,'Documents has been fetched.',$data);
  }

  public function categoryDropdown(){
    $data =  array("categories"=>Category::whereNull('deleted_at')->get(['id','name']));
    return $this->result(200,'Category has been fetched.',$data);

  }

  public function supplierRefDropdown(){
    $data =  array(
      "supplier_types"=>SupplierType::whereNull('deleted_at')->get(['id','type']),
      "references"=>Referrence::whereNull('deleted_at')->get(['id','type']));
      return $this->result(200,'Category has been fetched.',$data);
  }

  public function loccatsupDropdown(){
    $data =  array(
      "locations"=>UtilityLocation::whereNull('deleted_at')->get(['id','location']),
      "categories"=>UtilityCategory::whereNull('deleted_at')->get(['id','category']),
      "suppliers"=>Supplier::whereNull('deleted_at')->get(['id','name']));
      return $this->result(200,'Location, Category and Supplier has been fetched.',$data);
  }

  public function loccatDropdown(){
    $data =  array(
      "locations"=>UtilityLocation::whereNull('deleted_at')->get(['id','location']),
      "categories"=>UtilityCategory::whereNull('deleted_at')->get(['id','category']));
      return $this->result(200,'Location and Category has been fetched.',$data);
  }

  public function accountTitleDropdown(){
    $data =  array(
      "account_titles"=>AccountTitle::whereNull('deleted_at')->get(['id','title']));
      return $this->result(200,'Account Title has been fetched.',$data);
  }

}
