<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
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
            $company = DB::table('companies')
                ->where('is_active', '=', 1)
                ->latest()
                ->paginate(10);

        } elseif ($is_active == 'false') {
            $company = DB::table('companies')
                ->where('is_active', '=', 0)
                ->latest()
                ->paginate(10);

        } else {
            $company = DB::table('companies')
                ->latest()
                ->paginate(10);
        }

        if (!$company || $company->isEmpty()) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $company,
            ];
        } else {
            $response = [
                "code" => 200,
                "message" => "Succefully Retrieved",
                "data" => $company,
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
            'company_code' => 'required|string|unique:companies,company_code',
            'company_description' => 'required|string|unique:companies,company_description',
            'is_active' => 'required',

        ]);

        $new_company = Company::create([
            'company_code' => $fields['company_code']
            , 'company_description' => $fields['company_description']
            , 'is_active' => $fields['is_active'],
        ]);

        return [
            $response = [
                "code" => 200,
                "message" => "Succefully Created",
                "data" => $new_company,
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
        $result = Company::find($id);

        if (empty($result)) {
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
        $specific_company = Company::find($id);

        $fields = $request->validate([
            'company_code' => ['unique:companies,company_code,' . $id],
            'company_description' => ['unique:companies,company_description,' . $id,],

        ]);

        if (!$specific_company) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $specific_company,
            ];
        } else {

            $specific_company->company_code = $request->get('company_code');
            $specific_company->company_description = $request->get('company_description');
            $specific_company->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Updated",
                "data" => $specific_company,
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
        $specific_company = Company::find($id);

        if (!$specific_company) {

            $response = [
                "code" => 404,
                "message" => "Data Not Found",
                "data" => $specific_company,
            ];
        } else {

            $specific_company->is_active = 0;
            $specific_company->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Archieved",
                "data" => $specific_company,
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

        $result = Company::where('is_active', $is_active)
            ->where(function ($query) use ($value) {
                $query->where('company_code', 'like', '%' . $value . '%')
                    ->orWhere('company_description', 'like', '%' . $value . '%');
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

}
