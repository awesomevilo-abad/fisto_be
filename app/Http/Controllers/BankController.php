<?php

namespace App\Http\Controllers;

use App\Exceptions\FistoException;

use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{
  public function index(Request $request,$status,$rows)
    {
      $rows = (int)$rows;
      $status = (bool)$status;

      $banks = DB::table('banks as B')
      ->join('account_titles as AT1', 'B.account_title_1', 'AT1.id')
      ->join('account_titles as AT2', 'B.account_title_2', 'AT2.id')
      ->select(
        'B.id',
        'B.code',
        'B.branch',
        'B.account_no',
        'B.location',
        'AT1.id as account_title_1_id',
        'AT1.title as account_title_1',
        'AT2.id as account_title_2_id',
        'AT2.title as account_title_2',
        'B.updated_at',
        'B.deleted_at'
      )
      ->where(function ($query) use ($status) {
        if ($status == true) $query->whereNull('B.deleted_at');
        else $query->whereNotNull('B.deleted_at');
      })
      ->latest('B.updated_at')
      ->paginate($rows);

      if (count($banks) == true) {
        $result = [
          "code" => 200,
          "message" => "Banks has been fetched.",
          "result" => $banks
        ];
        
        return response($result);
      }
      else
        throw new FistoException("No records found.", 404, NULL, []);
    }

  public function all(Request $request,$status)
    {
      $status = (bool)$status;

      $banks = Bank::latest('name')
        ->where(function ($query) use ($status) {
          if ($status == true) $query->whereNull('deleted_at');
          else $query->whereNotNull('deleted_at');
        })
        ->get(['id','name']);

      if (count($banks) == true) {
        $result = [
          "code" => 200,
          "message" => "Banks has been fetched.",
          "result" => $banks
        ];
        
        return response($result);
      }
      else
        throw new FistoException("No records found.", 404, NULL, []);
    }
    
  public function show($id)
    {
      $bank = Bank::find($id);

      if (!empty($bank)) {
        $result = [
          "code" => 200,
          "message" => "Bank has been fetched.",
          "result" => $bank
        ];
        
        return response($result);
      }
      else
        throw new FistoException("No records found.", 404, NULL, []);
    }

  public function search(Request $request,$status,$rows)
    {
      $rows = (int)$rows;
      $status = (bool)$status;
      $value = $request['value'];

      $banks = DB::table('banks as B')
        ->join('account_titles as AT1', 'B.account_title_1', 'AT1.id')
        ->join('account_titles as AT2', 'B.account_title_2', 'AT2.id')
        ->select(
          'B.id',
          'B.code',
          'B.branch',
          'B.account_no',
          'B.location',
          'AT1.id as account_title_1_id',
          'AT1.title as account_title_1',
          'AT2.id as account_title_2_id',
          'AT2.title as account_title_2',
          'B.updated_at',
          'B.deleted_at'
        )
        ->where(function ($query) use ($status) {
          if ($status == true) $query->whereNull('B.deleted_at');
          else $query->whereNotNull('B.deleted_at');
        })
        ->where(function ($query) use ($value) {
          $query->where('B.code', 'like', '%'.$value.'%')
          ->orWhere('B.name', 'like', '%'.$value.'%')
          ->orWhere('B.branch', 'like', '%'.$value.'%')
          ->orWhere('B.account_no', 'like', '%'.$value.'%')
          ->orWhere('B.location', 'like', '%'.$value.'%');
        })
        ->latest('B.updated_at')
        ->paginate($rows);

      if (count($banks) == true) {
        $result = [
          "code" => 200,
          "message" => "Banks has been fetched.",
          "result" => $banks
        ];
        
        return response($result);
      }
      else
        throw new FistoException("No records found.", 404, NULL, []);
    }

  public function store(Request $request)
    {
      $fields = $request->validate([
        'code' => 'required|string|unique:banks,code',
        'name' => 'required|string',
        'branch' => 'required|string|unique:banks,branch',
        'account_no' => 'required|string|unique:banks,account_no',
        'location' => 'required|string',
        'account_title_1' => 'required|numeric',
        'account_title_2' => 'required|numeric'
      ]);

        // $new_bank = Bank::create([
        //     'bank_code' => $fields['bank_code']
        //     , 'bank_name' => $fields['bank_name']
        //     , 'bank_account' => $fields['bank_account']
        //     , 'bank_location' => $fields['bank_location']
        //     , 'is_active' => $fields['is_active'],
        // ]);

        // return [
        //     $response = [
        //         "code" => 200,
        //         "message" => "Succefully Created",
        //         "data" => $new_bank,
        //     ],
        // ];
    }
    
    public function update(Request $request, $id)
    {
        $specific_bank = Bank::find($id);

        $fields = $request->validate([
            'bank_code' => ['unique:banks,bank_code,' . $id],
            'bank_name' => ['unique:banks,bank_name,' . $id],
            'bank_account' => ['unique:banks,bank_account,' . $id],

        ]);

        if (!$specific_bank) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $specific_bank,
            ];
        } else {
            $specific_bank->bank_code = $request->get('bank_code');
            $specific_bank->bank_name = $request->get('bank_name');
            $specific_bank->bank_account = $request->get('bank_account');
            $specific_bank->bank_location = $request->get('bank_location');
            $specific_bank->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Updated",
                "data" => $specific_bank,
            ];

        }
        return response($response);

    }
    
    public function archive(Request $request, $id)
    {
        $specific_bank = Bank::find($id);

        if (!$specific_bank) {
            $response = [
                "code" => 404,
                "message" => "Data Not Found!",
                "data" => $specific_bank,
            ];
        } else {

            $specific_bank->is_active = 0;
            $specific_bank->save();

            $response = [
                "code" => 200,
                "message" => "Succefully Archieved",
                "data" => $specific_bank,
            ];
        }
        return response($response);
    }
}
