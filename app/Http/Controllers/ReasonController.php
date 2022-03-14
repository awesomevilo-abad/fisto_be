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
      
      $reasons = Reason::withTrashed()
      ->where(function ($query) use ($status){
        return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
      })
      ->where(function ($query) use ($search) {
        $query->where('reason', 'like', '%'.$search.'%')
          ->orWhere('remarks', 'like', '%'.$search.'%');
      })
      ->latest('updated_at')
      ->paginate($rows);
      
      if(count($reasons)==true){
        return $this->result(200,"Reasons has been fetched.",$reasons);
      }
      throw new FistoException("No records found.", 404, NULL, []);
    }

    public function show(Request $request,$id)
    {
      $reason = Reason::find($id);

      if (!empty($reason)) {
        $result = [
          "code" => 200,
          "message" => "Reason has been fetched.",
          "result" => $reason
        ];
        
        return response($result);
      }
      else
        throw new FistoException("No records found.", 404, NULL, []);
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

        $result = [
          "code" => 200,
          "message" => "New reason has been saved.",
          "result" => $new_reason
        ];
        
        return response($result);
      }
      else
        throw new FistoException("Reason already registered.", 409, NULL, []);
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
          throw new FistoException("Reason already registered.", 409, NULL, []);
      }
      else
        throw new FistoException("No records found.", 404, NULL, []);
    }

    public function change_status(Request $request,$id){
        $status = $request['status'];
        $model = new Reason();
        return $this->change_masterlist_status($status,$model,$id,'Reason');
    }
    
}
