<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{

    public function index(Request $request){
        $is_active = $request->get('is_active');

        if ($is_active == 'true') {
            $department = DB::table('departments')
                ->where('is_active', '=', 1)
                ->latest()
                ->paginate(10);

        } elseif ($is_active == 'false') {
            $department = DB::table('departments')
                ->where('is_active', '=', 0)
                ->latest()
                ->paginate(10);

        } else {
            $department = DB::table('departments')
                ->latest()
                ->paginate(10);
        }

        $code = 200;
        $message = "Succefully Retrieved";
        $data = $department;

        if (!$department || $department->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = $department;
        }

        return $this->result($code,$message,$data);
    }

    public function store(Request $request){
        $department = $request['department'];
        $company = $request['department'];

        $messages = [
            "department.unique" => "Department and company are not unique"
        ];

        $fields = $request->validate([
            'code' => 'required|string|unique:departments,code',
            'is_active' => 'required',
            'department'=>'nullable',
            'company'=>'nullable'
        ]);

        $validate_department_company = DB::table('departments')::where('department',$fields['department'])
        ->where('company',$fields['company'])->get();

        return $validate_department_company;

        if(count($validate_department_company)>0){
            return $this->result(403,'Either department or company already exist',null);
        }

        $new_department = Department::create([
            'code' => $fields['code']
            , 'department' => $fields['department']
            , 'is_active' => $fields['is_active'],
        ]);

        return $this->result(200,'Succefully Created',$new_department);
    }

    public function show($id)
    {
        $result = Department::find($id);

        $code = 200;
        $message = "Succefully Retrieved";
        $data = $department;

        if (!$department || $department->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = $department;
        }

        return $this->result($code,$message,$data);
    }

    public function update(Request $request, $id)
    {
        $specific_department = Department::find($id);

        $fields = $request->validate([
            'code' => ['unique:departments,code,' . $id],
            'department' => ['unique:departments,department,' . $id],

        ]);

        if (!$specific_company) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $specific_company,
            ];
        } else {

            $specific_company->company_code = $request->get('company_code');
            $specific_company->company_description = $request->get('company_description');
            $specific_company->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Updated",
                "data" => $specific_company,
            ];

        }
        return response($response);
    }
}
