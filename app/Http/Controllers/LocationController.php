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
      $paginate = (isset($request['paginate']))? $request['paginate']:$paginate = 1;
      $department_id =  $request['department_id'];
      
      $locations = Location::withTrashed()
        ->when($paginate === 1, function ($query) {
          return $query->with('departments');
        })
        ->where(function ($query) use ($status){
          if ($status == 1) return $query->whereNull('deleted_at');
          else return $query->whereNotNull('deleted_at');
        })
        ->where(function ($query) use ($search) {
          $query->where('code', 'like', '%' . $search . '%')
            ->orWhere('location', 'like', '%' . $search . '%')
            ->orWhereHas('departments', function($query) use ($search) {
              return $query->where('department', 'like', '%'.$search.'%');
            });
        })
        ->latest('updated_at');  

      if ($paginate == 1) {
        $locations = $locations->paginate($rows);
      }
      else {
        $locations = $locations->when(!empty($department_id), function($query) use ($department_id) {
          return $query->whereHas('departments', function($query) use ($department_id) {
            return $query->where('departments.id', $department_id);
          });
        })
        ->get(['id', 'location as name']);

        if(count($locations)) $locations = array('locations' => $locations);
      }
      
      if(count($locations)) return $this->resultResponse('fetch', 'Location', $locations);
      return $this->resultResponse('not-found', 'Location', []);
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
        $departmentExist = $this->validateIfObjectsExistByLocationStore(new Department,$fields['departments'],'Department');
        
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

        if (!$specific_location) {
          return $this->resultResponse('not-found','Location',[]);
        }
        
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
        
        $departmentExist = $this->validateIfObjectsExistByLocationStore(new Department,$fields['departments'],'Department');

        $is_associates_modified = $this->isTaggedArrayModified($fields['departments'],  $specific_location->departments()->get(),'id');
        $specific_location->code = $fields['code'];
        $specific_location->location = $fields['location'];
        $specific_location->departments()->sync(array_unique($fields['departments']));
        return $this->validateIfNothingChangeThenSave($specific_location,'Location',$is_associates_modified);
    }
    
    public function change_status(Request $request,$id){
            $status = $request['status'];
            $model = new Location();
            return $this->change_masterlist_status($status,$model,$id,'Location');
    }

    public function group_and_merge($raw_location_data){
      $locations = $raw_location_data->unique('location')->values();
      $department_list_raw =  $raw_location_data->pluck('department');
      $department_list = Department::all();

     $errorBag =  $this->validateIfObjectsExistByLocation(new Department,$department_list_raw,'Department');
      $department_per_locations =  $raw_location_data
      ->mapToGroups(function ($item, $key) use($department_list) {
        $dep = $item['department'];  
        return [
              $item['location'] =>$department_list->filter(function ($query) use ($dep){
                return (strtolower($query["department"]) == strtolower($dep)); 
              })->values()->first()['id']
        ];
      });

      $location_object = collect();
        foreach($locations as $k=>$v) {
          $location_code = $locations[$k]['code'];
          $location_name = $locations[$k]['location'];
          $departments =  $department_per_locations["$location_name"]->unique()->values();
          $location_object->push(['code' => $location_code, 'location' => $location_name,'department'=>$departments,'status'=> $locations[$k]['status']]);
        }

        return collect(["errorBag"=>$errorBag,"location"=>$location_object]);
    }

    public function import(Request $request)
    {
      $groupAndMergeResult= $this->group_and_merge(collect($request));
      $timezone = "Asia/Dhaka";
      date_default_timezone_set($timezone);
      $date = date("Y-m-d H:i:s", strtotime('now'));
      $errorBag = [];
      $data = $groupAndMergeResult['location'];
      $index = 2;
      $location_list = Location::withTrashed()->get();
      $department_list = Department::all();
      $headers = 'Code, Location, Department, Status';
      $template = ["code","location","department","status"];
      $keys = array_keys(current(current($data)));

      $errorBag = $groupAndMergeResult['errorBag'];
      $this->validateHeader($template,$keys,$headers);
  
      foreach ($data as $location) {
            $code = $location['code'];
            $location_name= $location['location'];
            $departments = $location['department'];
    
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
          
          $fields['departments']= $location['department'];
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
          $specific_chunk_to_insert = [];
          foreach($specific_chunk as $key=>$chunk){
            
            $specific_chunk_to_insert[$key]['code'] = $chunk['code'];
            $specific_chunk_to_insert[$key]['location'] = $chunk['location'];
            $specific_chunk_to_insert[$key]['created_at'] = $chunk['created_at'];
            $specific_chunk_to_insert[$key]['updated_at'] = $chunk['updated_at'];
            $specific_chunk_to_insert[$key]['deleted_at'] = $chunk['deleted_at'];
          }
          
          $new_location = DB::table('locations')->insert($specific_chunk_to_insert);
          foreach($specific_chunk->toArray() as $chunk){
            $location= Location::withTrashed()->where('code',$chunk['code'])->first();
            $location->departments()->attach($chunk['departments']);
          }
        }
        return $this->resultResponse('import','location',$count_upload,$active,$inactive,);
      }
      else
        return $this->resultResponse('import-error','location',$errorBag);

    }
}
