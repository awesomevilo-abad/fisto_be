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
    public function index(Request $request)
    {
      $status =  $request['status'];
      $rows =  (empty($request['rows']))?10:$request['rows'];
      $search =  $request['search'];
      
      $credit_card= CreditCard::with(['utility_categories','utility_locations'])->withTrashed()
      ->where(function ($query) use ($status){
        ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
      })
      ->where(function ($query) use ($search){
        $query->where('name', 'like', '%'.$search.'%')
          ->orWhere('account_no', 'like', '%'.$search.'%');
      })
      ->latest('updated_at')
      ->paginate($rows);
  
      if(count($credit_card) == true)
        return $this->result(200,'Credit Card has been fetched.',$credit_card);
      else
        throw new FistoException("No records found.",404,NULL,[]);
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
    
  public function change_status(Request $request,$id){
    $status = $request['status'];
    $model = new CreditCard();
    return $this->change_masterlist_status($status,$model,$id);
  }
}
