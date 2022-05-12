<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Company;
use App\Models\Department;
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
      ->with('departments')
      ->where(function ($query) use ($status){
        return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
      })->where(function ($query) use ($search) {
        $query->where('code', 'like', '%' . $search . '%');
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
            'departments' => 'required'
        ]);

        $location_validateCodeDuplicate = Location::withTrashed()->where('code', $fields['code'])->first();
        if (!empty($location_validateCodeDuplicate)) {
          return $this->resultResponse('registered','Code',["error_field" => "code"]);
        }
        $location_validateDescriptionDuplicate = Location::withTrashed()->where('location', $fields['location'])->first();
        if (!empty($location_validateDescriptionDuplicate)) {
          return $this->resultResponse('registered','Location',["error_field" => "location"]);
        }
        $departmentExist = $this->validateIfObjectsExist(new Department,$fields['departments'],'Department');
        
        $new_location = Location::create([
            'code' => $fields['code']
            , 'location' => $fields['location']
        ]);
        $new_location->departments()->attach($fields['departments']);
        return $this->resultResponse('save','Location',$new_location);
    }

    public function update(Request $request, $id)
    {
      
        $company =new Company();
        $specific_location = Location::find($id);

        $fields = $request->validate([
            'code' => 'required',
            'location' => 'required',
            'departments' => 'required'
        ]);

        $location_validateCodeDuplicate = Location::withTrashed()->where('code', $fields['code'])->where('id','<>',$id)->first();
        if (!empty($location_validateCodeDuplicate)) {
          return $this->resultResponse('registered','Code',["error_field" => "code"]);
        }
        $location_validateDescriptionDuplicate = Location::withTrashed()->where('location', $fields['location'])->where('id','<>',$id)->first();
        if (!empty($location_validateDescriptionDuplicate)) {
          return $this->resultResponse('registered','Location',["error_field" => "location"]);
        }
        
        $departmentExist = $this->validateIfObjectsExist(new Department,$fields['departments'],'Department');

        if (!$specific_location) {
            return $this->resultResponse('not-found','Location',[]);
        } else {
            $is_associates_modified = $this->isTaggedArrayModified($fields['departments'],  $specific_location->departments()->get(),'id');
            $specific_location->code = $fields['code'];
            $specific_location->location = $fields['location'];
            $specific_location->departments()->sync(array_unique($fields['departments']));
            return $this->validateIfNothingChangeThenSave($specific_location,'Location',$is_associates_modified);
        }
    }
    
    public function change_status(Request $request,$id){
            $status = $request['status'];
            $model = new Location();
            return $this->change_masterlist_status($status,$model,$id,'Location');
    }

    public function import(Request $request)
    {
      $location_ibject = collect();
      $locations = collect($request)->unique('location')->values();
      $timezone = "Asia/Dhaka";
      date_default_timezone_set($timezone);
      $date = date("Y-m-d H:i:s", strtotime('now'));
      $errorBag = [];
      $data = $request->all();
      $data_validation_fields = $request->all();
      $index = 2;
      $location_list = Location::withTrashed()->get();
      $department_list = Department::all();
      $headers = 'Code, Location, Department, Status';
      $template = ["code","location","department","status"];
      $keys = array_keys(current($data));

      $department_per_locations =  collect($request)->pluck('department');
      $departmentExist = $this->validateIfObjectsExistByLocation(new Department,$department_per_locations,'Department');

       $department_per_locations =  collect($request)
      ->mapToGroups(function ($item, $key) use($department_list) {
          return [$item['location'] => $department_list->where('department',$item['department'])->values()->first()['id']];
        });

          $location_object = collect();
            foreach($locations as $k=>$v) {
              $location_code = $locations[$k]['code'];
              $location_name = $locations[$k]['location'];
              $departments =  $department_per_locations["$location_name"];
              $location_object->push(['code' => $location_code, 'name' => $location_name,'departments'=>$departments]);
            }
            return $location_object;
      $this->validateHeader($template,$keys,$headers);
  
      foreach ($data as $location) {
            $code = $location['code'];
            $location_name= $location['location'];
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
                $duplicatelocationCode = $this->getDuplicateInputs($location_list,$code,'code');
                if ($duplicatelocationCode->count() > 0)
                $errorBag[] = (object) [
                    "error_type" => "existing",
                    "line" => $index,
                    "description" => $code . " is already registered."
                    ];
            }
            
            if (!empty($location_name)) {
                $duplicatelocationLocation = $this->getDuplicateInputs($location_list,$location_name,'location');
                if ($duplicatelocationLocation->count() > 0)
                $errorBag[] = (object) [
                    "error_type" => "existing",
                    "line" => $index,
                    "description" => $location_name . " is already registered."
                    ];
            }

            $index++;
      }
  
  
      $original_lines = array_keys($data_validation_fields);
      $duplicate_code = array_values(array_diff($original_lines,array_keys($this->unique_multidim_array($data_validation_fields,'code'))));
  
      foreach($duplicate_code as $line){
        $input_code = $data_validation_fields[$line]['code'];
        
        $duplicate_data =  array_filter($data_validation_fields, function ($query) use($input_code){
          return strtolower((string)$query["code"]) === strtolower((string)$input_code);
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
      
      $duplicate_location = array_values(array_diff($original_lines,array_keys($this->unique_multidim_array($data_validation_fields,'location'))));
      foreach($duplicate_location as $line){
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
          $status_date = (strtolower($location['status'])=="active"?NULL:$date);
          $fields = [
            'code' => $location['code'],
            'location' => $location['location'],
            'created_at' => $date,
            'updated_at' => $date,
            'deleted_at' => $status_date,
          ];
  
          $inputted_fields[] = $fields;
        }
        $count_upload = count($inputted_fields);
        $inputted_fields = collect($inputted_fields);
        $chunks = $inputted_fields->chunk(300);

        $active =  $inputted_fields->filter(function ($q){
          return $q['deleted_at']==NULL;
        })->count();

        $inactive =  $inputted_fields->filter(function ($q){
          return $q['deleted_at']!=NULL;
        })->count();
  
        foreach ($chunks as $specific_chunk)
        {
          $new_location = DB::table('locations')->insert($specific_chunk->toArray());
        }
        return $this->resultResponse('import','location',$count_upload,$active,$inactive,);
      }
      else
        return $this->resultResponse('import-error','location',$errorBag);
    }
}
