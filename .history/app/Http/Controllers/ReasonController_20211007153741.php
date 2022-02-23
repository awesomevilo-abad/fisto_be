<?php

namespace App\Http\Controllers;

use App\Models\Reason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReasonController extends Controller
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
            $reason = DB::table('reasons')
                ->where('is_active', '=', 1)
                ->latest()
                ->paginate(10);

        } elseif ($is_active == 'false') {
            $reason = DB::table('reasons')
                ->where('is_active', '=', 0)
                ->latest()
                ->paginate(10);

        } else {
            $reason = DB::table('reasons')
                ->latest()
                ->paginate(10);
        }

        if (!$reason || $reason->isEmpty()) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $reason,
            ];
        } else {
            $response = [
                "code" => 200,
                "message" => "Succefully Retrieved",
                "data" => $reason,
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
            'reason' => 'required|string|unique:reasons,reason',
            'remarks' => 'required|string|unique:reasons,remarks',
            'is_active' => 'required',

        ]);

        $new_reason = Reason::create([
            'reason' => $fields['reason']
            , 'remarks' => $fields['remarks']
            , 'is_active' => $fields['is_active'],
        ]);

        return [
            $response = [
                "code" => 200,
                "message" => "Succefully Created",
                "data" => $new_reason,
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
        $result = Reason::find($id);

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
        $specific_reason = Reason::find($id);

        $fields = $request->validate([
            'reason' => ['unique:reasons,reason,' . $id],
            'remarks' => ['unique:reasons,remarks,' . $id],

        ]);

        if (!$specific_reason) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $specific_reason,
            ];
        } else {

            $specific_reason->reason = $request->get('reason');
            $specific_reason->remarks = $request->get('remarks');
            $specific_reason->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Updated",
                "data" => $specific_reason,
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
        $specific_reason = Reason::find($id);

        if (!$specific_reason) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $specific_reason,
            ];
        } else {

            $specific_reason->is_active = 0;
            $specific_reason->save();

            $response = [
                "code" => 200,
                "message" => "Reason Succesfully Archived",
                "data" => $specific_reason,
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

        $result = Reason::where('is_active', $is_active)
            ->where(function ($query) use ($value) {
                $query->where('reason', 'like', '%' . $value . '%')
                    ->orWhere('remarks', 'like', '%' . $value . '%');
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
        // return $result;
    }

}
