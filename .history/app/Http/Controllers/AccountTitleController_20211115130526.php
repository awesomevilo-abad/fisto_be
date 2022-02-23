<?php

namespace App\Http\Controllers;

use App\Models\AccountTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountTitleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $is_active = $request->get('is_active');



        $account_titles = DB::table('account_titles')
        ->latest()
        ->paginate(10);

        return empty($is_active);

        if ($is_active ==true) {
            $account_titles = DB::table('account_titles')
                ->where('is_active', '=', 1)
                ->latest()
                ->paginate(10);

        } elseif ($is_active == false) {
            $account_titles = DB::table('account_titles')
                ->where('is_active', '=', 0)
                ->latest()
                ->paginate(10);

        }


        // $code = 200;
        // $message = "Succefully Retrieved";
        // $data = $account_titles;

        // if (!$account_titles || $account_titles->isEmpty()) {

        //     $code = 404;
        //     $message = "Data Not Found!";
        //     $data = $account_titles;
        // }

        // return $this->result($code,$message,$data);
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
        $account_titles = $request['title'];

        $fields = $request->validate([
            'code' => 'nullable',
            'title' => 'nullable',
            'is_active' => 'required',
        ]);

        $validate_account_titles_company = DB::table('account_titles')
        ->where('code',$fields['code'])
        ->orWhere('title',$fields['title'])->get();

        if(count($validate_account_titles_company)>0){
            return $this->result(403,'Account Title Already exist',null);
        }

        $new_account_titles = AccountTitle::create([
            'code' => $fields['code']
            ,'title' => $fields['title']
            , 'is_active' => $fields['is_active'],
        ]);

        return $this->result(200,'Succefully Created',$new_account_titles);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AccountTitle  $accountTitle
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $result = AccountTitle::find($id);

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
     * @param  \App\Models\AccountTitle  $accountTitle
     * @return \Illuminate\Http\Response
     */
    public function edit(AccountTitle $accountTitle)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AccountTitle  $accountTitle
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, $id)
    {
        $specific_account_titles = AccountTitle::find($id);

        $fields = $request->validate([
            'category' => ['unique:account_titles,category,' . $id],

        ]);

        if (!$specific_account_titles) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $specific_account_titles,
            ];
        } else {

            $specific_account_titles->category = $request->get('category');
            $specific_account_titles->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Updated",
                "data" => $specific_account_titles,
            ];

        }
        return response($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AccountTitle  $accountTitle
     * @return \Illuminate\Http\Response
     */
    public function destroy(AccountTitle $accountTitle)
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
        $specific_account_titles = AccountTitle::find($id);

        if (!$specific_account_titles) {

            $response = [
                "code" => 404,
                "message" => "Data Not Found",
                "data" => $specific_account_titles,
            ];
        } else {

            $specific_account_titles->is_active = 0;
            $specific_account_titles->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Archieved",
                "data" => $specific_account_titles,
            ];
        }

        return response($response);

    }

    public function restore(Request $request, $id)
    {
        $specific_account_titles = AccountTitle::find($id);

        if(!$specific_account_titles){

            return $this->result(404,"Data Not Found", $specific_account_titles);
        }

            $specific_account_titles->is_active = 1;
            $specific_account_titles->save();

            return $this->result(200,"Succesfully Restored",$specific_account_titles);
    }

    public function search(Request $request)
    {
        $value = $request['value'];

        if (isset($request['is_active'])) {
            if ($request['is_active'] == true) {

                $is_active = 1;
            } else {

                $is_active = 0;
            }
        } else {
            $is_active = 1;
        }

        $result = AccountTitle::where('is_active', $is_active)
            ->where(function ($query) use ($value) {
                $query->where('category', 'like', '%' . $value . '%');
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
