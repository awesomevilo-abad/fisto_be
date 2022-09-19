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
use Carbon\Carbon;

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
            )->find($id);

        $transaction_id = $transaction->transaction_id;
        $remarks = $transaction->remarks;
        $users_id = $transaction->users_id;

        $tag_no = $transaction->tag_no;
        if(($transaction->tag_no)==0 AND ($subprocess == 'tag')){
            $tag_no = GenericMethod::generateTagNo();
        }

        
        $previous_voucher_transaction = Transaction::with('transaction_voucher.account_title')->where('transaction_id',$transaction['transaction_id'])->latest()->first();
        $previous_cheque_transaction = Transaction::with('transaction_cheque.account_title')->where('transaction_id',$transaction['transaction_id'])->latest()->first();
        
        $previous_receipt_type = ($previous_voucher_transaction['transaction_voucher']->isEmpty())?NULL:$previous_voucher_transaction['transaction_voucher']->first()['receipt_type'];
        $previous_percentage_tax = ($previous_voucher_transaction['transaction_voucher']->isEmpty())?NULL:$previous_voucher_transaction['transaction_voucher']->first()['percentage_tax'];
        $previous_withholding_tax = ($previous_voucher_transaction['transaction_voucher']->isEmpty())?NULL:$previous_voucher_transaction['transaction_voucher']->first()['withholding_tax'];
        $previous_net_amount = ($previous_voucher_transaction['transaction_voucher']->isEmpty())?NULL:$previous_voucher_transaction['transaction_voucher']->first()['net_amount'];
        $previous_voucher_no = ($previous_voucher_transaction['transaction_voucher']->isEmpty())?NULL:$previous_voucher_transaction['voucher_no'];
        $previous_voucher_month = ($previous_voucher_transaction['transaction_voucher']->isEmpty())?NULL:$previous_voucher_transaction['voucher_month'];
        $previous_approver = array("approver"=>array("id"=>$previous_voucher_transaction['transaction_voucher']->first()['approver_id'],"name"=>$previous_voucher_transaction['transaction_voucher']->first()['approver_name']));

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
        $accounts=  isset($request['accounts'])?$request['accounts']:null;
        $cheque_cheques=  isset($request['cheques'])?$request['accounts']:null;

        $receipt_type = GenericMethod::with_previous_transaction($request['tax']['receipt_type'],$previous_receipt_type);
        $percentage_tax = GenericMethod::with_previous_transaction($request['tax']['percentage_tax'],$previous_percentage_tax);
        $withholding_tax = GenericMethod::with_previous_transaction($request['tax']['withholding_tax'],$previous_withholding_tax);
        $net_amount = GenericMethod::with_previous_transaction($request['tax']['net_amount'],$previous_net_amount);
        $voucher_no = GenericMethod::with_previous_transaction($request['voucher']['no'],$previous_voucher_no);
        $voucher_month = GenericMethod::with_previous_transaction($request['voucher']['month'],$previous_voucher_month);
        $approver = GenericMethod::with_previous_transaction($request['approver'],$previous_approver);
        $voucher_account_titles=  GenericMethod::with_previous_transaction($accounts,$voucher_account_title);

        $cheque_cheques = GenericMethod::with_previous_transaction($cheque_cheques,$previous_cheque_transaction_cheque['cheques']);
        $cheque_account_titles = GenericMethod::with_previous_transaction($accounts,$previous_cheque_transaction_account_title['accounts']);

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
            GenericMethod::updateTransactionStatus($transaction_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month);
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
            $state= $subprocess;
            GenericMethod::tagTransaction($model,$transaction_id,$remarks,$date_now,$reason_id,$reason_remarks,$status,$distributed_to );
            GenericMethod::updateTransactionStatus($transaction_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month);
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
                GenericMethod::voucherNoValidation($voucher_no,$id);
                $status= 'voucher-voucher';
            }else if(in_array($subprocess,['unhold','unreturn'])){
                $status = GenericMethod::getStatus($process,$transaction);
            }
            $state= $subprocess;

            $document_amount = $transaction['document_amount'];
            if(!$document_amount){
                $document_amount = $transaction['referrence_amount'];
            }
            
            if(!empty($account_titles)){
                $debit_entries_amount = array_filter($account_titles, function ($account_title){
                    return $account_title['entry']!="credit";
                });
                
                $credit_entries_amount = array_filter($account_titles, function ($account_title){
                    return $account_title['entry']!="debit";
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

            GenericMethod::voucherTransaction($model,$transaction_id,$tag_no,$reason_remarks,$date_now,$reason_id,$status,$receipt_type,$percentage_tax,$withholding_tax,$net_amount,$voucher_no,$approver,$account_titles );
            GenericMethod::updateTransactionStatus($transaction_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month);

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
            $state= $subprocess;

            GenericMethod::approveTransaction($model,$transaction_id,$tag_no,$reason_remarks,$date_now,$reason_id,$status,$distributed_to );
            GenericMethod::updateTransactionStatus($transaction_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month);

        }else if($process == 'transmit'){
            $model = new Transmit;
            if($subprocess == 'receive'){
                $status= 'transmit-receive';
            }else if($subprocess == 'transmit'){
                $status= 'transmit-transmit';
            }
            $state= $subprocess;

            GenericMethod::transmitTransaction($model,$transaction_id,$tag_no,$reason_remarks,$date_now,$reason_id,$status,$distributed_to );
            GenericMethod::updateTransactionStatus($transaction_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month);

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
                    $errorMessage = GenericMethod::resultLaravelFormat('cheque.no',["Cheque number already exist."]);
                    return GenericMethod::resultResponse('invalid','',$errorMessage);   
                }
                $status= 'cheque-cheque';
            }else if($subprocess == 'release'){
                $cheques = GenericMethod::get_cheque_details($id);
                $account_titles = GenericMethod::get_account_title_details($id);
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
                        return $account_title['entry']!="credit" && $account_title['transaction_type']=="new";
                    }
                    return $account_title['entry']!="credit" ;
                });
                
                $credit_entries_amount = array_filter($account_titles, function ($account_title){
                    if(isset($account_title['transaction_type'])){
                        return $account_title['entry']!="debit" && $account_title['transaction_type']=="new";
                    }
                    return $account_title['entry']!="debit" ;
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
            GenericMethod::updateTransactionStatus($transaction_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month);

        }else if($process == 'file'){
            $model = new Filing;
            if($subprocess == 'receive'){
                $status= 'file-receive';
                $state= 'receive';
            }else if($subprocess == 'file'){
                $status= 'file-file';
                $state= 'file';
            }else if($subprocess == 'accept'){
                $status= 'file-accept';
                $state= 'accept';
            }else if($subprocess == 'return'){
                $status= 'file-return';
                $state= 'return';
            }
            
            // //CREATE
            // Filing::Create([
            //     "tag_id"=>$tag_no,
            //     "date_received"=>$date_received,
            //     "status"=>$status,
            //     "date_status"=>$date_status,
            //     "reason_id"=>$reason_id,
            //     "remarks"=>$reason_remarks
            // ]);

            // GenericMethod::tagTransaction($model,$transaction_id,$reason_remarks,$date_now,$reason_id,$status,$distributed_to );
            // GenericMethod::updateTransactionStatus($transaction_id,$tag_no,$status,$state);
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

       $transaction = Transaction::with('tag.cheque.cheques')
       ->whereHas('tag.cheque.cheques', function ($query) use ($cheque_no){
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


    // public static function getStatusAndTableFromProcessAndSubProcess($process,$subprocess){
    //     if($process == 'tag'){
    //         if($subprocess == 'pending'){
    //             $status = ["pending","received (tagging)","unhold (tagging)"];
    //         }else if($subprocess == 'hold'){
    //             $status = ["hold (tagging)"];
    //         }else if($subprocess == 'unhold'){
    //             $status = ["unhold (tagging)"];
    //         }else if($subprocess == 'cancel'){
    //             $status = ["cancelled (tagging)"];
    //         }else if($subprocess == 'tag'){
    //             $status = ["tagged (tagging)"];
    //         }
    //         $table = 'taggings';
    //     }else if($process == 'gas'){

    //         if($subprocess == 'pending'){
    //             $status = ["tagged (tagging)", "received (gas)","unhold (gas)"];
    //         }else if($subprocess == 'hold'){
    //             $status = ["hold (gas)"];
    //         }else if($subprocess == 'return'){
    //             $status = ["returned (gas)"];
    //         }else if($subprocess == 'approve'){
    //             $status = ["approved (gas)"];
    //         }
    //         $table = 'gases';
    //     }else if($process == 'filing'){

    //         if($subprocess == 'pending'){
    //             $status = ["approved (gas)","unhold (filing)"];
    //         }else if($subprocess == 'distribute'){
    //             $status = ["distributed (filing)"];
    //         }else if($subprocess == 'hold'){
    //             $status = ["hold (filing)"];
    //         }else if($subprocess == 'unhold'){
    //             $status = ["unhold (filing)"];
    //         }
    //         $table = 'filings';
    //     }else if($process == 'create voucher'){
    //         if($subprocess == 'pending'){
    //             $status = ["distributed (filing)","unhold (create-voucher)"];
    //         }else if($subprocess == 'approve'){
    //             $status = ["approved (create-voucher)"];
    //         }else if($subprocess == 'hold'){
    //             $status = ["hold (create-voucher)"];
    //         }else if($subprocess == 'unhold'){
    //             $status = ["unhold (create-voucher)"];
    //         }else if($subprocess == 'cancel'){
    //             $status = ["cancelled (create-voucher)"];
    //         }else if($subprocess == 'return'){
    //             $status = ["returned (create-voucher)"];
    //         }
    //         $table = 'associates';
    //     }else if($process == 'approve voucher'){
    //         if($subprocess == 'pending'){
    //             $status = ["approved (create-voucher)","unhold (approve-voucher)"];
    //         }else if($subprocess == 'approve'){
    //             $status = ["approved (approve-voucher)"];
    //         }else if($subprocess == 'hold'){
    //             $status = ["hold (approve-voucher)"];
    //         }else if($subprocess == 'unhold'){
    //             $status = ["unhold (approve-voucher)"];
    //         }else if($subprocess == 'cancel'){
    //             $status = ["cancelled (approve-voucher)"];
    //         }
    //         $table = 'specialists';
    //     }else if($process == 'matching'){
    //         if($subprocess == 'pending'){
    //             $status = ["approved (approve-voucher)"];
    //         }else if($subprocess == 'match'){
    //             $status = ["matched (matching)"];
    //         }else if($subprocess == 'return'){
    //             $status = ["returned (matching)"];
    //         }
    //         $table = 'matches';
    //     }else if($process == 'returned voucher'){
    //         if($subprocess == 'pending'){
    //             $status = ["matched (matching)"];
    //         }else if($subprocess == 'distribute'){
    //             $status = ["distributed (return-voucher)"];
    //         }else if($subprocess == 'cancel'){
    //             $status = ["cancelled (return-voucher)"];
    //         }

    //         $table = 'return_vouchers';
    //     }else if($process == 'approver'){
    //         if($subprocess == 'pending'){
    //             $status = ["distributed (return-voucher)"];
    //         }else if($subprocess == 'hold'){
    //             $status = ["hold (approver)"];
    //         }else if($subprocess == 'unhold'){
    //             $status = ["unhold (approver)"];
    //         }else if($subprocess == 'approve'){
    //             $status = ["approved (approver)"];
    //         }

    //         $table = 'approvers';
    //     }else if($process == 'transmitted'){
    //         if($subprocess == 'pending'){
    //             $status = ["approved (approver)"];
    //         }else if($subprocess == 'transmit'){
    //             $status = ["transmitted (transmit)"];
    //         }

    //         $table = 'transmittal';
    //     }else if($process == 'create cheque'){
    //         if($subprocess == 'pending'){
    //             $status = ["approved (approver)","unhold (approver)"];
    //         }else if($subprocess == 'hold'){
    //             $status = ["hold (create-cheque)"];
    //         }else if($subprocess == 'unhold'){
    //             $status = ["unhold (create-cheque)"];
    //         }else if($subprocess == 'create'){
    //             $status = ["created (create-cheque)"];
    //         }else if($subprocess == 'release'){
    //             $status = ["released (create-cheque)"];
    //         }

    //         $table = 'cheque_creations';
    //     }else if($process == 'release cheque'){
    //         if($subprocess == 'pending'){
    //             $status = ["approved (approver)","unhold (approver)"];
    //         }else if($subprocess == 'return'){
    //             $status = ["returned (release-cheque)"];
    //         }else if($subprocess == 'release'){
    //             $status = ["released (release-cheque)"];
    //         }
    //         $table = 'cheque_releaseds';
    //     }else if($process == 'clear cheque'){
    //         if($subprocess == 'pending'){
    //             $status = ["released (release-cheque)"];
    //         }else if($subprocess == 'clear'){
    //             $status = ["cleared (clear-cheque)"];
    //         }

    //         $table = 'cheque_clearings';
    //     }else{
    //         $status = "status not registered";
    //         $table = 'not registered';
    //     }
    //     return array("status"=>$status,"table"=>$table);
    // }

    // public static function pullRequest($process,$subprocess){

    //     $result = TransactionFlow::getStatusAndTableFromProcessAndSubProcess($process,$subprocess);
    //     $transactions = DB::table('transactions')
    //     ->whereIn('status',$result['status'])
    //     ->orderBy('id','desc')->get();

    //     $transaction_format =  GenericMethod::getTransactionFormat($transactions, $result['table']);

    //     return $transaction_format;
    // }

    // public static function pullSingleRequest($process,$subprocess,$id){
    //     $result = TransactionFlow::getStatusAndTableFromProcessAndSubProcess($process,$subprocess);

    //     $transactions = Transaction::where('id',$id)
    //     ->whereIn('status',$result['status'])
    //     ->get()->first();


    //     return $transaction_format =  GenericMethod::getTransactionFormat($transactions, $result['table']);

    //     return $transaction_format;
    // }

    // public static function receivedRequest($request, $id){


    //     $process =  $request['process'];
    //     $subprocess =  $request['subprocess'];
    //     $transactions = TransactionFlow::pullSingleRequest($process,$subprocess,$id);

    //     return $transactions;
    //     $transaction_id =  $transactions['transaction_id'];
    //     $tag_no =  $transactions['tag_no'];
    //     $description = $request['description'];
    //     $reason_id = $request['reason_id'];
    //     $remarks = $request['remarks'];
    //     $date_status = date('Y-m-d H:i:s');

    //     if(!isset($request['date_received']) || empty($request['date_received'])){
    //         $date_received = date('Y-m-d H:i:s');
    //     }else{
    //         $date_received = $request['date_received'];
    //     }
    //     if($process == 'tag'){
    //         if($subprocess == 'receive'){
    //             $status = 'received (tagging)';
    //             $tag_no = 0;
    //         }else if($subprocess == 'tag'){
    //             $status = 'tagged (tagging)';

    //             if($max_tag_no == 0 ){
    //                 $tag_no = 1;
    //             }else{
    //                 $tag_no = $max_tag_no + 1;
    //             }
    //         }else if($subprocess == 'hold'){
    //             $status = 'hold (tagging)';
    //             $tag_no = 0;
    //         }else if($subprocess == 'unhold'){
    //             $status = 'unhold (tagging)';
    //             $tag_no = 0;
    //         }else if($subprocess == 'cancel'){
    //             $status = 'cancelled (tagging)';
    //             $tag_no = 0;
    //         }else{
    //             $status = 'Not Registered';
    //             $tag_no = 0;
    //         }

    //         return $tag_no;

    //         //CREATE
    //         Tagging::Create([
    //             "transaction_id"=>$transaction_id,
    //             "description"=>$description,
    //             "date_received"=>$date_received,
    //             "status"=>$status,
    //             "date_status"=>$date_status,
    //             "reason_id"=>$reason_id,
    //             "remarks"=>$remarks
    //         ]);
    //         // UPDATE
    //         GenericMethod::updateTransactionStatus($transaction_id,$tag_no,$status);

    //     }else if($process == 'gas'){

    //         if(isset($transactions[0]->receipt_type)){
    //             $receipt_type = $transactions[0]->receipt_type;
    //         }else{
    //             $receipt_type = $request['receipt_type'];
    //         }
    //         $witholding_tax = $request['witholding_tax'];
    //         $percentage_tax = $request['percentage_tax'];

    //         if($subprocess == 'receive'){
    //             $status = 'received (gas)';
    //         }else if($subprocess == 'approve'){
    //             $status = 'approved (gas)';
    //         }else if($subprocess == 'hold'){
    //             $status = 'hold (gas)';
    //         }else if($subprocess == 'unhold'){
    //             $status = 'unhold (gas)';
    //         }else if($subprocess == 'return'){
    //             $status = 'returned (gas)';
    //         }else{
    //             $status = 'Not Registered';
    //         }

    //         //CREATE
    //         Gas::Create([
    //             "tag_id"=>$tag_no,
    //             "receipt_type"=>$receipt_type,
    //             "date_received"=>$date_received,
    //             "status"=>$status,
    //             "date_status"=>$date_status,
    //             "witholding_tax"=>$witholding_tax,
    //             "percentage_tax"=>$percentage_tax,
    //             "reason_id"=>$reason_id,
    //             "remarks"=>$remarks
    //         ]);
    //         // UPDATE
    //         GenericMethod::updateTransactionStatus($transaction_id,$status);


    //     }else if($process == 'filing'){

    //         $distributed_to = $request['distributed_to'];

    //         if($subprocess == 'receive'){
    //             $status = 'received (filing)';
    //         }else if($subprocess == 'distribute'){
    //             $status = 'distributed (filing)';
    //         }else if($subprocess == 'hold'){
    //             $status = 'hold (filing)';
    //         }else if($subprocess == 'unhold'){
    //             $status = 'unhold (filing)';
    //         }else{
    //             $status = 'Not Registered';
    //         }

    //         //CREATE
    //         Filing::Create([
    //             "tag_id"=>$tagging_tag_id,
    //             "date_received"=>$date_received,
    //             "status"=>$status,
    //             "date_status"=>$date_status,
    //             "distributed_to"=>$distributed_to,
    //             "reason_id"=>$reason_id,
    //             "remarks"=>$remarks
    //         ]);
    //         // UPDATE
    //         GenericMethod::updateTransactionStatus($transaction_id,$status);

    //     }else if($process == 'create voucher'){

    //         if($subprocess == 'receive'){
    //             $status = 'received (create-voucher)';
    //         }else if($subprocess == 'approve'){
    //             $status = 'approved (create-voucher)';
    //         }else if($subprocess == 'hold'){
    //             $status = 'hold (create-voucher)';
    //         }else if($subprocess == 'unhold'){
    //             $status = 'unhold (create-voucher)';
    //         }else if($subprocess == 'cancel'){
    //             $status = 'cancelled (create-voucher)';
    //         }else if($subprocess == 'return'){
    //             $status = 'returned (create-voucher)';
    //         }else{
    //             $status = 'Not Registered';
    //         }

    //         //CREATE
    //         Associate::Create([
    //             "tag_id"=>$tagging_tag_id,
    //             "date_received"=>$date_received,
    //             "status"=>$status,
    //             "date_status"=>$date_status,
    //             "reason_id"=>$reason_id,
    //             "remarks"=>$remarks
    //         ]);
    //         // UPDATE
    //         GenericMethod::updateTransactionStatus($transaction_id,$status);

    //     }else if($process == 'approve voucher'){

    //         $month_in =  $request['month_in'];
    //         $voucher_no = $request['voucher_no'];

    //         if($subprocess == 'receive'){
    //             $status = 'received (approve-voucher)';
    //         }else if($subprocess == 'approve'){
    //             $status = 'approved (approve-voucher)';
    //         }else if($subprocess == 'hold'){
    //             $status = 'hold (approve-voucher)';
    //         }else if($subprocess == 'unhold'){
    //             $status = 'unhold (approve-voucher)';
    //         }else if($subprocess == 'cancel'){
    //             $status = 'cancelled (approve-voucher)';
    //         }else{
    //             $status = 'Not Registered';
    //         }

    //         //CREATE
    //         Specialist::Create([
    //             "tag_id"=>$tagging_tag_id,
    //             "date_received"=>$date_received,
    //             "month_in"=>$month_in,
    //             "voucher_no"=>$voucher_no,
    //             "status"=>$status,
    //             "date_status"=>$date_status,
    //             "reason_id"=>$reason_id,
    //             "remarks"=>$remarks
    //         ]);
    //         // UPDATE
    //         GenericMethod::updateTransactionStatus($transaction_id,$status);

    //     }else if($process == 'matching'){
    //         if($subprocess == 'receive'){
    //             $status = 'received (matching)';
    //         }else if($subprocess == 'match'){
    //             $status = 'matched (matching)';
    //         }else if($subprocess == 'return'){
    //             $status = 'returned (matching)';
    //         }else{
    //             $status = 'Not Registered';
    //         }

    //         //CREATE
    //         Match::Create([
    //             "tag_id"=>$tagging_tag_id,
    //             "date_received"=>$date_received,
    //             "status"=>$status,
    //             "date_status"=>$date_status,
    //             "reason_id"=>$reason_id,
    //             "remarks"=>$remarks
    //         ]);
    //         // UPDATE
    //         GenericMethod::updateTransactionStatus($transaction_id,$status);

    //     }else if($process == 'return voucher'){

    //         $distributed_to = $request['distributed_to'];

    //         if($subprocess == 'receive'){
    //             $status = 'received (return-voucher)';
    //         }else if($subprocess == 'distribute'){
    //             $status = 'distributed (return-voucher)';
    //         }else if($subprocess == 'cancel'){
    //             $status = 'cancelled (return-voucher)';
    //         }else{
    //             $status = 'Not Registered';
    //         }

    //         //CREATE
    //         ReturnVoucher::Create([
    //             "tag_id"=>$tagging_tag_id,
    //             "date_received"=>$date_received,
    //             "distributed_to"=>$distributed_to,
    //             "status"=>$status,
    //             "date_status"=>$date_status,
    //             "reason_id"=>$reason_id,
    //             "remarks"=>$remarks
    //         ]);
    //         // UPDATE
    //         GenericMethod::updateTransactionStatus($transaction_id,$status);

    //     }else if($process == 'approver'){

    //         if($subprocess == 'receive'){
    //             $status = 'received (approver)';
    //         }else if($subprocess == 'approve'){
    //             $status = 'approved (approver)';
    //         }else if($subprocess == 'hold'){
    //             $status = 'hold (approver)';
    //         }else if($subprocess == 'unhold'){
    //             $status = 'unhold (approver)';
    //         }else{
    //             $status = 'Not Registered';
    //         }
    //         //CREATE
    //         Approver::Create([
    //             "tag_id"=>$tagging_tag_id,
    //             "date_received"=>$date_received,
    //             "status"=>$status,
    //             "date_status"=>$date_status,
    //             "reason_id"=>$reason_id,
    //             "remarks"=>$remarks
    //         ]);
    //         // UPDATE
    //         GenericMethod::updateTransactionStatus($transaction_id,$status);

    //     }else if($process == 'transmit'){

    //         if($subprocess == 'receive'){
    //             $status = 'received (transmit)';
    //         }else if($subprocess == 'transmit'){
    //             $status = 'transmitted (transmit)';
    //         }else{
    //             $status = 'Not Registered';
    //         }
    //         //CREATE
    //         ChequeCreation::Create([
    //             "tag_id"=>$tagging_tag_id,
    //             "date_received"=>$date_received,
    //             "status"=>$status,
    //             "date_status"=>$date_status,
    //             "reason_id"=>$reason_id,
    //             "remarks"=>$remarks
    //         ]);
    //         // UPDATE
    //         GenericMethod::updateTransactionStatus($transaction_id,$status);

    //     }else if($process == 'create cheque'){

    //         if($subprocess == 'receive'){
    //             $status = 'received (create-cheque)';
    //         }else if($subprocess == 'create'){
    //             $status = 'created (create-cheque)';
    //         }else if($subprocess == 'hold'){
    //             $status = 'hold (create-cheque)';
    //         }else if($subprocess == 'unhold'){
    //             $status = 'unhold (create-cheque)';
    //         }else if($subprocess == 'release'){
    //             $status = 'released (create-cheque)';
    //         }else{
    //             $status = 'Not Registered';
    //         }
    //         //CREATE
    //         ChequeCreation::Create([
    //             "tag_id"=>$tagging_tag_id,
    //             "date_received"=>$date_received,
    //             "status"=>$status,
    //             "date_status"=>$date_status,
    //             "reason_id"=>$reason_id,
    //             "remarks"=>$remarks
    //         ]);
    //         // UPDATE
    //         GenericMethod::updateTransactionStatus($transaction_id,$status);

    //     }else if($process == 'release cheque'){

    //         if($subprocess == 'receive'){
    //             $status = 'received (release-cheque)';
    //         }else if($subprocess == 'release'){
    //             $status = 'released (release-cheque)';
    //         }else if($subprocess == 'return'){
    //             $status = 'returned (release-cheque)';
    //         }else{
    //             $status = 'Not Registered';
    //         }
    //         //CREATE
    //         ChequeReleased::Create([
    //             "tag_id"=>$tagging_tag_id,
    //             "date_received"=>$date_received,
    //             "status"=>$status,
    //             "date_status"=>$date_status,
    //             "reason_id"=>$reason_id,
    //             "remarks"=>$remarks
    //         ]);
    //         // UPDATE
    //         GenericMethod::updateTransactionStatus($transaction_id,$status);

    //     }else if($process == 'clear cheque'){

    //         if($subprocess == 'receive'){
    //             $status = 'received (clear-cheque)';
    //         }else if($subprocess == 'clear'){
    //             $status = 'cleared (clear-cheque)';
    //         }else{
    //             $status = 'Not Registered';
    //         }
    //         //CREATE
    //         ChequeClearing::Create([
    //             "tag_id"=>$tagging_tag_id,
    //             "date_received"=>$date_received,
    //             "status"=>$status,
    //             "date_status"=>$date_status,
    //             "reason_id"=>$reason_id,
    //             "remarks"=>$remarks
    //         ]);
    //         // UPDATE
    //         GenericMethod::updateTransactionStatus($transaction_id,$status);


    //     }else{
    //         return 'Invalid Process';
    //     }

    // }

    // public static function searchRequest($process,$subprocess,$search){

    //     $result = TransactionFlow::getStatusAndTableFromProcessAndSubProcess($process,$subprocess);
    //     $transactions = DB::table('transactions')
    //     ->whereIn('status',$result['status'])
    //     ->where(function($query) use($search){
    //         $query->where('id_no','like', '%'.$search.'%')
    //         ->orWhere('department','like', '%'.$search.'%')
    //         ->orWhere('document_date','like', '%'.$search.'%')
    //         ->orWhere('reason','like', '%'.$search.'%')
    //         ->orWhere('utilities_from','like', '%'.$search.'%')
    //         ->orWhere('utilities_to','like', '%'.$search.'%')
    //         ->orWhere('document_type','like', '%'.$search.'%')
    //         ->orWhere('category','like', '%'.$search.'%')
    //         ->orWhere('document_amount','like', '%'.$search.'%')
    //         ->orWhere('company','like', '%'.$search.'%')
    //         ->orWhere('supplier','like', '%'.$search.'%')
    //         ->orWhere('payment_type','like', '%'.$search.'%')
    //         ->orWhere('status','like', '%'.$search.'%')
    //         ->orWhere('remarks','like', '%'.$search.'%')
    //         ->orWhere('pcf_date','like', '%'.$search.'%')
    //         ->orWhere('pcf_letter','like', '%'.$search.'%')
    //         ->orWhere('tagging_tag_id','like', '%'.$search.'%')
    //         ->orWhere('transaction_id','like', '%'.$search.'%');
    //     })
    //     ->get();

    //     $transaction_format =  GenericMethod::getTransactionFormat($transactions, $result['table']);

    //     return $transaction_format;
    // }

}
