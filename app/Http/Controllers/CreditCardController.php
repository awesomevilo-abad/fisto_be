<?php

namespace App\Http\Controllers;

use App\Models\CreditCard;
use App\Models\Category;
use App\Models\Location;

use App\Exceptions\FistoException;
use App\Http\Requests\CreditCardRequest;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class CreditCardController extends Controller
{
    public function index(Request $request, bool $status, int $rows)
    {
        $credit_card = CreditCard::with(['utility_categories','utility_locations'])->withTrashed()
        ->where(function ($query) use ($status){
          return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
        })
        ->latest('updated_at')
        ->paginate($rows);
        
        if(count($credit_card)==true){
          return $this->result(200,"Credit Card has been fetched.",$credit_card);
        }
        throw new FistoException("No records found.", 404, NULL, []);
    }

    public function store(CreditCardRequest $request)
    {
        $fields = $request->validated();
        $credit_card_validateDuplicateAccountNo = CreditCard::withTrashed()->firstWhere('account_no', $fields['account_no']);

        if (!empty($credit_card_validateDuplicateAccountNo))
          throw new FistoException("Account no already registered.", 409, NULL, [
            "error_field" => "account_no"
          ]);
          $credit_card = CreditCard::create($fields);
          $credit_card->utility_categories()->attach($fields['categories']);
          $credit_card->utility_locations()->attach($fields['locations']);
          return $this->result(201,"Credit Card has been saved.",$credit_card);
    }
    
    public function show($id)
    {
      $credit_card = CreditCard::with('utility_categories','utility_locations')->withTrashed()
      ->where('id',$id)
      ->get();

      if(count($credit_card)==true){
        return $this->result(200,"Credit Card has been fetched",$credit_card);
      }
      throw new FistoException("No records found.", 404, NULL, []);
    }
    
    public function search(Request $request, bool $status, int $rows)
    {
        $value = $request['value'];
        $credit_card = CreditCard::with(['utility_categories','utility_locations'])->withTrashed()
        ->where(function ($query) use ($status){
          return ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
        })
        ->where(function ($query) use ($value){
          $query->where('credit_cards.name', 'like', '%'.$value.'%')
            ->orWhere('credit_cards.account_no', 'like', '%'.$value.'%');
        })
        ->latest('updated_at')
        ->paginate($rows);
        
        if(count($credit_card)==true){
          return $this->result(200,"Credit Card has been fetched.",$credit_card);
        }
        throw new FistoException("No records found.", 404, NULL, []);
    }

    public function update(CreditCardRequest $request, $id)
    {
      $fields = $request->validated();

      $credit_card = CreditCard::withTrashed()->find($id);

      if(!empty($credit_card) == true){

        $credit_card->name = $fields['name'];
        $credit_card->account_no = $fields['account_no'];
        $credit_card->save();
        $credit_card->utility_categories()->detach();
        $credit_card->utility_categories()->attach($fields['categories']);
        $credit_card->utility_locations()->detach();
        $credit_card->utility_locations()->attach($fields['locations']);

        return $this->result(200,"Credit Card has been updated",$credit_card);
      }
      throw new FistoException("No records found.", 404, NULL, []);



    }
    
    public function archive(Request $request,$id)
    {
      $softDeleteCreditCard = CreditCard::where('id', $id)->delete();
      if ($softDeleteCreditCard == true) {
        return $this->result(200,"Credit Card has been archived",[]);
      }
      else
        throw new FistoException("No records found.", 404, NULL, []);
    }
    
    public function restore(Request $request, $id)
    {
        if(!CreditCard::onlyTrashed()->find($id)){
            throw new FistoException("No records found.", 404, NULL, []);
        }
        $restoreSoftDelete = CreditCard::onlyTrashed()->find($id)->restore();
        if ($restoreSoftDelete == 1) {
            return $this->result(200,"Succefully Restored",[]);
        }
    }
}
