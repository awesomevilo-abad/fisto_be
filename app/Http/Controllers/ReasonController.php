<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;

use App\Models\Reason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReasonController extends Controller
{
    public function index(Request $request,$status,$rows)
      {
        $rows = (int)$rows;
        $status = (bool)$status;

        $reasons = DB::table('reasons')
          ->select(['id', 'reason', 'remarks', 'updated_at', 'deleted_at'])
          ->where(function ($query) use ($status) {
            if ($status == true) $query->whereNull('deleted_at');
            else  $query->whereNotNull('deleted_at');
          })
          ->latest('updated_at')
          ->paginate($rows);

        if (count($reasons) == true) {
          $result = [
            "code" => 200,
            "message" => "Reasons has been fetched.",
            "result" => $reasons
          ];
          
          return response($result);
        }
        else
          throw new FistoException("No records found.", 404, NULL, []);
      }

    public function all(Request $request,$status)
      {
        $status = (bool)$status;
        
        $reasons = DB::table('reasons')
          ->select(['id', 'reason', 'remarks'])
          ->where(function ($query) use ($status) {
            if ($status == true) $query->whereNull('deleted_at');
            else  $query->whereNotNull('deleted_at');
          })
          ->latest('reason')
          ->get();

        if (count($reasons) == true) {
          $result = [
            "code" => 200,
            "message" => "Reasons has been fetched.",
            "result" => $reasons
          ];
          
          return response($result);
        }
        else
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
      
    public function search(Request $request,$status,$rows)
      {
        $rows = (int)$rows;
        $status = (bool)$status;
        $value = $request['value'];

        $reasons = DB::table('reasons')
          ->select(['id', 'reason', 'remarks', 'updated_at', 'deleted_at'])
          ->where(function ($query) use ($status) {
            if ($status == true) $query->whereNull('deleted_at');
            else $query->whereNotNull('deleted_at');
          })
          ->where(function ($query) use ($value) {
            $query->where('reason', 'like', '%'.$value.'%')
              ->orWhere('remarks', 'like', '%'.$value.'%');
          })
          ->latest('updated_at')
          ->paginate($rows);

        if (count($reasons) == true) {
          $result = [
            "code" => 200,
            "message" => "Reasons has been fetched.",
            "result" => $reasons
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
            $reason->save();
  
            $result = [
              "code" => 200,
              "message" => "Reason has been updated.",
              "result" => $reason
            ];
            
            return response($result);
          }
          else
            throw new FistoException("Reason already registered.", 409, NULL, []);
        }
        else
          throw new FistoException("No records found.", 404, NULL, []);
      }
    
    public function archive(Request $request, $id)
      {
        $softDeleteReason = Reason::where('id', $id)->delete();

        if ($softDeleteReason == true) {
          $result = [
            "code" => 200,
            "message" => "Reason has been archived.",
            "result" => []
          ];
          
          return response($result);
        }
        else
          throw new FistoException("No records found.", 404, NULL, []);
      }

    public function restore(Request $request, $id)
      {
        $softRestoreReason = Reason::onlyTrashed()->where('id', $id)->restore();

        if ($softRestoreReason == true) {
          $result = [
            "code" => 200,
            "message" => "Reason has been restored.",
            "result" => []
          ];
          
          return response($result);
        }
        else
          throw new FistoException("No records found.", 404, NULL, []);
      }
}
