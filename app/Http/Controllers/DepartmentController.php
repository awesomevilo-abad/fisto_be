<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{

    public function index(Request $request)
    {
      $status =  $request['status'];
      $rows =  (empty($request['rows']))?10:(int)$request['rows'];
      $search =  $request['search'];
      
      $departments = Department::withTrashed()
      ->with('Company')
      ->where(function ($query) use ($status){
        return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
      })->where(function ($query) use ($search) {
        $query->where('code', 'like', '%' . $search . '%')
        ->orWhere('department', 'like', '%' . $search . '%');
     })
      ->latest('updated_at')
      ->paginate($rows);
      
      if(count($departments)==true){
        return $this->resultResponse('fetch','Department',$departments);
      }
      return $this->resultResponse('not-found','Department',[]);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'code' => 'required',
            'department' => 'required',
            'company' => 'required'
        ]);

        $department_validateCodeDuplicate = Department::withTrashed()->where('code', $fields['code'])->first();
        if (!empty($department_validateCodeDuplicate)) {
          return $this->resultResponse('registered','Code',["error_field" => "code"]);
        }
        $department_validateDescriptionDuplicate = Department::withTrashed()->where('department', $fields['department'])->first();
        if (!empty($department_validateDescriptionDuplicate)) {
          return $this->resultResponse('registered','Department',["error_field" => "department"]);
        }
        $companyExist = $this->validateIfObjectExist(new Company,$fields['company'],'Company');
        if(!$companyExist){
            return $this->resultResponse('not-found','Company',[]);
        }
        $new_department = Department::create([
            'code' => $fields['code']
            , 'department' => $fields['department']
            , 'company' => $fields['company']
        ]);
        return $this->resultResponse('save','Department',$new_department);
    }

    public function update(Request $request, $id)
    {
        $specific_department = Department::find($id);

        $fields = $request->validate([
            'code' => 'required',
            'department' => 'required',
            'company' => 'required'
        ]);

        $department_validateCodeDuplicate = Department::withTrashed()->where('code', $fields['code'])->where('id','<>',$id)->first();
        if (!empty($department_validateCodeDuplicate)) {
          return $this->resultResponse('registered','Code',["error_field" => "code"]);
        }
        $department_validateDescriptionDuplicate = Department::withTrashed()->where('department', $fields['department'])->where('id','<>',$id)->first();
        if (!empty($department_validateDescriptionDuplicate)) {
          return $this->resultResponse('registered','Department',["error_field" => "department"]);
        }
        $companyExist = DB::table('departments')->where('company','=',$fields['company'])->first();
        if(!$companyExist){
            return $this->resultResponse('not-registered','Company',[]);
        }

        if (!$specific_department) {
            return $this->resultResponse('not-found','Department',[]);
        } else {
            $specific_department->code = $fields['code'];
            $specific_department->department = $fields['department'];
            $specific_department->company = $fields['company'];
            return $this->validateIfNothingChangeThenSave($specific_department,'Department');
        }
    }
    
    public function change_status(Request $request,$id){
            $status = $request['status'];
            $model = new Department();
            return $this->change_masterlist_status($status,$model,$id,'Department');
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
      $department_list = Department::withTrashed()->get();
      $company_list = Company::get();

      $headers = 'Code, Department, Company, Status';
      $template = ["code","department","company","status"];
      $keys = array_keys(current($data));
      $this->validateHeader($template,$keys,$headers);
  
      foreach ($data as $department) {
            $code = $department['code'];
            $department_name= $department['department'];
            $company = $department['company'];
    
            foreach ($department as $key => $value) 
            {
            if (empty($value))
                $errorBag[] = (object) [
                "error_type" => "empty",
                "line" => $index,
                "description" => $key . " is empty."
                ];
            }

            if (!empty($code)) {
                $duplicatedepartmentCode = $this->getDuplicateInputs($department_list,$code,'code');
                if ($duplicatedepartmentCode->count() > 0)
                $errorBag[] = (object) [
                    "error_type" => "existing",
                    "line" => $index,
                    "description" => $code . " is already registered."
                    ];
            }
            
            if (!empty($department_name)) {
               $duplicatedepartmentDepartment = $this->getDuplicateInputs($department_list,$department_name,'department');
                if ($duplicatedepartmentDepartment->count() > 0)
                $errorBag[] = (object) [
                    "error_type" => "existing",
                    "line" => $index,
                    "description" => $department_name . " is already registered."
                    ];
            }

            if (!empty($company)) {
               $unregistercompany = $this->getDuplicateInputs($company_list,$company,'company');
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
      
      $duplicate_department = array_values(array_diff($original_lines,array_keys($this->unique_multidim_array($data_validation_fields,'department'))));
      foreach($duplicate_department as $line){
        $input_name = $data_validation_fields[$line]['department'];
        $duplicate_data =  array_filter($data_validation_fields, function ($query) use($input_name){
          return ($query['department'] == $input_name);
        }); 
        $duplicate_lines =  implode(",",array_map(function($query){
          return $query+2;
        },array_keys($duplicate_data)));
        $firstDuplicateLine =  array_key_first($duplicate_data);
  
        if((empty($data_validation_fields[$line]['department']))){
  
        }else{
          $errorBag[] = [
            "error_type" => "duplicate",
            "line" => (string) $duplicate_lines,
            "description" =>  $data_validation_fields[$firstDuplicateLine]['department'].' department has a duplicate in your excel file.'
          ];
        }
      }
      $errorBag = array_values(array_unique($errorBag,SORT_REGULAR));
      if (empty($errorBag)) {
        foreach ($data as $department) {
          $status_date = (strtolower($department['status'])=="active"?NULL:$date);
          $fields = [
            'code' => $department['code'],
            'department' => $department['department'],
            'company' => Company::where('company',$department['company'])->first()->id,
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
          $new_department = DB::table('departments')->insert($specific_chunk->toArray());
        }
        return $this->resultResponse('import','department',$count_upload,$active,$inactive);
      }
      else
        return $this->resultResponse('import-error','department',$errorBag);
    }
}
