<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;

use App\Models\Supplier;
use App\Models\Referrence;
use App\Models\SupplierType;
use App\Methods\GenericMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
  public function index(Request $request)
  {
    $status =  $request['status'];
    $rows =  (empty($request['rows']))?10:(int)$request['rows'];
    $search =  $request['search'];

    $suppliers = Supplier::withTrashed()
      ->with('referrences')
      ->with('supplier_types')
      ->where(function ($query) use ($status) {
        if ($status == true) $query->whereNull('suppliers.deleted_at');
        else  $query->whereNotNull('suppliers.deleted_at');
      })
      ->where(function ($query) use ($search) {
        $query->where('suppliers.code', 'like', '%'.$search.'%')
          ->orWhere('suppliers.name', 'like', '%'.$search.'%')
          ->orWhere('suppliers.terms', 'like', '%'.$search.'%');
      })
      ->latest('suppliers.updated_at')
      ->paginate($rows);

    if (count($suppliers) == true) {
      return $this->result(200,"Suppliers has been fetched",$suppliers);
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }
  public function show($id)
  {
    $supplier = Supplier::with('referrences')
      ->where('suppliers.id', $id)
      ->join('supplier_types', 'suppliers.supplier_type_id', 'supplier_types.id')
      ->select([
        'suppliers.id',
        'suppliers.code',
        'suppliers.name',
        'suppliers.terms',
        'suppliers.supplier_type_id',
        'supplier_types.type as supplier_type',
        'suppliers.updated_at',
        'suppliers.deleted_at'
      ])
      ->get();

    if (!empty($supplier)) {
      return $this->result(200,"Supplier has been fetched",$supplier);
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }
  public function store(Request $request)
  {
    $fields = $request->validate([
      'code' => ['required','string'],
      'name' => ['required','string'],
      'terms' => ['required','string'],
      'supplier_type_id' => ['required','numeric'],
      'references' => ['required','array']
    ]);

    $supplier_validateDuplicateCode = Supplier::withTrashed()->firstWhere('code', $fields['code']);

    if (!empty($supplier_validateDuplicateCode))
      throw new FistoException("Supplier code already registered.", 409, NULL, [
        "error_field" => "code"
      ]);

    $supplier_validateDuplicateName = Supplier::withTrashed()->firstWhere('name', $fields['name']);

    if (!empty($supplier_validateDuplicateName))
      throw new FistoException("Supplier name already registered.", 409, NULL, [
        "error_field" => "name"
      ]);

    $new_supplier = Supplier::create($fields);
    $new_supplier->referrences()
      ->attach($fields['references']);

      return $this->result(200,"Supplier has been saved.",$new_supplier);
  }
  public function update(Request $request, $id)
  {
    $supplier = Supplier::withTrashed()->find($id);

    $fields = $request->validate([
      'code' => ['required','string'],
      'name' => ['required','string'],
      'terms' => ['required','string'],
      'supplier_type_id' => ['required','numeric'],
      'references' => ['required','array']
    ]);

    if (!empty($supplier)) {
      $supplier_validateDuplicateCode = Supplier::withTrashed()->firstWhere([['id', '<>', $id],['code', $fields['code']]]);

      if (!empty($supplier_validateDuplicateCode))
        throw new FistoException("Supplier code already registered.", 409, NULL, [
          "error_field" => "code"
        ]);

      $supplier_validateDuplicateName = Supplier::withTrashed()->firstWhere([['id', '<>', $id],['name', $fields['name']]]);

      if (!empty($supplier_validateDuplicateName))
        throw new FistoException("Supplier name already registered.", 409, NULL, [
          "error_field" => "name"
        ]);

      $supplier->code = $fields['code'];
      $supplier->name = $fields['name'];
      $supplier->terms = $fields['terms'];
      $supplier->supplier_type_id = $fields['supplier_type_id'];
      $supplier->referrences()->detach();
      $supplier->referrences()->attach(array_unique($fields['references']));
      return $this->validateIfNothingChangeThenSave($supplier,'Supplier');
    }
    else
      throw new FistoException("No records found.", 404, NULL, []);
  }
  public function import(Request $request)
  {
    $timezone = "Asia/Dhaka";
    date_default_timezone_set($timezone);

    $date = date("Y-m-d H:i:s", strtotime('now'));
    $errorBag = [];
    $data = $request->all();
    $data_validation_fields = $request->all();
    $index = 2;
    $supplier_type_list = SupplierType::withTrashed()->get();
    $referrence_list = Referrence::withTrashed()->get();
    $supplier_list = Supplier::withTrashed()->get();
    $supplier_type_list_no_trash = SupplierType::get();
    $referrence_list_no_trash = Referrence::get();
    
    $template = [
      "code",
      "name",
      "terms",
      "supplier_type",
      "referrences"
    ];
    $keys = array_keys(current($data));

    if (count(array_diff($template, $keys)))
      throw new FistoException("Invalid excel template, it should be Supplier Code, Supplier Name, Terms, Supplier Type, Referrences", 406, NULL, []);

    foreach ($data as $supplier) {
      $code = $supplier['code'];
      $name = $supplier['name'];
      $supplier_type = $supplier['supplier_type'];
      $supplier_references = $supplier['referrences'];

          foreach ($supplier as $key => $value) {
              if (empty($value))
                  $errorBag[] = (object) [
                  "error_type" => "empty",
                  "line" => $index,
                  "description" => $key . " is empty."
                  ];
          }
          if (!empty($supplier_type)) {
              $unregisterSupplierType = $supplier_type_list->filter(function ($supplier) use ($supplier_type){return strtolower($supplier['type']) == strtolower($supplier_type);});
              if ($unregisterSupplierType->count() == 0)
                  $errorBag[] = (object) [
                  "error_type" => "unregistered",
                  "line" => $index,
                  "description" => $supplier_type . " is not registered."
                  ];
          }
          if (!empty($supplier_references)) {
              foreach (explode(",", $supplier_references) as $reference_type) {
                  $unregisterSupplierReference = $referrence_list->filter(function ($referrence) use ($reference_type){return strtolower($referrence['type']) == strtolower($reference_type);});
                  if ($unregisterSupplierReference->count() == 0)
                  $errorBag[] = (object) [
                      "error_type" => "unregistered",
                      "line" => $index,
                      "description" => $reference_type . " is not registered."
                  ];
              }
          }
          if (!empty($code)) {
              $duplicateSupplierCode = $supplier_list->filter(function ($supplier) use ($code){return strtolower($supplier['code']) == strtolower($code);});
              if ($duplicateSupplierCode->count() > 0)
              $errorBag[] = (object) [
                  "error_type" => "existing",
                  "line" => $index,
                  "description" => $code . " is already registered."
                  ];
          }
          if (!empty($name)) {
              $duplicateSupplierName =$supplier_list->filter(function ($supplier) use ($name){return strtolower($supplier['name']) == strtolower($name);});
              if ($duplicateSupplierName->count() > 0)
              $errorBag[] = (object) [
                  "error_type" => "existing",
                  "line" => $index,
                  "description" => $name . " is already registered."
                  ];
          }
          $index++;
    }


    $original_lines = array_keys($data_validation_fields);
    
    $duplicate_code = array_values(array_diff($original_lines,array_keys($this->unique_multidim_array($data_validation_fields,'code'))));

    foreach($duplicate_code as $line){
      $input_code = $data_validation_fields[$line]['code'];
      $duplicate_data =  array_filter($data_validation_fields, function ($query) use($input_code){
        return ($query['code'] == $input_code);
      }); 
      $duplicate_lines =  implode(",",array_map(function($query){
        return $query+2;
      },array_keys($duplicate_data)));
      $firstDuplicateLine =  array_key_first($duplicate_data);

      if((empty($data_validation_fields[$line]['code']))){

      }else{
        $errorBag[] = [
          "error_type" => "duplicate",
          "line" => (string) $duplicate_lines,
          "description" =>  $data_validation_fields[$firstDuplicateLine]['code'].' code has a duplicate in your excel file.'
        ];
      }
    }
    
    $duplicate_name = array_values(array_diff($original_lines,array_keys($this->unique_multidim_array($data_validation_fields,'name'))));

    foreach($duplicate_name as $line){
      $input_name = $data_validation_fields[$line]['name'];
      $duplicate_data =  array_filter($data_validation_fields, function ($query) use($input_name){
        return ($query['name'] == $input_name);
      }); 
      $duplicate_lines =  implode(",",array_map(function($query){
        return $query+2;
      },array_keys($duplicate_data)));
      $firstDuplicateLine =  array_key_first($duplicate_data);

      if((empty($data_validation_fields[$line]['code']))){

      }else{
        $errorBag[] = [
          "error_type" => "duplicate",
          "line" => (string) $duplicate_lines,
          "description" =>  $data_validation_fields[$firstDuplicateLine]['code'].' code has a duplicate in your excel file.'
        ];
      }
    }

    if (empty($errorBag)) {
      foreach ($data as $supplier) {
          $supplier_type = $supplier['supplier_type'];
        $fields = [
          'code' => $supplier['code'],
          'name' => $supplier['name'],
          'terms' => $supplier['terms'],
          'supplier_type_id' => SupplierType::where('type',$supplier_type)->first()->id,
          'created_at' => $date,
          'updated_at' => $date,
        ];

        $inputted_fields[] = $fields;
        $references = explode(",", $supplier['referrences']);
        $references_ids = Referrence::whereIn('type', $references)->pluck('id');
      }
      $inputted_fields = collect($inputted_fields);
      $chunks = $inputted_fields->chunk(1000);

      foreach ($chunks as $specific_chunk)
      {
        $new_supplier = DB::table('suppliers')->insert($specific_chunk->toArray());
        foreach($specific_chunk->toArray() as $chunk){
          $supplier= Supplier::where('code',$chunk)->first();
          $supplier->referrences()->attach($references_ids);
        }
      }
      return $this->result(201,"Suppliers has been imported",[]);
    }
    else
      throw new FistoException("No supplier were imported. Please correct the errors in the excel file.", 409, NULL, $errorBag);
  }
  public function change_status(Request $request,$id)
  {
    $status = $request['status'];
    $model = new Supplier();
    return $this->change_masterlist_status($status,$model,$id,'Supplier');
  }
}
