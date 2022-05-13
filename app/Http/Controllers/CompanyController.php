<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Exceptions\FistoException;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
      $status =  $request['status'];
      $rows =  (empty($request['rows']))?10:(int)$request['rows'];
      $search =  $request['search'];
      $paginate = (isset($request['paginate']))? $request['paginate']:$paginate = 1;
      
      $companies = Company::withTrashed()
      ->with('associates')
      ->where(function ($query) use ($status){
        return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
      })->where(function ($query) use ($search) {
        $query->where('code', 'like', '%' . $search . '%')
        ->orWhere('company', 'like', '%' . $search . '%');
     })
     ->latest('updated_at');    
     if ($paginate == 1){
       $companies = $companies
       ->paginate($rows);
     }else if ($paginate == 0){
       $companies = $companies
       ->without('associates')
       ->get(['id','company as name']);
       if(count($companies)==true){
           $companies = array("companies"=>$companies);;
       }
     }
      
      if(count($companies)==true){
        return $this->resultResponse('fetch','Company',$companies);
      }
      return $this->resultResponse('not-found','Company',[]);
    }

    public function store(Request $request)
    {
        // $user_list = User::get();
        $fields = $request->validate([
            'code' => 'required',
            'company' => 'required',
            'associates' => 'required',
        ]);

        $company_validateCodeDuplicate = Company::withTrashed()->where('code', $fields['code'])->first();
        if (!empty($company_validateCodeDuplicate)) {
          return $this->resultResponse('registered','Code',["error_field" => "code"]);
        }

        $company_validateDescriptionDuplicate = Company::withTrashed()->where('company', $fields['company'])->first();
        if (!empty($company_validateDescriptionDuplicate)) {
          return $this->resultResponse('registered','Company',["error_field" => "company"]);
        }

        $apExist = $this->validateIfObjectsExist(new User,$fields['associates'],'AP Associate');
        if($apExist){
            return $this->resultResponse('not-registered','AP Associate',[]);
        }
        $new_company = Company::create([
            'code' => $fields['code']
            , 'company' => $fields['company']
        ]);
        $new_company->associates()->attach($fields['associates']);

        return $this->resultResponse('save','Company',$new_company);

    }

    public function update(Request $request, $id)
    {
        $user =new User();
        $specific_company = Company::withTrashed()->find($id);
        $fields = $request->validate([
            'code' => ['required'],
            'company' => ['required'],
            'associates' => ['required'],
        ]);


        $company_validateCodeDuplicate = Company::withTrashed()->where('code', $fields['code'])->where('id','<>',$id)->first();
        if (!empty($company_validateCodeDuplicate)) {
          return $this->resultResponse('registered','Code',["error_field" => "code"]);
        }

        $company_validateDescriptionDuplicate = Company::withTrashed()->where('company', $fields['company'])->where('id','<>',$id)->first();
        if (!empty($company_validateDescriptionDuplicate)) {
          return $this->resultResponse('registered','Company',["error_field" => "company"]);
        }

        $apExist = $this->validateIfObjectsExist($user,$fields['associates'],'AP Associate');
        if($apExist){
            return $this->resultResponse('not-registered','AP Associate',[]);
        }
        
        if (!$specific_company) {
            return $this->resultResponse('not-found','Company',[]);
        } else {

            $specific_company->associates()->get();
            $is_associates_modified = $this->isTaggedArrayModified($fields['associates'],  $specific_company->associates()->get(),'id');
      
            $specific_company->code = $fields['code'];
            $specific_company->company = $fields['company'];
            $specific_company->associates()->detach();
            $specific_company->associates()->attach(array_unique($fields['associates']));
            return $this->validateIfNothingChangeThenSave($specific_company,'Company',$is_associates_modified);
            
        }
    }
    
    public function change_status(Request $request,$id){
            $status = $request['status'];
            $model = new Company();
            return $this->change_masterlist_status($status,$model,$id,'Company');
    }
}
