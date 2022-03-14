<?php

namespace App\Http\Controllers;

use App\Models\SupplierType;
use App\Methods\GenericMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exceptions\FistoException;

class SupplierTypeController extends Controller
{

    public function index(Request $request)
    {
        $status =  $request['status'];
        $rows =  (empty($request['rows']))?10:(int)$request['rows'];
        $search =  $request['search'];
        
        $supplier_types = SupplierType::withTrashed()
        ->where(function ($query) use ($status){
          return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
        })
        ->where(function ($query) use ($search) {
            $query->where('type', 'like', '%' . $search . '%')
                ->orWhere('transaction_days', 'like', '%' . $search . '%');
        })
        ->latest('updated_at')
        ->paginate($rows);
        
        if(count($supplier_types)==true){
          return $this->result(200,"Supplier types has been fetched.",$supplier_types);
        }
        throw new FistoException("No records found.", 404, NULL, []);
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
            return $this->validateIfNothingChangeThenSave($specific_supplier_type,'Supplier type');
        }
    }

    public function change_status(Request $request,$id){
        $status = $request['status'];
        $model = new SupplierType();
        return $this->change_masterlist_status($status,$model,$id,'Supplier type');
    }
    


}
