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
      $rows =  (empty($request['rows']))?10:(int)$request['rows'];
      $search =  $request['search'];
      $paginate = (isset($request['paginate']))? $request['paginate']:$paginate = 1;
      
      $credit_card= CreditCard::with(['utility_categories','utility_locations'])->withTrashed()
      ->where(function ($query) use ($status){
        ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
      })
      ->where(function ($query) use ($search){
        $query->where('name', 'like', '%'.$search.'%')
          ->orWhere('account_no', 'like', '%'.$search.'%');
      });

      if($paginate==0){
        $credit_card = CreditCard::withTrashed()
        ->where(function ($query) use ($status){
          ($status==true)?$query->whereNull('deleted_at'):$query->whereNotNull('deleted_at');
        })
        ->get(['id','account_no as no'])
        ->unique('no');
        $credit_card = array("account_numbers"=>$credit_card);
      }else{
        $credit_card = $credit_card->latest('updated_at')
        ->paginate($rows);
      }
  
      if(count($credit_card) == true)
        return $this->resultResponse('fetch','Credit Card',$credit_card);
      else
        return $this->resultResponse('not-found','Credit Card',[]);
    }

    public function store(CreditCardRequest $request)
    {
        $fields = $request->toArray();
        
        // $credit_card_validateDuplicateAccountNo = CreditCard::withTrashed()->firstWhere('account_no', $fields['account_no']);

        // if (!empty($credit_card_validateDuplicateAccountNo))
        //   return $this->resultResponse('registered','Account number',["error_field" => "account_no"]);


          $credit_card = CreditCard::create($fields);
          $credit_card->utility_categories()->attach($fields['categories']);
          $credit_card->utility_locations()->attach($fields['locations']);
          return $this->resultResponse('save','Credit Card',$credit_card);
    }
    
    public function update(CreditCardRequest $request, $id)
    {
      $fields = $request->validated();

      $credit_card = CreditCard::withTrashed()->find($id);

      if(!empty($credit_card) == true){

        $credit_card->name = $fields['name'];
        $credit_card->account_no = $fields['account_no'];
        
        $is_tagged_array_modified_category = $this->isTaggedArrayModified($fields['categories'],  $credit_card->utility_categories()->get(),'id');
        $is_tagged_array_modified_location = $this->isTaggedArrayModified($fields['locations'],  $credit_card->utility_locations()->get(),'id');
        $is_tagged_array_modified = $this->isMultipleTaggedArrayModified($is_tagged_array_modified_category,$is_tagged_array_modified_location);

        $credit_card->utility_categories()->detach();
        $credit_card->utility_categories()->attach($fields['categories']);
        $credit_card->utility_locations()->detach();
        $credit_card->utility_locations()->attach($fields['locations']);
        return $this->validateIfNothingChangeThenSave($credit_card,'Credit card',$is_tagged_array_modified);
      }
      return $this->resultResponse('not-found','Credit Card',[]);
    }
    
  public function change_status(Request $request,$id){
    $status = $request['status'];
    $model = new CreditCard();
    return $this->change_masterlist_status($status,$model,$id,'Credit Card');
  }
}
