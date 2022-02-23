<?php

namespace App\Http\Controllers;

use App\Models\SupplierType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $is_active = $request->get('is_active');

        if ($is_active == 'true') {
            $supplier_type = DB::table('supplier_types')
                ->where('is_active', '=', 1)
                ->latest()
                ->paginate(10);

        } elseif ($is_active == 'false') {
            $supplier_type = DB::table('supplier_types')
                ->where('is_active', '=', 0)
                ->latest()
                ->paginate(10);

        } else {
            $supplier_type = DB::table('supplier_types')
                ->latest()
                ->paginate(10);
        }

        if (!$supplier_type || $supplier_type->isEmpty()) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $supplier_type,
            ];
        } else {
            $response = [
                "code" => 200,
                "message" => "Succefully Retrieved",
                "data" => $supplier_type,
            ];

        }

        return response($response);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'type' => 'required|string|unique:supplier_types,type',
            'transaction_days' => 'required',
            'is_active' => 'required',

        ]);

        $new_supplier_type = SupplierType::create([
            'type' => $fields['type']
            , 'transaction_days' => $fields['transaction_days']
            , 'is_active' => $fields['is_active'],
        ]);

        return [
            $response = [
                "code" => 200,
                "message" => "Succefully Created",
                "data" => $new_supplier_type,
            ],
        ];
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $result = SupplierType::find($id);

        if (!$result) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $result,
            ];
        } else {
            $response = [
                "code" => 200,
                "message" => "Succefully Retrieved",
                "data" => $result,
            ];

        }

        return response($response);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $specific_supplier_type = SupplierType::find($id);

        $fields = $request->validate([
            'type' => ['unique:supplier_types,type,' . $id],

        ]);

        if (!$specific_supplier_type) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $specific_supplier_type,
            ];
        } else {

            $specific_supplier_type->type = $request->get('type');
            $specific_supplier_type->transaction_days = $request->get('transaction_days');
            $specific_supplier_type->save();
            $response = [
                "code" => 200,
                "message" => "Succefully Updated",
                "data" => $specific_supplier_type,
            ];

        }
        return response($response);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    #_________________________SPECIAL CASE________________________________
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function archive(Request $request, $id)
    {
        $specific_supplier_type = SupplierType::find($id);

        if (!$specific_supplier_type) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $specific_supplier_type,
            ];
        } else {

            $specific_supplier_type->is_active = 0;
            $specific_supplier_type->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Archieved",
                "data" => $specific_supplier_type,
            ];
        }
        return response($response);

    }

    public function search(Request $request)
    {
        $value = $request['value'];

        if (isset($request['is_active'])) {
            if ($request['is_active'] == 'active') {

                $is_active = 1;
            } else {

                $is_active = 0;
            }
        } else {
            $is_active = 1;
        }

        $result = SupplierType::where('is_active', $is_active)
            ->where(function ($query) use ($value) {
                $query = where('type', 'like', '%' . $value . '%')
                    ->orWhere('transaction_days', 'like', '%' . $value . '%');
            })
            ->get();

        if ($result->isEmpty()) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $result,
            ];
        } else {
            $response = [
                "code" => 200,
                "message" => "Succefully Retrieved",
                "data" => $result,
            ];

        }

        return response($response);
    }

    public function all(Request $request)
    {

        $is_active = $request->get('is_active');

        if ($is_active == 'active') {
            $supplier_type = DB::table('supplier_types')
                ->where('is_active', '=', 1)
                ->latest()
                ->get();

        } elseif ($is_active == 'inactive') {
            $supplier_type = DB::table('supplier_types')
                ->where('is_active', '=', 0)
                ->latest()
                ->get();

        } else {
            $supplier_type = DB::table('supplier_types')
                ->latest()
                ->get();
        }

        if (!$supplier_type || $supplier_type->isEmpty()) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $supplier_type,
            ];
        } else {
            $response = [
                "code" => 200,
                "message" => "Succefully Retrieved",
                "data" => $supplier_type,
            ];

        }

        return response($response);
    }

}
