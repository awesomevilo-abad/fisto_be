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
use App\Models\RequestorLogs;
use App\Methods\GenericMethod;
use Carbon\Carbon;

class TransactionFlow{


    public static function updateInTransactionFlow ($request,$id) {
        
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
            )->find($id);

        $transaction_id = $transaction->transaction_id;
        $remarks = $transaction->remarks;
        $users_id = $transaction->users_id;

        $tag_no = $transaction->tag_no;
        if(($transaction->tag_no)==0 AND ($subprocess == 'receive')){
            $tag_no = GenericMethod::generateTagNo();
        }
 
        $reason_description= isset($request['reason']['description'])?$request['reason']['description']:null;
        $reason_remarks=  isset($request['reason']['remarks'])?$request['reason']['remarks']:null;
        $receipt_type= isset($request['tax']['receipt_type'])?$request['tax']['receipt_type']:null;
        $percentage_tax=  isset($request['tax']['percentage_tax'])?$request['tax']['percentage_tax']:null;
        $withholding_tax= isset($request['tax']['withholding_tax'])?$request['tax']['withholding_tax']:null;
        $net_amount=  isset($request['tax']['net_amount'])?$request['tax']['net_amount']:null;
        $voucher_no= isset($request['voucher']['no'])?$request['voucher']['no']:null;
        $voucher_month=  isset($request['voucher']['month'])?$request['voucher']['month']:null;
        $approver= isset($request['approver'])?$request['approver']:null;
        $distributed_to=  isset($request['distributed_to'])?$request['distributed_to']:null;
        $account_titles=  isset($request['accounts'])?$request['accounts']:null;

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
                $state= 'receive';
            }else if($subprocess == 'hold'){
                $status= 'tag-hold';
                $state= 'hold';
            }else if($subprocess == 'unhold'){
                $status= 'tag-unhold';
                $state= 'unhold';
            }else if($subprocess == 'return'){
                $status= 'tag-return';
                $state= 'return';
            }else if($subprocess == 'void'){
                $status= 'tag-void';
                $state= 'void';
            }else if($subprocess == 'tag'){
                $status= 'tag-tag';
                $state= 'tag';
            }

            GenericMethod::tagTransaction($model,$transaction_id,$remarks,$date_now,$reason_id,$reason_remarks,$status,$distributed_to );
            GenericMethod::updateTransactionStatus($transaction_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month);
        
        }else if($process == 'voucher'){
            $model = new Associate;
            if($subprocess == 'receive'){
                $status= 'voucher-receive';
                $state= 'receive';
            }else if($subprocess == 'hold'){
                $status= 'voucher-hold';
                $state= 'hold';
            }else if($subprocess == 'unhold'){
                $status= 'voucher-unhold';
                $state= 'unhold';
            }else if($subprocess == 'return'){
                $status= 'voucher-return';
                $state= 'return';
            }else if($subprocess == 'void'){
                $status= 'voucher-void';
                $state= 'void';
            }else if($subprocess == 'voucher'){
                $status= 'voucher-voucher';
                $state= 'voucher';
            }
            
            GenericMethod::voucherTransaction($model,$transaction_id,$tag_no,$reason_remarks,$date_now,$reason_id,$status,$receipt_type,$percentage_tax,$withholding_tax,$net_amount,$voucher_no,$approver,$account_titles );
            GenericMethod::updateTransactionStatus($transaction_id,$tag_no,$status,$state,$reason_id,$reason_description,$reason_remarks,$voucher_no,$voucher_month);
        }else if($process == 'approval'){
            $model = new Approver;
            if($subprocess == 'receive'){
                $status= 'approval-receive';
                $state= 'receive';
            }else if($subprocess == 'hold'){
                $status= 'approval-hold';
                $state= 'hold';
            }else if($subprocess == 'unhold'){
                $status= 'approval-unhold';
                $state= 'unhold';
            }else if($subprocess == 'return'){
                $status= 'approval-return';
                $state= 'return';
            }else if($subprocess == 'void'){
                $status= 'approval-void';
                $state= 'void';
            }else if($subprocess == 'approve'){
                $status= 'approval-approve';
                $state= 'approve';
            }

            GenericMethod::approveTransaction($model,$transaction_id,$reason_remarks,$date_now,$reason_id,$status,$distributed_to );
            GenericMethod::updateTransactionStatus($transaction_id,$tag_no,$status,$state);

        }else if($process == 'transmit'){
            $model = new Transmit;
            if($subprocess == 'receive'){
                $status= 'transmit-receive';
                $state= 'receive';
            }else if($subprocess == 'transmit'){
                $status= 'transmit-transmit';
                $state= 'transmit';
            }
            
            // //CREATE
            // Transmit::Create([
            //     "tag_id"=>$tag_no,
            //     "date_received"=>$date_received,
            //     "status"=>$status,
            //     "date_status"=>$date_status,
            //     "reason_id"=>$reason_id,
            //     "remarks"=>$reason_remarks
            // ]);

            // GenericMethod::tagTransaction($model,$transaction_id,$reason_remarks,$date_now,$reason_id,$status,$distributed_to );
            // GenericMethod::updateTransactionStatus($transaction_id,$tag_no,$status,$state);
        }else if($process == 'chequeCreate'){
            $model = new Treasury;
            if($subprocess == 'receive'){
                $status= 'chequeCreate-receive';
                $state= 'receive';
            }else if($subprocess == 'hold'){
                $status= 'chequeCreate-hold';
                $state= 'hold';
            }else if($subprocess == 'unhold'){
                $status= 'chequeCreate-unhold';
                $state= 'unhold';
            }else if($subprocess == 'return'){
                $status= 'chequeCreate-return';
                $state= 'return';
            }else if($subprocess == 'void'){
                $status= 'chequeCreate-void';
                $state= 'void';
            }else if($subprocess == 'approve'){
                $status= 'chequeCreate-create';
                $state= 'create';
            }else if($subprocess == 'release'){
                $status= 'chequeCreate-release';
                $state= 'release';
            }else if($subprocess == 'reverse'){
                $status= 'chequeCreate-reverse';
                $state= 'reverse';
            }
            
            // //CREATE
            // Approver::Create([
            //     "tag_id"=>$tag_no,
            //     "date_received"=>$date_received,
            //     "status"=>$status,
            //     "date_status"=>$date_status,
            //     "reason_id"=>$reason_id,
            //     "remarks"=>$reason_remarks
            // ]);

            // GenericMethod::tagTransaction($model,$transaction_id,$reason_remarks,$date_now,$reason_id,$status,$distributed_to );
            // GenericMethod::updateTransactionStatus($transaction_id,$tag_no,$status,$state);
        }else if($process == 'chequeRelease'){
            $model = new chequeReleased;
            if($subprocess == 'receive'){
                $status= 'chequeRelease-receive';
                $state= 'receive';
            }else if($subprocess == 'return'){
                $status= 'chequeRelease-return';
                $state= 'return';
            }else if($subprocess == 'requestReturn'){
                $status= 'chequeRelease-requestReturn';
                $state= 'requestReturn';
            }else if($subprocess == 'release'){
                $status= 'chequeRelease-release';
                $state= 'release';
            }
            
            // //CREATE
            // chequeReleased::Create([
            //     "tag_id"=>$tag_no,
            //     "date_received"=>$date_received,
            //     "status"=>$status,
            //     "date_status"=>$date_status,
            //     "reason_id"=>$reason_id,
            //     "remarks"=>$reason_remarks
            // ]);

            // GenericMethod::tagTransaction($model,$transaction_id,$reason_remarks,$date_now,$reason_id,$status,$distributed_to );
            // GenericMethod::updateTransactionStatus($transaction_id,$tag_no,$status,$state);
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

    public static function getStatusAndTableFromProcessAndSubProcess($process,$subprocess){
        if($process == 'tag'){
            if($subprocess == 'pending'){
                $status = ["pending","received (tagging)","unhold (tagging)"];
            }else if($subprocess == 'hold'){
                $status = ["hold (tagging)"];
            }else if($subprocess == 'unhold'){
                $status = ["unhold (tagging)"];
            }else if($subprocess == 'cancel'){
                $status = ["cancelled (tagging)"];
            }else if($subprocess == 'tag'){
                $status = ["tagged (tagging)"];
            }
            $table = 'taggings';
        }else if($process == 'gas'){

            if($subprocess == 'pending'){
                $status = ["tagged (tagging)", "received (gas)","unhold (gas)"];
            }else if($subprocess == 'hold'){
                $status = ["hold (gas)"];
            }else if($subprocess == 'return'){
                $status = ["returned (gas)"];
            }else if($subprocess == 'approve'){
                $status = ["approved (gas)"];
            }
            $table = 'gases';
        }else if($process == 'filing'){

            if($subprocess == 'pending'){
                $status = ["approved (gas)","unhold (filing)"];
            }else if($subprocess == 'distribute'){
                $status = ["distributed (filing)"];
            }else if($subprocess == 'hold'){
                $status = ["hold (filing)"];
            }else if($subprocess == 'unhold'){
                $status = ["unhold (filing)"];
            }
            $table = 'filings';
        }else if($process == 'create voucher'){
            if($subprocess == 'pending'){
                $status = ["distributed (filing)","unhold (create-voucher)"];
            }else if($subprocess == 'approve'){
                $status = ["approved (create-voucher)"];
            }else if($subprocess == 'hold'){
                $status = ["hold (create-voucher)"];
            }else if($subprocess == 'unhold'){
                $status = ["unhold (create-voucher)"];
            }else if($subprocess == 'cancel'){
                $status = ["cancelled (create-voucher)"];
            }else if($subprocess == 'return'){
                $status = ["returned (create-voucher)"];
            }
            $table = 'associates';
        }else if($process == 'approve voucher'){
            if($subprocess == 'pending'){
                $status = ["approved (create-voucher)","unhold (approve-voucher)"];
            }else if($subprocess == 'approve'){
                $status = ["approved (approve-voucher)"];
            }else if($subprocess == 'hold'){
                $status = ["hold (approve-voucher)"];
            }else if($subprocess == 'unhold'){
                $status = ["unhold (approve-voucher)"];
            }else if($subprocess == 'cancel'){
                $status = ["cancelled (approve-voucher)"];
            }
            $table = 'specialists';
        }else if($process == 'matching'){
            if($subprocess == 'pending'){
                $status = ["approved (approve-voucher)"];
            }else if($subprocess == 'match'){
                $status = ["matched (matching)"];
            }else if($subprocess == 'return'){
                $status = ["returned (matching)"];
            }
            $table = 'matches';
        }else if($process == 'returned voucher'){
            if($subprocess == 'pending'){
                $status = ["matched (matching)"];
            }else if($subprocess == 'distribute'){
                $status = ["distributed (return-voucher)"];
            }else if($subprocess == 'cancel'){
                $status = ["cancelled (return-voucher)"];
            }

            $table = 'return_vouchers';
        }else if($process == 'approver'){
            if($subprocess == 'pending'){
                $status = ["distributed (return-voucher)"];
            }else if($subprocess == 'hold'){
                $status = ["hold (approver)"];
            }else if($subprocess == 'unhold'){
                $status = ["unhold (approver)"];
            }else if($subprocess == 'approve'){
                $status = ["approved (approver)"];
            }

            $table = 'approvers';
        }else if($process == 'transmitted'){
            if($subprocess == 'pending'){
                $status = ["approved (approver)"];
            }else if($subprocess == 'transmit'){
                $status = ["transmitted (transmit)"];
            }

            $table = 'transmittal';
        }else if($process == 'create cheque'){
            if($subprocess == 'pending'){
                $status = ["approved (approver)","unhold (approver)"];
            }else if($subprocess == 'hold'){
                $status = ["hold (create-cheque)"];
            }else if($subprocess == 'unhold'){
                $status = ["unhold (create-cheque)"];
            }else if($subprocess == 'create'){
                $status = ["created (create-cheque)"];
            }else if($subprocess == 'release'){
                $status = ["released (create-cheque)"];
            }

            $table = 'cheque_creations';
        }else if($process == 'release cheque'){
            if($subprocess == 'pending'){
                $status = ["approved (approver)","unhold (approver)"];
            }else if($subprocess == 'return'){
                $status = ["returned (release-cheque)"];
            }else if($subprocess == 'release'){
                $status = ["released (release-cheque)"];
            }
            $table = 'cheque_releaseds';
        }else if($process == 'clear cheque'){
            if($subprocess == 'pending'){
                $status = ["released (release-cheque)"];
            }else if($subprocess == 'clear'){
                $status = ["cleared (clear-cheque)"];
            }

            $table = 'cheque_clearings';
        }else{
            $status = "status not registered";
            $table = 'not registered';
        }
        return array("status"=>$status,"table"=>$table);
    }

    public static function pullRequest($process,$subprocess){

        $result = TransactionFlow::getStatusAndTableFromProcessAndSubProcess($process,$subprocess);
        $transactions = DB::table('transactions')
        ->whereIn('status',$result['status'])
        ->orderBy('id','desc')->get();

        $transaction_format =  GenericMethod::getTransactionFormat($transactions, $result['table']);

        return $transaction_format;
    }

    public static function pullSingleRequest($process,$subprocess,$id){
        $result = TransactionFlow::getStatusAndTableFromProcessAndSubProcess($process,$subprocess);

        $transactions = Transaction::where('id',$id)
        ->whereIn('status',$result['status'])
        ->get()->first();


        return $transaction_format =  GenericMethod::getTransactionFormat($transactions, $result['table']);

        return $transaction_format;
    }

    public static function receivedRequest($request, $id){


        $process =  $request['process'];
        $subprocess =  $request['subprocess'];
        $transactions = TransactionFlow::pullSingleRequest($process,$subprocess,$id);

        return $transactions;
        $transaction_id =  $transactions['transaction_id'];
        $tag_no =  $transactions['tag_no'];
        $description = $request['description'];
        $reason_id = $request['reason_id'];
        $remarks = $request['remarks'];
        $date_status = date('Y-m-d H:i:s');

        if(!isset($request['date_received']) || empty($request['date_received'])){
            $date_received = date('Y-m-d H:i:s');
        }else{
            $date_received = $request['date_received'];
        }
        if($process == 'tag'){
            if($subprocess == 'receive'){
                $status = 'received (tagging)';
                $tag_no = 0;
            }else if($subprocess == 'tag'){
                $status = 'tagged (tagging)';

                if($max_tag_no == 0 ){
                    $tag_no = 1;
                }else{
                    $tag_no = $max_tag_no + 1;
                }
            }else if($subprocess == 'hold'){
                $status = 'hold (tagging)';
                $tag_no = 0;
            }else if($subprocess == 'unhold'){
                $status = 'unhold (tagging)';
                $tag_no = 0;
            }else if($subprocess == 'cancel'){
                $status = 'cancelled (tagging)';
                $tag_no = 0;
            }else{
                $status = 'Not Registered';
                $tag_no = 0;
            }

            return $tag_no;

            //CREATE
            Tagging::Create([
                "transaction_id"=>$transaction_id,
                "description"=>$description,
                "date_received"=>$date_received,
                "status"=>$status,
                "date_status"=>$date_status,
                "reason_id"=>$reason_id,
                "remarks"=>$remarks
            ]);
            // UPDATE
            GenericMethod::updateTransactionStatus($transaction_id,$tag_no,$status);

        }else if($process == 'gas'){

            if(isset($transactions[0]->receipt_type)){
                $receipt_type = $transactions[0]->receipt_type;
            }else{
                $receipt_type = $request['receipt_type'];
            }
            $witholding_tax = $request['witholding_tax'];
            $percentage_tax = $request['percentage_tax'];

            if($subprocess == 'receive'){
                $status = 'received (gas)';
            }else if($subprocess == 'approve'){
                $status = 'approved (gas)';
            }else if($subprocess == 'hold'){
                $status = 'hold (gas)';
            }else if($subprocess == 'unhold'){
                $status = 'unhold (gas)';
            }else if($subprocess == 'return'){
                $status = 'returned (gas)';
            }else{
                $status = 'Not Registered';
            }

            //CREATE
            Gas::Create([
                "tag_id"=>$tag_no,
                "receipt_type"=>$receipt_type,
                "date_received"=>$date_received,
                "status"=>$status,
                "date_status"=>$date_status,
                "witholding_tax"=>$witholding_tax,
                "percentage_tax"=>$percentage_tax,
                "reason_id"=>$reason_id,
                "remarks"=>$remarks
            ]);
            // UPDATE
            GenericMethod::updateTransactionStatus($transaction_id,$status);


        }else if($process == 'filing'){

            $distributed_to = $request['distributed_to'];

            if($subprocess == 'receive'){
                $status = 'received (filing)';
            }else if($subprocess == 'distribute'){
                $status = 'distributed (filing)';
            }else if($subprocess == 'hold'){
                $status = 'hold (filing)';
            }else if($subprocess == 'unhold'){
                $status = 'unhold (filing)';
            }else{
                $status = 'Not Registered';
            }

            //CREATE
            Filing::Create([
                "tag_id"=>$tagging_tag_id,
                "date_received"=>$date_received,
                "status"=>$status,
                "date_status"=>$date_status,
                "distributed_to"=>$distributed_to,
                "reason_id"=>$reason_id,
                "remarks"=>$remarks
            ]);
            // UPDATE
            GenericMethod::updateTransactionStatus($transaction_id,$status);

        }else if($process == 'create voucher'){

            if($subprocess == 'receive'){
                $status = 'received (create-voucher)';
            }else if($subprocess == 'approve'){
                $status = 'approved (create-voucher)';
            }else if($subprocess == 'hold'){
                $status = 'hold (create-voucher)';
            }else if($subprocess == 'unhold'){
                $status = 'unhold (create-voucher)';
            }else if($subprocess == 'cancel'){
                $status = 'cancelled (create-voucher)';
            }else if($subprocess == 'return'){
                $status = 'returned (create-voucher)';
            }else{
                $status = 'Not Registered';
            }

            //CREATE
            Associate::Create([
                "tag_id"=>$tagging_tag_id,
                "date_received"=>$date_received,
                "status"=>$status,
                "date_status"=>$date_status,
                "reason_id"=>$reason_id,
                "remarks"=>$remarks
            ]);
            // UPDATE
            GenericMethod::updateTransactionStatus($transaction_id,$status);

        }else if($process == 'approve voucher'){

            $month_in =  $request['month_in'];
            $voucher_no = $request['voucher_no'];

            if($subprocess == 'receive'){
                $status = 'received (approve-voucher)';
            }else if($subprocess == 'approve'){
                $status = 'approved (approve-voucher)';
            }else if($subprocess == 'hold'){
                $status = 'hold (approve-voucher)';
            }else if($subprocess == 'unhold'){
                $status = 'unhold (approve-voucher)';
            }else if($subprocess == 'cancel'){
                $status = 'cancelled (approve-voucher)';
            }else{
                $status = 'Not Registered';
            }

            //CREATE
            Specialist::Create([
                "tag_id"=>$tagging_tag_id,
                "date_received"=>$date_received,
                "month_in"=>$month_in,
                "voucher_no"=>$voucher_no,
                "status"=>$status,
                "date_status"=>$date_status,
                "reason_id"=>$reason_id,
                "remarks"=>$remarks
            ]);
            // UPDATE
            GenericMethod::updateTransactionStatus($transaction_id,$status);

        }else if($process == 'matching'){
            if($subprocess == 'receive'){
                $status = 'received (matching)';
            }else if($subprocess == 'match'){
                $status = 'matched (matching)';
            }else if($subprocess == 'return'){
                $status = 'returned (matching)';
            }else{
                $status = 'Not Registered';
            }

            //CREATE
            Match::Create([
                "tag_id"=>$tagging_tag_id,
                "date_received"=>$date_received,
                "status"=>$status,
                "date_status"=>$date_status,
                "reason_id"=>$reason_id,
                "remarks"=>$remarks
            ]);
            // UPDATE
            GenericMethod::updateTransactionStatus($transaction_id,$status);

        }else if($process == 'return voucher'){

            $distributed_to = $request['distributed_to'];

            if($subprocess == 'receive'){
                $status = 'received (return-voucher)';
            }else if($subprocess == 'distribute'){
                $status = 'distributed (return-voucher)';
            }else if($subprocess == 'cancel'){
                $status = 'cancelled (return-voucher)';
            }else{
                $status = 'Not Registered';
            }

            //CREATE
            ReturnVoucher::Create([
                "tag_id"=>$tagging_tag_id,
                "date_received"=>$date_received,
                "distributed_to"=>$distributed_to,
                "status"=>$status,
                "date_status"=>$date_status,
                "reason_id"=>$reason_id,
                "remarks"=>$remarks
            ]);
            // UPDATE
            GenericMethod::updateTransactionStatus($transaction_id,$status);

        }else if($process == 'approver'){

            if($subprocess == 'receive'){
                $status = 'received (approver)';
            }else if($subprocess == 'approve'){
                $status = 'approved (approver)';
            }else if($subprocess == 'hold'){
                $status = 'hold (approver)';
            }else if($subprocess == 'unhold'){
                $status = 'unhold (approver)';
            }else{
                $status = 'Not Registered';
            }
            //CREATE
            Approver::Create([
                "tag_id"=>$tagging_tag_id,
                "date_received"=>$date_received,
                "status"=>$status,
                "date_status"=>$date_status,
                "reason_id"=>$reason_id,
                "remarks"=>$remarks
            ]);
            // UPDATE
            GenericMethod::updateTransactionStatus($transaction_id,$status);

        }else if($process == 'transmit'){

            if($subprocess == 'receive'){
                $status = 'received (transmit)';
            }else if($subprocess == 'transmit'){
                $status = 'transmitted (transmit)';
            }else{
                $status = 'Not Registered';
            }
            //CREATE
            ChequeCreation::Create([
                "tag_id"=>$tagging_tag_id,
                "date_received"=>$date_received,
                "status"=>$status,
                "date_status"=>$date_status,
                "reason_id"=>$reason_id,
                "remarks"=>$remarks
            ]);
            // UPDATE
            GenericMethod::updateTransactionStatus($transaction_id,$status);

        }else if($process == 'create cheque'){

            if($subprocess == 'receive'){
                $status = 'received (create-cheque)';
            }else if($subprocess == 'create'){
                $status = 'created (create-cheque)';
            }else if($subprocess == 'hold'){
                $status = 'hold (create-cheque)';
            }else if($subprocess == 'unhold'){
                $status = 'unhold (create-cheque)';
            }else if($subprocess == 'release'){
                $status = 'released (create-cheque)';
            }else{
                $status = 'Not Registered';
            }
            //CREATE
            ChequeCreation::Create([
                "tag_id"=>$tagging_tag_id,
                "date_received"=>$date_received,
                "status"=>$status,
                "date_status"=>$date_status,
                "reason_id"=>$reason_id,
                "remarks"=>$remarks
            ]);
            // UPDATE
            GenericMethod::updateTransactionStatus($transaction_id,$status);

        }else if($process == 'release cheque'){

            if($subprocess == 'receive'){
                $status = 'received (release-cheque)';
            }else if($subprocess == 'release'){
                $status = 'released (release-cheque)';
            }else if($subprocess == 'return'){
                $status = 'returned (release-cheque)';
            }else{
                $status = 'Not Registered';
            }
            //CREATE
            ChequeReleased::Create([
                "tag_id"=>$tagging_tag_id,
                "date_received"=>$date_received,
                "status"=>$status,
                "date_status"=>$date_status,
                "reason_id"=>$reason_id,
                "remarks"=>$remarks
            ]);
            // UPDATE
            GenericMethod::updateTransactionStatus($transaction_id,$status);

        }else if($process == 'clear cheque'){

            if($subprocess == 'receive'){
                $status = 'received (clear-cheque)';
            }else if($subprocess == 'clear'){
                $status = 'cleared (clear-cheque)';
            }else{
                $status = 'Not Registered';
            }
            //CREATE
            ChequeClearing::Create([
                "tag_id"=>$tagging_tag_id,
                "date_received"=>$date_received,
                "status"=>$status,
                "date_status"=>$date_status,
                "reason_id"=>$reason_id,
                "remarks"=>$remarks
            ]);
            // UPDATE
            GenericMethod::updateTransactionStatus($transaction_id,$status);


        }else{
            return 'Invalid Process';
        }

    }

    public static function searchRequest($process,$subprocess,$search){

        $result = TransactionFlow::getStatusAndTableFromProcessAndSubProcess($process,$subprocess);
        $transactions = DB::table('transactions')
        ->whereIn('status',$result['status'])
        ->where(function($query) use($search){
            $query->where('id_no','like', '%'.$search.'%')
            ->orWhere('department','like', '%'.$search.'%')
            ->orWhere('document_date','like', '%'.$search.'%')
            ->orWhere('reason','like', '%'.$search.'%')
            ->orWhere('utilities_from','like', '%'.$search.'%')
            ->orWhere('utilities_to','like', '%'.$search.'%')
            ->orWhere('document_type','like', '%'.$search.'%')
            ->orWhere('category','like', '%'.$search.'%')
            ->orWhere('document_amount','like', '%'.$search.'%')
            ->orWhere('company','like', '%'.$search.'%')
            ->orWhere('supplier','like', '%'.$search.'%')
            ->orWhere('payment_type','like', '%'.$search.'%')
            ->orWhere('status','like', '%'.$search.'%')
            ->orWhere('remarks','like', '%'.$search.'%')
            ->orWhere('pcf_date','like', '%'.$search.'%')
            ->orWhere('pcf_letter','like', '%'.$search.'%')
            ->orWhere('tagging_tag_id','like', '%'.$search.'%')
            ->orWhere('transaction_id','like', '%'.$search.'%');
        })
        ->get();

        $transaction_format =  GenericMethod::getTransactionFormat($transactions, $result['table']);

        return $transaction_format;
    }

}
