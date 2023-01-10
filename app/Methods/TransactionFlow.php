<?php
namespace App\Methods;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// For Pagination with Collection
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Tagging;
use App\Models\Gas;
use App\Models\Filing;
use App\Models\Associate;
use App\Models\Specialist;
use App\Models\Match;
use App\Models\ReturnVoucher;
use App\Models\Approver;
use App\Models\ChequeCreation;
use App\Models\ChequeInfo;
use App\Models\ChequeReleased;
use App\Models\ChequeClearing;
use App\Models\Reason;
use App\Models\Transmit;
use App\Models\Treasury;
use App\Models\RequestorLogs;
use App\Methods\GenericMethod;
use App\Models\Release;
use App\Models\File;
use App\Models\Reverse;
use App\Models\Clear;
use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;
class TransactionFlow{


    public static function updateInTransactionFlow ($request,$id) {
        // return GenericMethod::floatvalue('46,072.50');
       $transaction = Transaction::find($id);
        if(!isset($transaction)){
            return GenericMethod::resultResponse('not-found','transaction',[]);
        }
        $process =  $request['process'];
        $subprocess =  $request['subprocess'];
        $reason_id = (isset($request['reason']['id']) ? $request['reason']['id']:null);
        $date_now= Carbon::now('Asia/Manila')->format('Y-m-d');
     
        $transaction= Transaction::select(
            'transaction_id'
            ,'tag_no'
            ,'voucher_no'
            ,'users_id'
            ,'remarks'
            ,'document_amount'
            ,'referrence_amount'
            ,'distributed_id'
            ,'approver_id'
            ,'request_id'
            )
            ->find($id);

        $request_id = $transaction->request_id;
        $transaction_id = $transaction->transaction_id;
        $remarks = $transaction->remarks;
        $users_id = $transaction->users_id;

        $tag_no = $transaction->tag_no;
        if(($transaction->tag_no)==0 AND ($subprocess == 'tag')){
            $tag_no = GenericMethod::generateTagNo();
        }

        
        $previous_voucher_transaction = Transaction::with('transaction_voucher.account_title')->where('transaction_id',$transaction['transaction_id'])->latest()->first();
        $previous_cheque_transaction = Transaction::with('transaction_cheque.account_title')->where('transaction_id',$transaction['transaction_id'])->latest()->first();
        
        // $previous_percentage_tax = ($previous_voucher_transaction['transaction_voucher']->isEmpty())?NULL:$previous_voucher_transaction['transaction_voucher']->first()['percentage_tax'];
        // $previous_withholding_tax = ($previous_voucher_transaction['transaction_voucher']->isEmpty())?NULL:$previous_voucher_transaction['transaction_voucher']->first()['witholding_tax'];
        // $previous_net_amount = ($previous_voucher_transaction['transaction_voucher']->isEmpty())?NULL:$previous_voucher_transaction['transaction_voucher']->first()['net_amount'];
        $previous_receipt_type = ($previous_voucher_transaction['transaction_voucher']->isEmpty())?NULL:$previous_voucher_transaction['transaction_voucher']->first()['receipt_type'];
        $previous_voucher_no = ($previous_voucher_transaction['transaction_voucher']->isEmpty())?NULL:$previous_voucher_transaction['voucher_no'];
        $previous_voucher_month = ($previous_voucher_transaction['transaction_voucher']->isEmpty())?NULL:$previous_voucher_transaction['voucher_month'];
        $previous_approver = array("id"=>$previous_voucher_transaction['transaction_voucher']->first()['approver_id'],"name"=>$previous_voucher_transaction['transaction_voucher']->first()['approver_name']);
        $previous_distributed = array("id"=>$previous_voucher_transaction['distributed_id'],"name"=>$previous_voucher_transaction['distributed_name']);

        $cheque_cheques = ($previous_cheque_transaction['transaction_cheque']->isEmpty())?NULL:$previous_cheque_transaction['transaction_cheque']->first()['cheques'];
        $cheque_account_title = ($previous_cheque_transaction['transaction_cheque']->isEmpty())?NULL:$previous_cheque_transaction['transaction_cheque']->first()['account_title'];
        $voucher_account_title = $previous_voucher_transaction['transaction_voucher']->first()['account_title'];
        
        $previous_cheque_transaction_account_title = GenericMethod::format_account_title($cheque_account_title);
        $previous_cheque_transaction_cheque = GenericMethod::format_cheque($cheque_cheques);

        $previous_cheque_transaction_account_title = isset($previous_cheque_transaction_account_title['accounts'])?$previous_cheque_transaction_account_title['accounts']:null;
        $previous_cheque_transaction_cheque = isset($previous_cheque_transaction_cheque['cheques'])?$previous_cheque_transaction_cheque['cheques']:null;

        $reason_description= isset($request['reason']['description'])?$request['reason']['description']:null;
        $reason_remarks=  isset($request['reason']['remarks'])?$request['reason']['remarks']:null;
        $distributed_to=  isset($request['distributed_to'])?$request['distributed_to']:null;
        $accounts=  isset($request['accounts'])?$request['accounts']:NULL;
        $cheque_cheques=  isset($request['cheques'])?$request['cheques']:NULL;
        $date_cleared=  isset($request['date_cleared'])?$request['date_cleared']:NULL;

        // $percentage_tax = GenericMethod::with_previous_transaction($request['tax']['percentage_tax'],$previous_percentage_tax);
        // $withholding_tax = GenericMethod::with_previous_transaction($request['tax']['withholding_tax'],$previous_withholding_tax);
        // $net_amount = GenericMethod::with_previous_transaction($request['tax']['net_amount'],$previous_net_amount);
        $receipt_type = GenericMethod::with_previous_transaction($request['receipt_type'],$previous_receipt_type);
        $voucher_no = GenericMethod::with_previous_transaction($request['voucher']['no'],$previous_voucher_no);
        $voucher_month = GenericMethod::with_previous_transaction($request['voucher']['month'],$previous_voucher_month);
        $voucher_account_titles=  GenericMethod::with_previous_transaction($accounts,$voucher_account_title);
        $approver = GenericMethod::with_previous_transaction($request['approver'],$previous_approver);
        $distributed = GenericMethod::with_previous_transaction($request['distributed_to'],$previous_distributed);

        $approver_id=  isset($approver['id'])?$approver['id']:NULL;
        $approver_name=  isset($approver['name'])?$approver['name']:NULL;
        $distributed_id=  isset($distributed['id'])?$distributed['id']:NULL;
        $distributed_name=  isset($distributed['name'])?$distributed['name']:NULL;

        $cheque_cheques = GenericMethod::with_previous_transaction($cheque_cheques,$previous_cheque_transaction_cheque);
        $cheque_account_titles = GenericMethod::with_previous_transaction($accounts,$previous_cheque_transaction_account_title);

        
        if(isset($voucher_account_titles)){
            $voucher_account_titles = GenericMethod::object_to_array($voucher_account_titles);
        }

        if(isset($cheque_account_titles)){
            $cheque_account_titles = GenericMethod::object_to_array($cheque_account_titles);
        }

        if(isset($cheque_cheques)){
            $cheque_cheques = GenericMethod::object_to_array($cheque_cheques);
        }

        if($process == 'requestor'){
            $model = new RequestorLogs;
            if($subprocess == 'void'){
                $status= 'requestor-void';
                $state= 'void';
            }
            GenericMethod::insertRequestorLogs($id,$transaction_id,$date_now,$remarks,
            $users_id,$status,$reason_id,$reason_description,$reason_remarks);
            GenericMethod::updateTransactionStatus($transaction_id,$request_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month,$distributed_id,$distributed_name,$approver_id,$approver_name);
        }else if($process == 'tag'){
            $model = new Tagging;
            if($subprocess == 'receive'){
                $status= 'tag-receive';
            }else if($subprocess == 'hold'){
                $status= 'tag-hold';
            }else if($subprocess == 'return'){
                $status= 'tag-return';
            }else if($subprocess == 'void'){
                $status= 'tag-void';
            }else if($subprocess == 'tag'){
                $status= 'tag-tag';
            }else if(in_array($subprocess,['unhold','unreturn'])){
                $status = GenericMethod::getStatus($process,$transaction);
            }
            if(!isset($status)){
                return GenericMethod::resultResponse('invalid-access','','');
            }
            $state= $subprocess;
            GenericMethod::tagTransaction($model,$request_id,$transaction_id,$remarks,$date_now,$reason_id,$reason_remarks,$status,$distributed_to );
            GenericMethod::updateTransactionStatus($transaction_id,$request_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month,$distributed_id,$distributed_name,$approver_id,$approver_name);
        }else if($process == 'voucher'){
            $account_titles = $voucher_account_titles;
            $model = new Associate;
            if($subprocess == 'receive'){
                $status= 'voucher-receive';
            }else if($subprocess == 'hold'){
                $status= 'voucher-hold';
            }else if($subprocess == 'return'){
                $status= 'voucher-return';
            }else if($subprocess == 'void'){
                $status= 'voucher-void';
            }else if($subprocess == 'voucher'){
                GenericMethod::voucherNoValidationUponSaving($voucher_no,$id);
                $status= 'voucher-voucher';
            }else if(in_array($subprocess,['unhold','unreturn'])){
                $status = GenericMethod::getStatus($process,$transaction);
            }
            if(!isset($status)){
                return GenericMethod::resultResponse('invalid-access','','');
            }
            $state= $subprocess;
            $document_amount = $transaction['document_amount'];
            if(!$document_amount){
                $document_amount = $transaction['referrence_amount'];
            }
            
            if(!empty($account_titles)){
                $debit_entries_amount = array_filter($account_titles, function ($account_title){
                    return strtolower($account_title['entry'])!=strtolower("credit");
                });
                
                $credit_entries_amount = array_filter($account_titles, function ($account_title){
                    return strtolower($account_title['entry'])!=strtolower("debit");
                });
                
                $debit_amount= array_sum(array_column($debit_entries_amount,'amount'));
                $credit_amount= array_sum(array_column($credit_entries_amount,'amount'));
                
                if($debit_amount != $credit_amount){
                    return GenericMethod::resultResponse('not-equal','Total debit and credit',[]); 
                }
    
                if($document_amount != $debit_amount){
                    return GenericMethod::resultResponse('not-equal','Document and account title',[]); 
                }
            }
            GenericMethod::voucherTransaction($model,$transaction_id,$tag_no,$reason_remarks,$date_now,$reason_id,$status,$receipt_type,$voucher_no,$approver,$account_titles );
            GenericMethod::updateTransactionStatus($transaction_id,$request_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month,$distributed_id,$distributed_name,$approver_id,$approver_name);

        }else if($process == 'approve'){
            $model = new Approver;
            if($subprocess == 'receive'){
                $status= 'approve-receive';
            }else if($subprocess == 'hold'){
                $status= 'approve-hold';
            }else if($subprocess == 'return'){
                $status= 'approve-return';
            }else if($subprocess == 'void'){
                $status= 'approve-void';
            }else if($subprocess == 'approve'){
                $status= 'approve-approve';
            }else if(in_array($subprocess,['unhold','unreturn'])){
                $status = GenericMethod::getStatus($process,$transaction);
            }

            if(!isset($status)){
                return GenericMethod::resultResponse('invalid-access','','');
            }

            $state= $subprocess;

            GenericMethod::approveTransaction($model,$transaction_id,$tag_no,$reason_remarks,$date_now,$reason_id,$status,$distributed_to );
            GenericMethod::updateTransactionStatus($transaction_id,$request_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month,$distributed_id,$distributed_name,$approver_id,$approver_name);

        }else if($process == 'transmit'){
            
            $transaction_type =  $request['transaction_type'];
            $model = new Transmit;

            if($subprocess == 'receive'){
                $status= 'transmit-receive';
            }else if($subprocess == 'transmit'){
                $status= 'transmit-transmit';
            }
            if(!isset($status)){
                return GenericMethod::resultResponse('invalid-access','','');
            }
            $state= $subprocess;
            

            GenericMethod::transmitTransaction($model,$transaction_id,$tag_no,$reason_remarks,$date_now,$reason_id,$status,$distributed_to,$transaction_type );
            GenericMethod::updateTransactionStatus($transaction_id,$request_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month,$distributed_id,$distributed_name,$approver_id,$approver_name,$transaction_type);

        }else if($process == 'cheque'){
            $account_titles = $cheque_account_titles;
            $cheques = $cheque_cheques;

            $model = new Treasury;
            if($subprocess == 'receive'){
                $status= 'cheque-receive';
            }else if($subprocess == 'hold'){
                $status= 'cheque-hold';
            }else if($subprocess == 'return'){
                $status= 'cheque-return';
            }else if($subprocess == 'void'){
                $status= 'cheque-void';
            }else if($subprocess == 'cheque'){
                $not_valid =  GenericMethod::validateCheque($id,$cheques);
                if($not_valid){
                    return GenericMethod::resultResponse('cheque-no-exist','Cheque_no number already exist.',[]); 
                }
                $status= 'cheque-cheque';
            }else if($subprocess == 'release'){
               $cheques = GenericMethod::get_cheque_details_latest($id);
               $cheques =  array_values(array_filter($cheques,function ($item){
                   return  $item['transaction_type'] == "new";
                }));
                $account_titles = GenericMethod::get_account_title_details_latest($id);
                $account_titles =  array_values(array_filter($account_titles,function ($item){
                    return  $item['transaction_type'] == "new";
                }));
                
                $status= 'cheque-release';
            }else if($subprocess == 'reverse'){
                $old_cheques = GenericMethod::get_cheque_details($id);
                $old_cheques = isset($old_cheques)? $old_cheques : []; 
                $old_account_titles = GenericMethod::get_account_title_details($id);
                $old_account_titles = isset($old_account_titles)? $old_account_titles : []; 

                $old_cheques_with_type = array_map(function($item){
                    return array_merge($item,array("transaction_type"=>"old"));
                },$old_cheques);

               $reverse_cheques_with_type = array_map(function($item){
                    return array_merge($item,array("transaction_type"=>"reverse"));
                },$old_cheques);
            
                $new_cheques_with_type = array_map(function($item){
                    return array_merge($item,array("transaction_type"=>"new"));
                },$cheques);

                $old_account_titles_with_type = array_map(function($item){
                    return array_merge($item,array("transaction_type"=>"old"));
                },$old_account_titles);

               $reverse_account_titles_with_type = array_map(function($item){
                    return array_merge($item,array("transaction_type"=>"reverse"));
                },$old_account_titles);
            
                $new_account_titles_with_type = array_map(function($item){
                    return array_merge($item,array("transaction_type"=>"new"));
                },$account_titles);

                $cheques = array_merge($old_cheques_with_type, $reverse_cheques_with_type, $new_cheques_with_type);
                $account_titles = array_merge($old_account_titles_with_type, $reverse_account_titles_with_type, $new_account_titles_with_type);

                $new_cheque_with_type_amount = array_filter($cheques, function ($cheque){
                    return $cheque['transaction_type']=="new";
                });

                $new_cheque_amount =  array_values($new_cheque_with_type_amount);
                $new_cheque_amount = array_sum(array_column($new_cheque_amount,'amount'));
                
                $status= 'cheque-reverse';
            }else if(in_array($subprocess,['unhold','unreturn'])){
                $status = GenericMethod::getStatus($process,$transaction);
            }else if($subprocess == 'file'){
                $status= 'cheque-file';
            }
            
            if(!isset($status)){
                return GenericMethod::resultResponse('invalid-access','','');
            }

            $state= $subprocess;
            
            $document_amount = $transaction['document_amount'];
            if(!$document_amount){
                $document_amount = $transaction['referrence_amount'];
            }
            
            if(!empty($cheques)){
               $cheque_amount= array_sum(array_column($cheques,'amount'));
               $cheque_amount = isset($new_cheque_amount)?$new_cheque_amount:$cheque_amount;

                if($document_amount != $cheque_amount){
                    return GenericMethod::resultResponse('not-equal','Document and cheque',[]); 
                }
            }

            if(!empty($account_titles)){
                
                $debit_entries_amount = array_filter($account_titles, function ($account_title){
                    if(isset($account_title['transaction_type'])){
                        return strtolower($account_title['entry'])!="credit" && $account_title['transaction_type']=="new";
                    }
                    return strtolower($account_title['entry'])!="credit" ;
                });
                
                $credit_entries_amount = array_filter($account_titles, function ($account_title){
                    if(isset($account_title['transaction_type'])){
                        return strtolower($account_title['entry'])!="debit" && $account_title['transaction_type']=="new";
                    }
                    return strtolower($account_title['entry'])!="debit" ;
                });
                
                $debit_amount= array_sum(array_column($debit_entries_amount,'amount'));
                $credit_amount= array_sum(array_column($credit_entries_amount,'amount'));
                
                if($debit_amount != $credit_amount){
                    return GenericMethod::resultResponse('not-equal','Total debit and credit',[]); 
                }
    
                if($cheque_amount != $debit_amount){
                    return GenericMethod::resultResponse('not-equal','Cheque and account title',[]); 
                }
            }
            
            GenericMethod::chequeTransaction($model,$transaction_id,$tag_no,$reason_remarks,$date_now,$reason_id,$status,$cheques,$account_titles );
            GenericMethod::updateTransactionStatus($transaction_id,$request_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month,$distributed_id,$distributed_name,$approver_id,$approver_name);

        }else if($process == 'release'){
            $model = new Release;
            if($subprocess == 'receive'){
                $status= 'release-receive';
            }else if($subprocess == 'return'){
                $status= 'release-return';
            }else if($subprocess == 'release'){
                $status= 'release-release';
            }else if(in_array($subprocess,['unreturn'])){
                $status = GenericMethod::getStatus($process,$transaction);
            }
            if(!isset($status)){
                return GenericMethod::resultResponse('invalid-access','','');
            }
            $state= $subprocess;
            GenericMethod::releaseTransaction($model,$transaction_id,$tag_no,$remarks,$date_now,$reason_id,$reason_remarks,$status,$distributed_to );
            GenericMethod::updateTransactionStatus($transaction_id,$request_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month,$distributed_id,$distributed_name,$approver_id,$approver_name);
        }else if($process == 'file'){
            $model = new File;
            if($subprocess == 'receive'){
                $status= 'file-receive';
            }else if($subprocess == 'return'){
                $status= 'file-return';
            }else if($subprocess == 'file'){
                $status= 'file-file';
            }else if(in_array($subprocess,['unreturn'])){
                $status = GenericMethod::getStatus($process,$transaction);
            }
            
            if(!isset($status)){
                return GenericMethod::resultResponse('invalid-access','','');
            }

            $state= $subprocess;
            GenericMethod::fileTransaction($model,$transaction_id,$tag_no,$reason_remarks,$date_now,$reason_id,$status,$receipt_type,$percentage_tax,$withholding_tax,$net_amount,$voucher_no,[],[] );
            GenericMethod::updateTransactionStatus($transaction_id,$request_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month,$distributed_id,$distributed_name,$approver_id,$approver_name);

        }else if($process == 'reverse'){
            $model = new Reverse;
            $role = Auth::user()->role;

            if($role == "AP Associate" || $role == "AP Specialist"){
                if($subprocess == 'receive-approver'){
                    $status= 'reverse-receive-approver';
                }else if($subprocess == 'approve'){
                    $status= 'reverse-approve';
                }

                if(!isset($status)){
                    return GenericMethod::resultResponse('invalid-access','','');
                }
                
            }else{
                if($subprocess == 'request'){
                    $status= 'reverse-request';
                }else if($subprocess == 'receive-requestor'){
                    $status= 'reverse-receive-requestor';
                }else if($subprocess == 'return'){
                    $status= 'reverse-return';
                }
            }
        
            if(!isset($status)){
                return GenericMethod::resultResponse('invalid-access','','');
            }
            $state= $subprocess;
            GenericMethod::reverseTransaction($model,$transaction_id,$tag_no,$reason_remarks,$date_now,$reason_id,$status,$role,$distributed_to);
            GenericMethod::updateTransactionStatus($transaction_id,$request_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month,$distributed_id,$distributed_name,$approver_id,$approver_name);
            return GenericMethod::resultResponse($state,'','');
        }else if($process == 'clear'){
            $account_titles = $cheque_account_titles;
            $model = new Clear;
            if($subprocess == 'receive'){
                $status= 'clear-receive';
            }else if($subprocess == 'clear'){
                $status= 'clear-clear';
            }
            
            if(!isset($status)){
                return GenericMethod::resultResponse('invalid-access','','');
            }

            $state= $subprocess;
            GenericMethod::clearTransaction($model,$tag_no,$date_now,$status,$account_titles,$subprocess,$date_cleared );
            GenericMethod::updateTransactionStatus($transaction_id,$request_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month,$distributed_id,$distributed_name,$approver_id,$approver_name);

        }

        return GenericMethod::resultResponse($state,'','');
    }

