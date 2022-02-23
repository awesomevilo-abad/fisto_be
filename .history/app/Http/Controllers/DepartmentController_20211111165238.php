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
        $department = $request['department'];

        $fields = $request->validate([
            'code' => 'required|string|unique:departments,code',
            'is_active' => 'required',
            'department'=>'nullable',
            'company'=>'nullable'
        ]);

        $validate_department_company = DB::table('departments')
        ->where('department',$fields['department'])
        ->where('company',$fields['company'])->get();

        if(count($validate_department_company)>0){
            return $this->result(403,'Either department or department already exist',null);
        }

        $new_department = Department::create([
            'code' => $fields['code']
            , 'department' => $fields['department']
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
            'code' => 'nullable',
            'department' =>['unique:departments,department,'.$id],
            'company' => 'nullable',
        ]);

        if (!$specific_department) {
            return $this->result(404,'Data Not Found',null);
        }

        $specific_department->code = $fields['code'];
        $specific_department->department = $fields['department'];
        $specific_department->company = $fields['company'];
        $specific_department->save();

        $response = [
            "code" => 200,
            "message" => "Succefully Updated",
            "data" => $specific_department,
        ];
        return response($response);
    }

    public function archive(Request $request, $id)
    {
        $specific_department = Department::find($id);

        if(!$specific_department){

            return $this->result(404,"Data Not Found", $specific_department);
        }

            $specific_department->is_active = 0;
            $specific_department->save();

            return $this->result(200,"Succesfully Archieved",$specific_department);
    }

    public function restore(Request $request, $id)
    {
        $specific_department = Department::find($id);

        if(!$specific_department){

            return $this->result(404,"Data Not Found", $specific_department);
        }

            $specific_department->is_active = 1;
            $specific_department->save();

            return $this->result(200,"Succesfully Restored",$specific_department);
    }

    public function search(Request $request)
    {
        $value = $request['value'];

        if (isset($request['is_active'])) {
            if ($request['is_active'] == 'active') {
                $is_active = 1;
            } else {
                $is_active = 0;
            }
        } else {
            $is_active = 1;
        }

        $result = Department::where('code', 'like', '%' . $value . '%')
            ->where('is_active', $is_active)
            ->orWhere('department', 'like', '%' . $value . '%')
            ->orWhere('company', 'like', '%' . $value . '%')
            ->paginate(10);

        if ($result->isEmpty()) {
            return $this->result(404,"Data Not Found",null);
        }
        return $this->result(200,"Search Result",$result);
    }
}
