<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
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
            $location = DB::table('locations')
                ->where('is_active', '=', 1)
                ->latest()
                ->paginate(10);

        } elseif ($is_active == 'false') {
            $location = DB::table('locations')
                ->where('is_active', '=', 0)
                ->latest()
                ->paginate(10);

        } else {
            $location = DB::table('locations')
                ->latest()
                ->paginate(10);
        }

        $code = 200;
        $message = "Succefully Retrieved";
        $data = $location;

        if (!$location || $location->isEmpty()) {

            $code = 404;
            $message = "Data Not Found!";
            $data = $location;
        }

        return $this->result($code,$message,$data);
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
            'code' => 'required|string|unique:locations,code',
            'is_active' => 'required',
            'location'=>'nullable',
            'company'=>'nullable'
        ]);

        $validate_location_company = DB::table('locations')
        ->where('location',$fields['location'])
        ->where('company',$fields['company'])->get();

        if(count($validate_location_company)>0){
            return $this->result(403,'Either location or company already exist',null);
        }

        $new_location = Location::create([
            'code' => $fields['code']
            , 'location' => $fields['location']
            , 'company' => $fields['company']
            , 'is_active' => $fields['is_active'],
        ]);

        return $this->result(200,'Succefully Created',$new_location);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $location = Location::find($id);

        $code = 200;
        $message = "Succefully Retrieved";
        $data = $location;
        return $location;
        if (count($location)>0) {

            $code = 404;
            $message = "Data Not Found!";
            $data = $location;
        }

        return $this->result($code,$message,$data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function edit(Location $location)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Location $location)
    {
        $specific_location = Location::find($id);

        $fields = $request->validate([
            'code' => 'nullable',
            'location' =>['unique:locations,location,'.$id],
            'company' => 'nullable',
        ]);

        if (!$specific_location) {
            return $this->result(404,'Data Not Found',null);
        }

        $specific_location->code = $fields['code'];
        $specific_location->location = $fields['location'];
        $specific_location->company = $fields['company'];
        $specific_location->save();

        $response = [
            "code" => 200,
            "message" => "Succefully Updated",
            "data" => $specific_location,
        ];
        return response($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function destroy(Location $location)
    {
        //
    }


    public function archive(Request $request, $id)
    {
        $specific_location = Location::find($id);

        if(!$specific_location){

            return $this->result(404,"Data Not Found", $specific_location);
        }

            $specific_location->is_active = 0;
            $specific_location->save();

            return $this->result(200,"Succesfully Archieved",$specific_location);
    }

    public function restore(Request $request, $id)
    {
        $specific_location = Location::find($id);

        if(!$specific_location){

            return $this->result(404,"Data Not Found", $specific_location);
        }

            $specific_location->is_active = 1;
            $specific_location->save();

            return $this->result(200,"Succesfully Restored",$specific_location);
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

        $result = Location::where('code', 'like', '%' . $value . '%')
            ->where('is_active', $is_active)
            ->orWhere('location', 'like', '%' . $value . '%')
            ->orWhere('company', 'like', '%' . $value . '%')
            ->paginate(10);

        if ($result->isEmpty()) {
            return $this->result(404,"Data Not Found",null);
        }
        return $this->result(200,"Search Result",$result);
    }
}
