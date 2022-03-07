<?php

namespace App\Http\Controllers;

use App\Models\Referrence;
use App\Methods\GenericMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exceptions\FistoException;

class ReferrenceController extends Controller
{
    public function index(Request $request)
    {
        $status =  $request['status'];
        $rows =  (empty($request['rows']))?10:$request['rows'];
        $search =  $request['search'];

        $referrences = Referrence::withTrashed()
        ->select(['id','referrence_type as type','referrence_description as description','updated_at','deleted_at'])
        ->where(function ($query) use ($status) {
            if ($status == true) $query->whereNull('deleted_at');
            else  $query->whereNotNull('deleted_at');
        })
        ->where(function ($query) use ($search) {
            $query->where('referrence_type', 'like', '%' . $search . '%')
                ->orWhere('referrence_description', 'like', '%' . $search . '%');
        })
        ->latest('updated_at')
        ->paginate($rows);

        if (count($referrences) == true) {
            return $this->result(200,"References has been fetched",$referrences);
        }
        else
         throw new FistoException("No records found.", 404, NULL, []);
    }
    public function store(Request $request)
    {
        $fields = $request->validate([
            'type' => 'required|string',
            'description' => 'required|string'
        ]);

       $duplicateValues= GenericMethod::validateDuplicateByIdAndTable($fields['type'],'referrence_type','referrences');

       if(count($duplicateValues)>0) {
            $code =403;
            $message = "Reference Already Registered";
            $data = [];
            return $this->result($code,$message,$data);
        }

        $new_referrence = Referrence::create([
            'referrence_type' => $fields['type']
            , 'referrence_description' => $fields['description']
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
            'type' => 'required|string',
            'description' => 'required|string',
        ]);

        if (!$specific_referrence) {
            $code =404;
            $message = "Data Not Found!";
            $data = [];

        } else {

            $validateDuplicateInUpdate =  GenericMethod::validateDuplicateInUpdate($fields['type'],'referrence_type','referrences',$id);
            if(count($validateDuplicateInUpdate)>0) {
                $code =403;
                $message = "Referrence type already registered in other referrence type";
                $data = [];
                return $this->result($code,$message,$data);
            }

            $specific_referrence->referrence_type = $request->get('type');
            $specific_referrence->referrence_description = $request->get('description');
            $specific_referrence->save();

            $code =200;
            $message = "Succefully Updated";
            $data = $specific_referrence;

        }

        return $this->result($code,$message,$data);
    }
    public function change_status(Request $request,$id)
    {
        $status = $request['status'];
        $model = new Referrence();
        return $this->change_masterlist_status($status,$model,$id);
    }
}
