<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;

use App\Models\Masterlist;

use App\Models\SupplierType;
use App\Models\Referrence;

use App\Models\UtilityLocation;
use App\Models\UtilityCategory;
use App\Models\Supplier;

use App\Models\AccountTitle;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterlistController extends Controller
{
  public function suppliersDropdown()
  {
    $supplier_types = SupplierType::orderBy('supplier_type')->get(['id as supplier_type_id','type as supplier_type']);
    $references = Referrence::orderBy('referrence_type')->get(['id as reference_id','referrence_type']);

    if (count($supplier_types) == true || count($references) == true) {
      return response([
        "code" => 200,
        "message" => "Supplier types and references has been fetched.",
        "result" => [
          "supplier_types" => $supplier_types,
          "references" => $references
        ]
      ]);
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }

  public function accountNumberDropdown()
  {
    $utility_locations = UtilityLocation::orderBy('location_name')->get(['id as location_id','location as location_name']);
    $utility_categories = UtilityCategory::orderBy('category_name')->get(['id as category_id','category as category_name']);
    $suppliers = Supplier::orderBy('supplier_name')->get(['id as supplier_id','supplier_name']);

    if (count($utility_locations) == true || count($utility_categories) == true || count($suppliers) == true) {
      return response([
        "code" => 200,
        "message" => "Location, category and supplier has been fetched.",
        "result" => [
          "locations" => $utility_locations,
          "categories" => $utility_categories,
          "suppliers" => $suppliers
        ]
      ]);
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }

  
  public function accountTitlesDropdown()
  {
    $account_titles = AccountTitle::orderBy('account_title')->get(['id as account_title_id','title as account_title']);

    if (count($account_titles) == true) {
      return response([
        "code" => 200,
        "message" => "Account titles has been fetched.",
        "result" => [
          "account_titles" => $account_titles
        ]
      ]);
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }

  // public function categoryPerDocument(){
  //   return "sample";
  // }
}
