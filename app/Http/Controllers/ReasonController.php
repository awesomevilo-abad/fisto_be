<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;

use App\Models\Reason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReasonController extends Controller
{
    public function index(Request $request)
    {
      $status =  $request['status'];
      $rows =  (empty($request['rows']))?10:(int)$request['rows'];
      $search =  $request['search'];
      $paginate = (isset($request['paginate']))? $request['paginate']:$paginate = 1;
      
      $reasons = Reason::withTrashed()
      ->where(function ($query) use ($status){
        return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
      })
      ->where(function ($query) use ($search) {
        $query->where('reason', 'like', '%'.$search.'%')
          ->orWhere('remarks', 'like', '%'.$search.'%');
      })
      ->latest('updated_at');
      if ($paginate == 1){
        $reasons = $reasons
        ->paginate($rows);
      }else if ($paginate == 0){
        $reasons = $reasons
        ->get(['id','reason as description']);
        if(count($reasons)==true){
            $reasons = array("reasons"=>$reasons);
        }
      }
      
      if(count($reasons)==true){
        return $this->resultResponse('fetch','Reason',$reasons);
      }
      return $this->resultResponse('not-found','Reason',[]);
    }

    public function store(Request $request)
    {
      $fields = $request->validate([
        'reason' => ['required','string'],
        'remarks' => ['required','string']
      ]);

      $reason_validateDuplicate = DB::table('reasons')
        ->where('reason', $fields['reason'])
        ->get();
      
      if (count($reason_validateDuplicate) === 0) {
        $new_reason = Reason::create($fields);
        return $this->resultResponse('save','Reason',$new_reason);
      }
      else
      return $this->resultResponse('registered','Reason',[]);
    }
    
    public function update(Request $request, $id)
    {
      $reason = Reason::find($id);

      $fields = $request->validate([
        'reason' => ['required','string'],
        'remarks' => ['required','string']
      ]);

      if (!empty($reason)) {
        $reason_validateDuplicate = DB::table('reasons')
          ->where('id', '!=', $id)
          ->where('reason', $fields['reason'])
          ->get();

        if (count($reason_validateDuplicate) === 0) {
          $reason->reason = $fields['reason'];
          $reason->remarks = $fields['remarks'];
          return $this->validateIfNothingChangeThenSave($reason,'Reason');
        }
        else
           return $this->resultResponse('registered','Reason',[]);
      }
      else
         return $this->resultResponse('not-found','Reason',[]);
    }

    public function change_status(Request $request,$id){
        $status = $request['status'];
        $model = new Reason();
        return $this->change_masterlist_status($status,$model,$id,'Reason');
    }
    
}
