<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    

    public function index(Request $request)
    {
      $status =  $request['status'];
      $rows =  (empty($request['rows']))?10:(int)$request['rows'];
      $search =  $request['search'];
      
      $locations = Location::withTrashed()
      ->with('Company')
      ->where(function ($query) use ($status){
        return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
      })->where(function ($query) use ($search) {
        $query->where('code', 'like', '%' . $search . '%')
        ->orWhere('location', 'like', '%' . $search . '%');
     })
      ->latest('updated_at')
      ->paginate($rows);
      
      if(count($locations)==true){
        return $this->resultResponse('fetch','Location',$locations);
      }
      return $this->resultResponse('not-found','Location',[]);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'code' => 'required',
            'location' => 'required',
            'company' => 'required'
        ]);

        $department_validateCodeDuplicate = Location::withTrashed()->where('code', $fields['code'])->first();
        if (!empty($department_validateCodeDuplicate)) {
          return $this->resultResponse('registered','Code',["error_field" => "code"]);
        }
        $department_validateDescriptionDuplicate = Location::withTrashed()->where('location', $fields['location'])->first();
        if (!empty($department_validateDescriptionDuplicate)) {
          return $this->resultResponse('registered','Description',["error_field" => "location"]);
        }
        $companyExist = $this->validateIfObjectExist(new Company,$fields['company'],'Company');
        if(!$companyExist){
            return $this->resultResponse('not-found','Company',[]);
        }
        $new_department = Location::create([
            'code' => $fields['code']
            , 'location' => $fields['location']
            , 'company' => $fields['company']
        ]);
        return $this->resultResponse('save','Location',$new_department);
    }

    public function update(Request $request, $id)
    {
        $specific_department = Location::find($id);

        $fields = $request->validate([
            'code' => 'required',
            'location' => 'required',
            'company' => 'required'
        ]);

        if (!$specific_department) {
            return $this->resultResponse('not-found','Location',[]);
        } else {
            $specific_department->code = $fields['code'];
            $specific_department->location = $fields['location'];
            $specific_department->company = $fields['company'];
            return $this->validateIfNothingChangeThenSave($specific_department,'Location');
        }
    }
    
    public function change_status(Request $request,$id){
            $status = $request['status'];
            $model = new Location();
            return $this->change_masterlist_status($status,$model,$id,'Location');
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
      $location_list = Location::withTrashed()->get();
      $company_list = Company::get();

      $headers = 'Code, Location, Company';
      $template = ["code","location","company"];
      $keys = array_keys(current($data));
      $this->validateHeader($template,$keys,$headers);
  
      foreach ($data as $location) {
            $code = $location['code'];
            $department_name= $location['location'];
            $company = $location['company'];
    
            foreach ($location as $key => $value) 
            {
            if (empty($value))
                $errorBag[] = (object) [
                "error_type" => "empty",
                "line" => $index,
                "description" => $key . " is empty."
                ];
            }

            if (!empty($code)) {
                $duplicatedepartmentCode = $location_list->filter(function ($location) use ($code){return strtolower($location['code']) == strtolower($code);});
                if ($duplicatedepartmentCode->count() > 0)
                $errorBag[] = (object) [
                    "error_type" => "existing",
                    "line" => $index,
                    "description" => $code . " is already registered."
                    ];
            }
            
            if (!empty($department_name)) {
                $duplicatedepartmentDepartment = $location_list->filter(function ($locations) use ($department_name){return strtolower($locations['location']) == strtolower($department_name);});
                if ($duplicatedepartmentDepartment->count() > 0)
                $errorBag[] = (object) [
                    "error_type" => "existing",
                    "line" => $index,
                    "description" => $department_name . " is already registered."
                    ];
            }

            if (!empty($company)) {
                $unregistercompany = $company_list->filter(function ($query) use ($company){return strtolower($query['company']) == strtolower($company);});
                if ($unregistercompany->count() == 0)
                    $errorBag[] = (object) [
                    "error_type" => "unregistered",
                    "line" => $index,
                    "description" => $company . " is not registered."
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
      
      $duplicate_department = array_values(array_diff($original_lines,array_keys($this->unique_multidim_array($data_validation_fields,'location'))));
      foreach($duplicate_department as $line){
        $input_name = $data_validation_fields[$line]['location'];
        $duplicate_data =  array_filter($data_validation_fields, function ($query) use($input_name){
          return ($query['location'] == $input_name);
        }); 
        $duplicate_lines =  implode(",",array_map(function($query){
          return $query+2;
        },array_keys($duplicate_data)));
        $firstDuplicateLine =  array_key_first($duplicate_data);
  
        if((empty($data_validation_fields[$line]['location']))){
  
        }else{
          $errorBag[] = [
            "error_type" => "duplicate",
            "line" => (string) $duplicate_lines,
            "description" =>  $data_validation_fields[$firstDuplicateLine]['location'].' location has a duplicate in your excel file.'
          ];
        }
      }
      $errorBag = array_values(array_unique($errorBag,SORT_REGULAR));
      if (empty($errorBag)) {
        foreach ($data as $location) {
          $fields = [
            'code' => $location['code'],
            'location' => $location['location'],
            'company' => Company::where('company',$location['company'])->first()->id,
            'created_at' => $date,
            'updated_at' => $date,
          ];
  
          $inputted_fields[] = $fields;
        }
        $count_upload = count($inputted_fields);
        $inputted_fields = collect($inputted_fields);
        $chunks = $inputted_fields->chunk(300);
  
        foreach ($chunks as $specific_chunk)
        {
          $new_department = DB::table('locations')->insert($specific_chunk->toArray());
        }
        return $this->resultResponse('import','location',$count_upload);
      }
      else
        return $this->resultResponse('import-error','location',$errorBag);
    }
}
