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
    $paginate = (isset($request['paginate']))? $request['paginate']:$paginate = 1;

    $suppliers = Supplier::withTrashed()
      ->with('references')
      ->with('supplier_type')
      ->where(function ($query) use ($status) {
        if ($status == true) $query->whereNull('suppliers.deleted_at');
        else  $query->whereNotNull('suppliers.deleted_at');
      })
      ->where(function ($query) use ($search) {
        $query->where('suppliers.code', 'like', '%'.$search.'%')
          ->orWhere('suppliers.name', 'like', '%'.$search.'%')
          ->orWhere('suppliers.terms', 'like', '%'.$search.'%');
      })
      ->latest('suppliers.updated_at');

      if ($paginate == 1){
          $suppliers = $suppliers
          ->paginate($rows);
      }else if ($paginate == 0){
          $suppliers = $suppliers
          ->with(['references'=> function($q){
                $q->select('referrences.id');
            }])
          ->without('supplier_type')
          ->get(['id','name']);
          $suppliers = array("suppliers"=>$suppliers);
      }

      if(count($suppliers)==true){
          return $this->resultResponse('fetch','Supplier',$suppliers);
      }
      
      return $this->resultResponse('not-found','Supplier',[]);
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
      return $this->resultResponse('registered','Code',["error_field" => "code"]);

    $supplier_validateDuplicateName = Supplier::withTrashed()->firstWhere('name', $fields['name']);

    if (!empty($supplier_validateDuplicateName))
      return $this->resultResponse('registered','Name',["error_field" => "name"]);

    $new_supplier = Supplier::create($fields);
    $new_supplier->referrences()
      ->attach($fields['references']);
    return $this->resultResponse('save','Supplier',$new_supplier);
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
      return $this->resultResponse('registered','Code',["error_field" => "code"]);

      $supplier_validateDuplicateName = Supplier::withTrashed()->firstWhere([['id', '<>', $id],['name', $fields['name']]]);

      if (!empty($supplier_validateDuplicateName))
      return $this->resultResponse('registered','Name',["error_field" => "name"]);
      
      $is_reference_modified = $this->isTaggedArrayModified($fields['references'],  $supplier->references()->get(),'id');
      
      $supplier->code = $fields['code'];
      $supplier->name = $fields['name'];
      $supplier->terms = $fields['terms'];
      $supplier->supplier_type_id = $fields['supplier_type_id'];
      $supplier->references()->detach();
      $supplier->references()->attach(array_unique($fields['references']));
      return $this->validateIfNothingChangeThenSave($supplier,'Supplier',$is_reference_modified);
    }
    else
      return $this->resultResponse('not-found','Supplier',[]);
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
    
    $headers = 'Supplier Code, Supplier Name, Terms, Supplier Type, Referrences, Status';
    $template = ["code","name","terms","supplier_type","referrences", "status"];
    $keys = array_keys(current($data));
    $this->validateHeader($template,$keys,$headers);

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
            $unregisterSupplierType = $this->getDuplicateInputs($supplier_type_list,$supplier_type,'type');
              if ($unregisterSupplierType->count() == 0)
                  $errorBag[] = (object) [
                  "error_type" => "unregistered",
                  "line" => $index,
                  "description" => $supplier_type . " is not registered."
                  ];
          }
          if (!empty($supplier_references)) {
              foreach (explode(",", $supplier_references) as $reference_type) {
                  $unregisterSupplierReference = $this->getDuplicateInputs($referrence_list,$reference_type,'type');
                  if ($unregisterSupplierReference->count() == 0)
                  $errorBag[] = (object) [
                      "error_type" => "unregistered",
                      "line" => $index,
                      "description" => $reference_type . " is not registered."
                  ];
              }
          }
          if (!empty($code)) {
              $duplicateSupplierCode = $this->getDuplicateInputs($supplier_list,$code,'code');
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

      if((empty($data_validation_fields[$line]['name']))){

      }else{
        $errorBag[] = [
          "error_type" => "duplicate",
          "line" => (string) $duplicate_lines,
          "description" =>  $data_validation_fields[$firstDuplicateLine]['name'].' name has a duplicate in your excel file.'
        ];
      }
    }
    $errorBag = array_values(array_unique($errorBag,SORT_REGULAR));

    if (empty($errorBag)) {
      foreach ($data as $supplier) {
          $status_date = (strtolower($supplier['status'])=="active"?NULL:$date);
          $supplier_type = $supplier['supplier_type'];
        $fields = [
          'code' => $supplier['code'],
          'name' => $supplier['name'],
          'terms' => $supplier['terms'],
          'supplier_type_id' => SupplierType::where('type',$supplier_type)->first()->id,
          'created_at' => $date,
          'updated_at' => $date,
          'deleted_at' => $status_date,
        ];

        $references = explode(",", $supplier['referrences']);
        $references_ids = Referrence::whereIn('type', $references)->pluck('id');
        $fields['references_ids']= $references_ids;
        $inputted_fields[] = $fields;
      }


      $inputted_fields = collect($inputted_fields);
      $chunks = $inputted_fields->chunk(1000);
      $count_upload = count($inputted_fields);

      $active =  $inputted_fields->filter(function ($q){
        return $q['deleted_at']==NULL;
      })->count();

      $inactive =  $inputted_fields->filter(function ($q){
        return $q['deleted_at']!=NULL;
      })->count();
      
      foreach ($chunks as $specific_chunk)
      {
        $specific_chunk_to_insert = [];
        foreach($specific_chunk as $key=>$chunk){
          
          $specific_chunk_to_insert[$key]['code'] = $chunk['code'];
          $specific_chunk_to_insert[$key]['name'] = $chunk['name'];
          $specific_chunk_to_insert[$key]['terms'] = $chunk['terms'];
          $specific_chunk_to_insert[$key]['supplier_type_id'] = $chunk['supplier_type_id'];
          $specific_chunk_to_insert[$key]['created_at'] = $chunk['created_at'];
          $specific_chunk_to_insert[$key]['updated_at'] = $chunk['updated_at'];
          $specific_chunk_to_insert[$key]['deleted_at'] = $chunk['deleted_at'];
        }

        $new_supplier = DB::table('suppliers')->insert($specific_chunk_to_insert);
        foreach($specific_chunk->toArray() as $chunk){
       
          $supplier= Supplier::withTrashed()->where('code',$chunk)->first();
          $supplier->references()->attach($chunk['references_ids']);
        }
      }
      return $this->resultResponse('import','Supplier',$count_upload,$active,$inactive);
    }
    else
      return $this->resultResponse('import-error','Supplier',$errorBag);
  }
  public function change_status(Request $request,$id)
  {
    $status = $request['status'];
    $model = new Supplier();
    return $this->change_masterlist_status($status,$model,$id,'Supplier');
  }
}