    public static function validateVoucherNo($request){
        $voucher_no = $request['voucher_no'];
        $id = $request['id'];
        $transaction = Transaction::where('voucher_no',$voucher_no)->where('id','<>',$id)->where('state','!=','void')->exists();

        if($transaction){
            $errorMessage = GenericMethod::resultLaravelFormat('voucher.no',["Voucher number already exist."]);
            return GenericMethod::resultResponse('invalid','',$errorMessage);   
        }
        return GenericMethod::resultResponse('success-no-content','',[]); 
    }

    public static function validateChequeNo($request){
        $cheque_no = $request['cheque_no'];
        $id = $request['id'];

       $transaction = Transaction::with('cheques.cheques')
       ->whereHas('cheques.cheques', function ($query) use ($cheque_no){
            $query->where('cheque_no',$cheque_no);
       })
       ->where('id','<>',$id)
       ->exists();

        if($transaction){
            $errorMessage = GenericMethod::resultLaravelFormat('cheque.no',["Cheque number already exist."]);
            return GenericMethod::resultResponse('invalid','',$errorMessage);   
        }
        return GenericMethod::resultResponse('success-no-content','',[]); 
    }

    public static function transfer($request,$id){
      $transaction = Transaction::where('id',$id)->first();
      $transaction_id = $transaction->transaction_id;
      $tag_no = $transaction->tag_no;
      $date_now= Carbon::now('Asia/Manila')->format('Y-m-d');
      $status= 'voucher-transfer';
     
       $model = new Associate;
       $user_info = Auth::user();
       $from_user_id = $user_info->id;
       $from_full_name = GenericMethod::getFullnameNoMiddle($user_info->first_name,$user_info->last_name,$user_info->suffix);
       $to_user_id = $request['transfer']['id'];
       $to_full_name = $request['transfer']['name'];
       
       GenericMethod::transferTransaction($id,$from_user_id,$from_full_name,$to_user_id,$to_full_name,$transaction_id,$tag_no);
       GenericMethod::voucherTransaction($model,$transaction_id,$tag_no,
       $reason_remarks = NULL,$date_now,$reason_id= NULL,$status,
       $receipt_type = NULL,$voucher_no = NULL,$approver = NULL,$account_titles = NULL );
       return GenericMethod::resultResponse('transfer','','');
    }

}
