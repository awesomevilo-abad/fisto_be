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
use App\Methods\GenericMethod;

class TransactionFlow{

    public static function getStatusAndTableFromProcessAndSubProcess($process,$subprocess){
        if($process == 'ap tagging'){
            if($subprocess == 'pending'){
                $status = ["pending","received (tagging)","unhold (tagging)"];
            }else if($subprocess == 'hold'){
                $status = ["hold (tagging)"];
            }else if($subprocess == 'unhold'){
                $status = ["unhold (tagging)"];
            }else if($subprocess == 'reject'){
                $status = ["rejected (tagging)"];
            }else if($subprocess == 'tag'){
                $status = ["tagged (tagging)"];
            }
            $table = 'taggings';
        }else if($process == 'gas'){

            if($subprocess == 'pending'){
                $status = ["tagged (tagging)", "received (gas)"];
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
            }else if($subprocess == 'return'){
                $status = ["returned (return-voucher)"];
            }

            $table = 'return_vouchers';
        }else if($process == 'approver'){
            if($subprocess == 'pending'){
                $status = ["distributed (return-voucher)"];
            }else if($subprocess == 'hold'){
                $status = ["hold (approver)"];
            }else if($subprocess == 'unhold'){
                $status = ["unhold (approver)"];
            }else if($subprocess == 'cancel'){
                $status = ["cancelled (approver)"];
            }else if($subprocess == 'approve'){
                $status = ["approved (approver)"];
            }

            $table = 'approvers';
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

        $transactions = DB::table('transactions')
        ->whereIn('status',$result['status'])
        ->where('id',$id)
        ->get();


        $transaction_format =  GenericMethod::getTransactionFormat($transactions, $result['table']);

        return $transaction_format;
    }

    public static function receivedRequest($request, $id){

        $max_tag_no = GenericMethod::generateTagNo();

        $process =  $request['process'];
        $transactions = TransactionFlow::pullSingleRequest($process,$id);
        $transaction_id =  $transactions[0]->transaction_id;
        $tagging_tag_id =  $transactions[0]->tagging_tag_id;

        $subprocess =  $request['subprocess'];
        $description = $request['description'];
        $reason_id = $request['reason_id'];
        $remarks = $request['remarks'];
        $date_status = date('Y-m-d H:i:s');

        if(!isset($request['date_received']) || empty($request['date_received'])){
            $date_received = date('Y-m-d H:i:s');
        }else{
            $date_received = $request['date_received'];
        }

        if($process == 'ap tagging'){
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
            }else if($subprocess == 'reject'){
                $status = 'rejected (tagging)';
                $tag_no = 0;
            }else{
                $status = 'Not Registered';
                $tag_no = 0;
            }

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
            GenericMethod::updateTransactionStatus($transaction_id,$status);

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
                $status = 'approved (gas)';
            }else if($subprocess == 'approve'){
                $status = 'approved (gas)';
            }else if($subprocess == 'return'){
                $status = 'returned (gas)';
            }else{
                $status = 'Not Registered';
            }

            //CREATE
            Gas::Create([
                "tag_id"=>$tagging_tag_id,
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
            }else if($subprocess == 'return'){
                $status = 'returned (return-voucher)';
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
            }else if($subprocess == 'cancel'){
                $status = 'cancelled (approver)';
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
