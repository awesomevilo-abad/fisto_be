<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Methods\GenericMethod;
use App\Models\OrganizationDepartment;
use App\Http\Requests\OrganizationDepartmentRequest;

class OrganizationDepartmentController extends Controller
{
    public function index(Request $request)
    {
        $status = $request['status'];
        $rows = $request['rows'];
        $search = $request['search'];

        $departments = OrganizationDepartment::
        when(!$status, function ($query) {
            $query->onlyTrashed();
        })
        ->when($search, function ($query) use ($search) {
            $query->where('name', 'LIKE', '%'.$search.'%')
            ->orWhere('code', 'LIKE', '%'.$search.'%');
        })
        ->paginate($rows);

        return count($departments) ?  GenericMethod::resultResponse('fetch', 'Department', $departments) :  GenericMethod::resultResponse('not-found', 'Department', []);
    }

    public function show(Request $request, $id)
    {
        $department = OrganizationDepartment::withTrashed()->select('id','code','name')->firstWhere('id',$id);
        return !empty($department) ?  GenericMethod::resultResponse('fetch', 'Department', $department) :  GenericMethod::resultResponse('not-found', 'Department', []);

    }

    public function import(OrganizationDepartmentRequest $request)
    {
        $organization = [];
        foreach($request->organization as $k=>$org){
            $deleted_at = $org['status'] === "active" ? NULL : date('Y-m-d H:i:s');
            $organization[] = ['code' => $org['id'], 'name' => $org['name'], 'deleted_at' => $deleted_at];
        }

        OrganizationDepartment::upsert($organization,['code'],['name','deleted_at']);
        return GenericMethod::resultResponse('save','Organization Department',[]);
    }

}
