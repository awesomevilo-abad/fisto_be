<?php

namespace App\Http\Controllers;

use App\Models\Referrence;
use App\Methods\GenericMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferrenceController extends Controller
{
    public function index(Request $request,$status,$tableRows)
    {
        $tableRows = (int)$tableRows;
        $is_active = $status;

        if ($is_active == 1) {
            $referrences = DB::table('referrences')
                ->select(['id','referrence_type as type','referrence_description as description','updated_at','deleted_at'])
                ->whereNull('deleted_at')
                ->latest()
                ->paginate($tableRows);

        } elseif ($is_active == 0) {
            $referrences = DB::table('referrences')
                ->select(['id','referrence_type as type','referrence_description as description','updated_at','deleted_at'])
                ->whereNotNull('deleted_at')
                ->latest()
                ->paginate($tableRows);

        } else {
            $referrences = DB::table('referrences')
                ->select(['id','referrence_type as type','referrence_description as description','updated_at','deleted_at'])
                ->latest()
                ->paginate($tableRows);
        }

        if (!$referrences || $referrences->isEmpty()) {
            $code = 404;
            $message = "Data Not Found!";
            $data = [];

        } else {
            $code = 200;
            $message = "Succefully Retrieved";
            $data = $referrences;

        }

        return $this->result($code,$message,$data);

    }

    public function all(Request $request,$status)
    {
        $is_active = $status;

        if ($is_active == 1) {
            $referrences = DB::table('referrences')
                ->select(['id','referrence_type'])
                ->whereNull('deleted_at')
                ->latest()
                ->get();

        } elseif ($is_active == 0) {
            $referrences = DB::table('referrences')
                ->select(['id','referrence_type'])
                ->whereNotNull('deleted_at')
                ->latest()
                ->get();

        } else {
            $referrences = DB::table('referrences')
                ->latest()
                ->get();
        }

        if (!$referrences || $referrences->isEmpty()) {
            $code = 404;
            $message = "Data Not Found!";
            $data = [];

        } else {
            $code =    200;
            $message = "Succefully Retrieved";
            $data = $referrences;

        }

        return $this->result($code,$message,$data);

    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'referrence_type' => 'required|string',
            'referrence_description' => 'required|string'
        ]);

       $duplicateValues= GenericMethod::validateDuplicateByIdAndTable($fields['referrence_type'],'referrence_type','referrences');

       if(count($duplicateValues)>0) {
            $code =403;
            $message = "Reference Already Registered";
            $data = [];
            return $this->result($code,$message,$data);
        }

        $new_referrence = Referrence::create([
            'referrence_type' => $fields['referrence_type']
            , 'referrence_description' => $fields['referrence_description']
        ]);

        if (!$new_referrence->count() == 0) {
            $response = [
                "code" => 201,
                "message" => "Succesfully Created!",
                "data" => $new_referrence,

            ];

        }

        $code =    200;
        $message = "Succefully Created";
        $data = $new_referrence;

        return $this->result($code,$message,$data);

    }

    public function show($id)
    {
        $result = Referrence::find($id);

        if (!$result) {
            $code = 404;
            $message = "Data Not Found!";
            $data = [];
        } else {
            $code = 200;
            $message = "Succesfully Retrieved";
            $data = $result;

        }
        return $this->result($code,$message,$data);
    }

    public function update(Request $request, $id)
    {
        $specific_referrence = Referrence::find($id);

        $fields = $request->validate([
            'referrence_type' => 'required|string',
            'referrence_description' => 'required|string',

        ]);

        if (!$specific_referrence) {
            $code =404;
            $message = "Data Not Found!";
            $data = [];
        } else {

            $validateDuplicateInUpdate =  GenericMethod::validateDuplicateInUpdate($fields['referrence_type'],'referrence_type','referrences',$id);
            if(count($validateDuplicateInUpdate)>0) {
                $code =403;
                $message = "Referrence type already registered in other referrence type";
                $data = [];
                return $this->result($code,$message,$data);
            }

            $specific_referrence->referrence_type = $request->get('referrence_type');
            $specific_referrence->referrence_description = $request->get('referrence_description');
            $specific_referrence->save();

            $code =200;
            $message = "Succefully Updated";
            $data = $specific_referrence;

        }

        return $this->result($code,$message,$data);
    }

    public function archive(Request $request, $id)
    {
        $softDeleteReferrence = Referrence::where('id',$id)->delete();
        if ($softDeleteReferrence == 0) {
            $code = 403;
            $data = [];
            $message = "Data Not Found";
        }else{

            $code =200;
            $message = "Succefully Archived";
            $data = [];
        }
        return $this->result($code,$message,$data);

    }

    public function restore(Request $request, $id)
    {
        $validateIfIdIsArchived = Referrence::onlyTrashed()->find($id);

        if (!isset($validateIfIdIsArchived)) {
            $code = 403;
            $data = [];
            $message = "Data is not in archive status";
            return $this->result($code,$message,$data);
        }

        $restoreSoftDelete = Referrence::onlyTrashed()->find($id)->restore();
        if ($restoreSoftDelete == 1) {
            $code = 200;
            $data = [];
            $message = "Succefully Restored";
        }else{
            $code = 403;
            $data = [];
            $message = "Data Not Found";
        }
        return $this->result($code,$message,$data);
    }

    public function search(Request $request,$status,$tableRows)
    {

        $tableRows = (int)$tableRows;
        $value = $request['value'];

        if($status == 1){
            $result = DB::table('referrences')
            ->select(['id','referrence_type as type','referrence_description as description','updated_at','deleted_at'])
            ->whereNull('deleted_at')
            ->where(function ($query) use ($value) {
                $query->where('referrence_type', 'like', '%' . $value . '%')
                    ->orWhere('referrence_description', 'like', '%' . $value . '%');

            })
            ->orderBy('updated_at','desc')
            ->paginate($tableRows);
        }else{
            $result = DB::table('referrences')
            ->select(['id','referrence_type as type','referrence_description as description','updated_at','deleted_at'])
            ->whereNotNull('deleted_at')
            ->where(function ($query) use ($value) {
                $query->where('referrence_type', 'like', '%' . $value . '%')
                    ->orWhere('referrence_description', 'like', '%' . $value . '%');

            })
            ->orderBy('updated_at','desc')
            ->paginate($tableRows);
        }

        if ($result->isEmpty()) {
            $code = 404;
            $message = "Data Not Found";
            $data = [];
        } else {
            $code = 200;
            $message = "Succefully Retrieved";
            $data = $result;
        }
        return $this->result($code,$message,$data);

    }

}
