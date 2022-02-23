<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bank = DB::table('banks')
            ->where('is_active', '=', 1)
            ->paginate(10);

        if (!$bank || $bank->isEmpty()) {
            return "No Data Found";
        }
        return $bank;
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
            'bank_code' => 'required|string|unique:banks,bank_code',
            'bank_name' => 'required|string|unique:banks,bank_name',
            'bank_account' => 'required|string|unique:banks,bank_account',
            'bank_location' => 'required|string',
            'is_active' => 'required',

        ]);

        $new_bank = Bank::create([
            'bank_code' => $fields['bank_code']
            , 'bank_name' => $fields['bank_name']
            , 'bank_account' => $fields['bank_account']
            , 'bank_location' => $fields['bank_location']
            , 'is_active' => $fields['is_active'],
        ]);

        return "Bank Succesfully Created";
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Bank::find($id);
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
        $specific_bank = Bank::find($id);

        if (!$specific_bank) {
            return "No Data Found";
        }

        $specific_bank->bank_code = $request->get('bank_code');
        $specific_bank->bank_name = $request->get('bank_name');
        $specific_bank->bank_account = $request->get('bank_account');
        $specific_bank->bank_code = $request->get('bank_code');
        $specific_bank->bank_description = $request->get('bank_description');
        $specific_bank->save();

        return "Succesfully Updated!";
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
}
