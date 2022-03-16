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
        return $this->resultResponse('fetch','Supplier Type',$supplier_types);
      }
      return $this->resultResponse('not-found','Supplier Type',[]);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'type' => 'required|string',
            'transaction_days' => 'required',

        ]);
        $duplicateValues= GenericMethod::validateDuplicateByIdAndTable($fields['type'],'type','supplier_types');

        if(count($duplicateValues)>0) {
            return $this->resultResponse('registered','Supplier Type',[]);
         }

        $new_supplier_type = SupplierType::create([
            'type' => $fields['type']
            , 'transaction_days' => $fields['transaction_days']
        ]);

        return $this->resultResponse('save','Supplier Type',$new_supplier_type);
    }

    public function update(Request $request, $id)
    {
        $specific_supplier_type = SupplierType::find($id);
        $fields = $request->validate([
            'type' => 'required|string',
        ]);

        if (!$specific_supplier_type) {
            return $this->resultResponse('not-found','Supplier Type',[]);
        } else {
            $validateDuplicateInUpdate =  GenericMethod::validateDuplicateInUpdate($fields['type'],'type','supplier_types',$id);
            if(count($validateDuplicateInUpdate)>0) {
                return $this->resultResponse('registered','Supplier Type',[]);
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
