<?php

namespace App\Http\Controllers;

use App\Models\SupplierType;
use App\Methods\GenericMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierTypeController extends Controller
{

    public function index(Request $request,$status,$tableRows)
    {
        $tableRows = (int)$tableRows;
        $is_active = $status;
        
        if ($is_active == 1) {
            $supplier_type = DB::table('supplier_types AS s')
                ->select('s.id', 's.type', 's.transaction_days', 's.updated_at', 's.deleted_at')
                ->whereNull('deleted_at')
                ->latest()
                ->paginate($tableRows);
        }
        else {
            $supplier_type = DB::table('supplier_types AS s')
                ->select('s.id', 's.type', 's.transaction_days', 's.updated_at', 's.deleted_at')
                ->whereNotNull('deleted_at')
                ->latest()
                ->paginate($tableRows);
        }

        if (!$supplier_type || $supplier_type->isEmpty()) {
            $code = 404;
            $message = "Data Not Found!";
            $data = [];
        } else {
            $code = 200;
            $message = "Succefully Retrieved";
            $data = $supplier_type;

        }

        return $this->result($code,$message,$data);
    }

    public function all(Request $request,$status)
    {
        $is_active = $status;

        if ($is_active ==1) {
            $supplier_type = DB::table('supplier_types')
                ->select(['id','type'])
                ->whereNull('deleted_at')
                ->latest()
                ->get();

        } elseif ($is_active == 0) {
            $supplier_type = DB::table('supplier_types')
                ->select(['id','type'])
                ->whereNotNull('deleted_at')
                ->latest()
                ->get();

        } else {
            $supplier_type = DB::table('supplier_types')
                ->latest()
                ->get();
        }

        if (!$supplier_type || $supplier_type->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = [];
        } else {
            $code = 200;
            $message = "Succefully Retrieved";
            $data = $supplier_type;

        }

        return $this->result($code,$message,$data);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'type' => 'required|string',
            'transaction_days' => 'required',

        ]);

        $duplicateValues= GenericMethod::validateDuplicateByIdAndTable($fields['type'],'type','supplier_types');

        if(count($duplicateValues)>0) {
             $code =403;
             $message = "Supplier type already registered.";
             $data = [];
             return $this->result($code,$message,$data);
         }

        $new_supplier_type = SupplierType::create([
            'type' => $fields['type']
            , 'transaction_days' => $fields['transaction_days']
        ]);

        return $response = [
                "code" => 200,
                "message" => "New supplier type has been saved.",
                "result" => $new_supplier_type,
            ];
    }

    public function show($id)
    {
        $result = SupplierType::find($id);

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
        $specific_supplier_type = SupplierType::find($id);
        $fields = $request->validate([
            'type' => 'required|string',
        ]);

        if (!$specific_supplier_type) {
            $code =404;
            $message = "Data Not Found!";
            $data = [];
        } else {
            $validateDuplicateInUpdate =  GenericMethod::validateDuplicateInUpdate($fields['type'],'type','supplier_types',$id);
            if(count($validateDuplicateInUpdate)>0) {
                $code =403;
                $message = "Supplier type already registered.";
                $data = [];
                return $this->result($code,$message,$data);
            }
            $specific_supplier_type->type = $request->get('type');
            $specific_supplier_type->transaction_days = $request->get('transaction_days');
            $specific_supplier_type->save();
            $code =200;
            $message = "Supplier type has been updated.";
            $data = $specific_supplier_type;
        }
        return $this->result($code,$message,$data);

    }

    public function archive(Request $request, $id)
    {
        $softDeleteReferrence = SupplierType::where('id',$id)->delete();
        if ($softDeleteReferrence == 0) {
            $code = 403;
            $data = [];
            $message = "Data Not Found";
        }else{

            $code =200;
            $message = "Supplier type has been archived.";
            $data = [];
        }
        return $this->result($code,$message,$data);

    }

    public function restore(Request $request, $id)
    {
        $validateIfIdIsArchived = SupplierType::onlyTrashed()->find($id);

        if (!isset($validateIfIdIsArchived)) {
            $code = 403;
            $data = [];
            $message = "Supplier type is not in archive status.";
            return $this->result($code,$message,$data);
        }

        $restoreSoftDelete = SupplierType::onlyTrashed()->find($id)->restore();
        if ($restoreSoftDelete == 1) {
            $code = 200;
            $data = [];
            $message = "Supplier type has been restored.";
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
            $result = DB::table('supplier_types AS s')
            ->select('s.id', 's.type', 's.transaction_days', 's.updated_at', 's.deleted_at')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($value) {
                $query->where('type', 'like', '%' . $value . '%')
                    ->orWhere('transaction_days', 'like', '%' . $value . '%');
            })
            ->orderBy('updated_at','desc')
            ->paginate($tableRows);
        }else{
            $result = DB::table('supplier_types AS s')
            ->select('s.id', 's.type', 's.transaction_days', 's.updated_at', 's.deleted_at')
            ->whereNotNull('deleted_at')
            ->where(function ($query) use ($value) {
                    $query->where('type', 'like', '%' . $value . '%')
                        ->orWhere('transaction_days', 'like', '%' . $value . '%');
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
