<?php

namespace App\Methods;

use App\Methods\GenericMethod;

use App\Models\Transaction;
use App\Models\POBatch;
use App\Models\RRBatch;
use App\Models\ReferrenceBatch;
use App\Models\ReferrenceGroupBatches;
use App\Models\POGroupBatches;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionValidationMethod
 {
    // public static

    //  MAIN PAD Transactions
   public static function padValidation($fields,$tag_id,$date_requested,$status,$transaction_id){

        if(isset($fields['document_no'])){


            if (GenericMethod::validateIfDocumentNoExist($fields['document_no']) > 0){
                $response = [
                    "code" => 403,
                    "message" => "Document No. already exist in Other Document Types",
                        "data" => null,
                ];
            }else{
                $po = $fields['po_group'];

                $po_count = count($po);
                $po_validation_count = count($po);

                $po_validation_total_amount = 0;
                $po_validation_total_qty = 0;
                $rr_validation_total_qty = 0;

                $po_total_amount = 0;
                $po_total_qty = 0;
                $rr_total_qty = 0;


                for($i=0;$i<$po_validation_count;$i++){

                    $po_validation_object = $fields['po_group'][$i];
                    $po_validation_object = (object) $po_validation_object;
                    $po_no =  $po_validation_object->po_no;

                    $po_validation_amount =(float) str_replace(',', '', $po_validation_object->po_amount);
                    $po_validation_qty =(float) str_replace(',', '', $po_validation_object->po_qty);
                    $po_validation_total_amount = $po_validation_total_amount+$po_validation_amount;
                    $po_validation_total_qty = $po_validation_total_qty+$po_validation_qty;

                    if (GenericMethod::validateIfPONoExist($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no) > 0){
                        $duplicate_po_array[] =  $po_no;
                    }
                }
                // Compile existed PO Number
                if(isset($duplicate_po_array)){
                    $response = [
                        "code" => 403,
                        "message" => "PO No. already exist in the company and supplier",
                            "data" => $duplicate_po_array,
                    ];
                }else{
                    $response = 'Validated';
                }

                if($fields['document_amount'] != $po_validation_total_amount){
                    $response =  [
                        "code" => 400,
                        "message" => "Document amount must be equal to total PO Amount",
                        "data" => null,
                    ];
                }else{

                    if($response == 'Validated'){

                        // INSERT PO DETAILS

                        for($i=0;$i<$po_count;$i++){
                            $po_object = $fields['po_group'][$i];
                            $po_object = (object) $po_object;

                            $po_no =  $po_object->po_no;
                            $po_amount =(float) str_replace(',', '', $po_object->po_amount);
                            $po_qty =(float) str_replace(',', '', $po_object->po_qty);
                            $po_total_amount = $po_total_amount+$po_amount;
                            $po_total_qty = $po_total_qty+$po_qty;

                            $insert_po_group = POGroupBatches::create([
                                'tag_id' => $tag_id
                                , "po_no" => $po_no
                            ]);

                            $insert_po_batch = POBatch::create([
                                'tag_id' => $tag_id,
                                'po_no' => $po_no
                                , "po_amount" => $po_amount
                                , "po_qty" => $po_qty
                            ]);

                        }

                        // INSERT TRANSACTION DETAILS
                        $new_transaction = Transaction::create([
                        'transaction_id' => $transaction_id
                        , "users_id" => $fields['users_id']
                        , "id_prefix" => $fields['id_prefix']
                        , "id_no" => $fields['id_no']
                        , "first_name" => $fields['first_name']
                        , "middle_name" => $fields['middle_name']
                        , "last_name" => $fields['last_name']
                        , "suffix" => $fields['suffix']
                        , "department" => $fields['department']
                        , "document_id" => $fields['document_id']
                        , "document_type" => $fields['document_type']
                        , "payment_type" => $fields['payment_type']
                        , "category_id" => $fields['category_id']
                        , "category" => $fields['category']
                        , "company_id" => $fields['company_id']
                        , "company" => $fields['company']
                        , "document_no" => $fields['document_no']
                        , "supplier_id" => $fields['supplier_id']
                        , "supplier" => $fields['supplier']
                        , "document_date" => $fields['document_date']
                        , "document_amount" => $fields['document_amount']
                        , "remarks" => $fields['remarks']
                        , "po_total_amount" => $po_total_amount
                        , "po_total_qty" => $po_total_qty

                        , "tag_id" => $tag_id
                        , "tagging_tag_id" => 0
                        , "date_requested" => $date_requested
                        , "status" => $status,
                        ]);

                        $response = [
                            "code" => 200,
                            "message" => "Succefully Created",
                            "data" => $new_transaction,
                        ];

                    }else{
                        $response = $response;
                    }
                }

            }
            $response = $response;
        }else{
            $response = [
                "code" => 404,
                "message" => "Document number is null",
                "data" => null,
            ];
        }
        return $response;
   }

   public static function prmValidation($fields,$tag_id,$date_requested,$status,$transaction_id){

    if(isset($fields['document_no'])){

        if($fields['document_amount'] == 0){
            $response = [
                "code" => 403,
                "message" => "Document amount must not be equal to 0",
                    "data" => null,
            ];

            return $response;
        }
        if (GenericMethod::validateIfDocumentNoExist($fields['document_no']) > 0){
            $response = [
                "code" => 403,
                "message" => "Document No. already exist",
                    "data" => null,
            ];
        }else{

            if($fields['payment_type'] == 'full'){

                $po = $fields['po_group'];
                $referrence_group = $fields['referrence_group'];

                if((!empty($po)) && ((empty($referrence_group) )) ){

                    $po_count = count($po);
                    $po_validation_count = count($po);

                    $po_validation_total_amount = 0;
                    $po_validation_total_qty = 0;
                    $rr_validation_total_qty = 0;

                    $po_total_amount = 0;
                    $po_total_qty = 0;
                    $rr_total_qty = 0;

                    for($i=0;$i<$po_validation_count;$i++){

                        $po_validation_object = $fields['po_group'][$i];
                        $po_validation_object = (object) $po_validation_object;
                        $po_no =  $po_validation_object->po_no;

                        $po_validation_amount =(float) str_replace(',', '', $po_validation_object->po_amount);
                        $po_validation_qty =(float) str_replace(',', '', $po_validation_object->po_qty);
                        $po_validation_total_amount = $po_validation_total_amount+$po_validation_amount;
                        $po_validation_total_qty = $po_validation_total_qty+$po_validation_qty;

                        if (GenericMethod::validateIfPONoExist($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no) > 0){
                            $duplicate_po_array[] =  $po_no;
                        }
                    }

                    if($fields['document_amount'] < $po_validation_total_amount){
                        $response =  [
                            "code" => 400,
                            "message" => "Document is lower than the total PO Amount",
                            "data" => null,
                        ];
                    }else if($fields['document_amount'] > $po_validation_total_amount){
                        $response =  [
                            "code" => 400,
                            "message" => "Total PO Amount is lower than the Document Amount",
                            "data" => null,
                        ];
                    }else if(isset($duplicate_po_array)){
                        $response = [
                            "code" => 403,
                            "message" => "PO No. already exist in the company and supplier",
                                "data" => $duplicate_po_array,
                        ];
                    }else{

                        // INSERT PO DETAILS

                        for($i=0;$i<$po_count;$i++){
                            $po_object = $fields['po_group'][$i];
                            $po_object = (object) $po_object;

                            $po_no =  $po_object->po_no;
                            $po_amount =(float) str_replace(',', '', $po_object->po_amount);
                            $po_qty =(float) str_replace(',', '', $po_object->po_qty);
                            $po_total_amount = $po_total_amount+$po_amount;
                            $po_total_qty = $po_total_qty+$po_qty;

                            $insert_po_group = POGroupBatches::create([
                                'tag_id' => $tag_id
                                , "po_no" => $po_no
                            ]);

                            $insert_po_batch = POBatch::create([
                                'tag_id' => $tag_id,
                                'po_no' => $po_no
                                , "po_amount" => $po_amount
                                , "po_qty" => $po_qty
                            ]);

                        }

                        // INSERT TRANSACTION DETAILS
                        $new_transaction = Transaction::create([
                        'transaction_id' => $transaction_id
                        , "users_id" => $fields['users_id']
                        , "id_prefix" => $fields['id_prefix']
                        , "id_no" => $fields['id_no']
                        , "first_name" => $fields['first_name']
                        , "middle_name" => $fields['middle_name']
                        , "last_name" => $fields['last_name']
                        , "suffix" => $fields['suffix']
                        , "department" => $fields['department']
                        , "document_id" => $fields['document_id']
                        , "document_type" => $fields['document_type']
                        , "payment_type" => $fields['payment_type']
                        , "category_id" => $fields['category_id']
                        , "category" => $fields['category']
                        , "company_id" => $fields['company_id']
                        , "company" => $fields['company']
                        , "document_no" => $fields['document_no']
                        , "supplier_id" => $fields['supplier_id']
                        , "supplier" => $fields['supplier']
                        , "document_date" => $fields['document_date']
                        , "document_amount" => $fields['document_amount']
                        , "remarks" => $fields['remarks']
                        , "po_total_amount" => $po_total_amount
                        , "po_total_qty" => $po_total_qty

                        , "tag_id" => $tag_id
                        , "tagging_tag_id" => 0
                        , "date_requested" => $date_requested
                        , "status" => $status,
                        ]);

                        $response = [
                            "code" => 200,
                            "message" => "Succefully Created",
                            "data" => $new_transaction,
                        ];
                    }

                }else if((empty($po)) && ((!empty($referrence_group) )) ){
                    // $response = 'REFERRENCE FULL';

                        $ref_count = count($referrence_group);
                        $ref_validation_count = count($referrence_group);

                        $ref_validation_total_amount = 0;
                        $ref_validation_total_qty = 0;
                        $ref_validation_total_qty = 0;

                        $ref_total_amount = 0;
                        $ref_total_qty = 0;
                        $ref_total_qty = 0;

                        for($i=0;$i<$ref_validation_count;$i++){

                            $ref_validation_object = $fields['referrence_group'][$i];
                            $ref_validation_object = (object) $ref_validation_object;
                            $ref_no =  $ref_validation_object->referrence_no;

                            $ref_validation_amount =(float) str_replace(',', '', $ref_validation_object->referrence_amount);
                            $ref_validation_qty =(float) str_replace(',', '', $ref_validation_object->referrence_qty);
                            $ref_validation_total_amount = $ref_validation_total_amount+$ref_validation_amount;
                            $ref_validation_total_qty = $ref_validation_total_qty+$ref_validation_qty;

                            if (GenericMethod::validateIfRefNoExist($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$ref_no) > 0){
                                $duplicate_ref_array[] =  $ref_no;
                            }
                        }

                        if($fields['document_amount'] < $ref_validation_total_amount){
                            $response =  [
                                "code" => 400,
                                "message" => "Document is lower than the total Referrence Amount",
                                "data" => null,
                            ];
                        }else if($fields['document_amount'] > $ref_validation_total_amount){
                            $response =  [
                                "code" => 400,
                                "message" => "Total Referrence Amount is lower than the Document Amount",
                                "data" => null,
                            ];
                        }else if(isset($duplicate_ref_array)){
                            $response = [
                                "code" => 403,
                                "message" => "Ref No. already exist in the company and supplier",
                                    "data" => $duplicate_ref_array,
                            ];
                        }else{

                                // INSERT PO DETAILS

                            for($i=0;$i<$ref_count;$i++){
                                $ref_object = $fields['referrence_group'][$i];
                                $ref_object = (object) $ref_object;

                                $ref_no =  $ref_object->referrence_no;
                                $ref_type =  $ref_object->referrence_type;
                                $ref_amount =(float) str_replace(',', '', $ref_object->referrence_amount);
                                $ref_qty =(float) str_replace(',', '', $ref_object->referrence_qty);
                                $ref_total_amount = $ref_total_amount+$ref_amount;
                                $ref_total_qty = $ref_total_qty+$ref_qty;

                                $insert_ref_group = ReferrenceGroupBatches::create([
                                    'tag_id' => $tag_id
                                    , "referrence_no" => $ref_no
                                    , "referrence_total_amount" => $ref_total_amount
                                ]);

                                $insert_po_batch = ReferrenceBatch::create([
                                    'tag_id' => $tag_id
                                    ,'referrence_type' => $ref_type
                                    ,'referrence_no' => $ref_no
                                    , "referrence_amount" => $ref_amount
                                    , "referrence_qty" => $ref_qty
                                ]);

                            }

                            // INSERT TRANSACTION DETAILS
                            $new_transaction = Transaction::create([
                            'transaction_id' => $transaction_id
                            , "users_id" => $fields['users_id']
                            , "id_prefix" => $fields['id_prefix']
                            , "id_no" => $fields['id_no']
                            , "first_name" => $fields['first_name']
                            , "middle_name" => $fields['middle_name']
                            , "last_name" => $fields['last_name']
                            , "suffix" => $fields['suffix']
                            , "department" => $fields['department']
                            , "document_id" => $fields['document_id']
                            , "document_type" => $fields['document_type']
                            , "payment_type" => $fields['payment_type']
                            , "category_id" => $fields['category_id']
                            , "category" => $fields['category']
                            , "company_id" => $fields['company_id']
                            , "company" => $fields['company']
                            , "document_no" => $fields['document_no']
                            , "supplier_id" => $fields['supplier_id']
                            , "supplier" => $fields['supplier']
                            , "document_date" => $fields['document_date']
                            , "document_amount" => $fields['document_amount']
                            , "remarks" => $fields['remarks']
                            , "referrence_total_amount" => $ref_total_amount
                            , "referrence_total_qty" => $ref_total_qty

                        , "tag_id" => $tag_id
                        , "tagging_tag_id" => 0
                            , "date_requested" => $date_requested
                            , "status" => $status,
                            ]);

                            $response = [
                                "code" => 200,
                                "message" => "Succefully Created",
                                "data" => $new_transaction,
                            ];


                        }


                }else if((!empty($po)) && ((!empty($referrence_group) )) ){
                    // $response = 'PO & REFERRENCE FULL';
                    $po = $fields['po_group'];
                    $referrence_group = $fields['referrence_group'];

                        $po_count = count($po);
                        $po_validation_count = count($po);

                        $po_validation_total_amount = 0;
                        $po_validation_total_qty = 0;
                        $rr_validation_total_qty = 0;

                        $po_total_amount = 0;
                        $po_total_qty = 0;
                        $rr_total_qty = 0;

                        for($i=0;$i<$po_validation_count;$i++){

                                $po_validation_object = $fields['po_group'][$i];
                                $po_validation_object = (object) $po_validation_object;
                                $po_no =  $po_validation_object->po_no;

                                $po_validation_amount =(float) str_replace(',', '', $po_validation_object->po_amount);
                                $po_validation_qty =(float) str_replace(',', '', $po_validation_object->po_qty);
                                $po_validation_total_amount = $po_validation_total_amount+$po_validation_amount;
                                $po_validation_total_qty = $po_validation_total_qty+$po_validation_qty;


                                $ref_count = count($referrence_group);
                                $ref_validation_count = count($referrence_group);

                                $ref_validation_total_amount = 0;
                                $ref_validation_total_qty = 0;
                                $ref_validation_total_qty = 0;

                                $ref_total_amount = 0;
                                $ref_total_qty = 0;
                                $ref_total_qty = 0;

                                if (GenericMethod::validateIfPONoExist($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no) > 0){
                                    $duplicate_po_array[] =  $po_no;
                                }


                        }


                        for($i=0;$i<$ref_validation_count;$i++){

                            $ref_validation_object = $fields['referrence_group'][$i];
                            $ref_validation_object = (object) $ref_validation_object;
                            $ref_no =  $ref_validation_object->referrence_no;

                            $ref_validation_amount =(float) str_replace(',', '', $ref_validation_object->referrence_amount);
                            $ref_validation_qty =(float) str_replace(',', '', $ref_validation_object->referrence_qty);
                            $ref_validation_total_amount = $ref_validation_total_amount+$ref_validation_amount;
                            $ref_validation_total_qty = $ref_validation_total_qty+$ref_validation_qty;

                            if (GenericMethod::validateIfRefNoExist($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$ref_no) > 0){
                                $duplicate_ref_array[] =  $ref_no;
                            }
                        }

                        // Compile existed PO Number
                        if($fields['document_amount'] < $po_validation_total_amount){
                            $response =  [
                                "code" => 400,
                                "message" => "Document is lower than the total PO Amount",
                                "data" => null,
                            ];
                        }else if($fields['document_amount'] > $po_validation_total_amount){
                            $response =  [
                                "code" => 400,
                                "message" => "Total PO Amount is lower than the Document Amount",
                                "data" => null,
                            ];
                        }else if($fields['document_amount'] < $ref_validation_total_amount){
                            $response =  [
                                "code" => 400,
                                "message" => "Document is lower than the total Referrence Amount",
                                "data" => null,
                            ];
                        }else if($fields['document_amount'] > $ref_validation_total_amount){
                            $response =  [
                                "code" => 400,
                                "message" => "Total Referrence Amount is lower than the Document Amount",
                                "data" => null,
                            ];
                        }else if(isset($duplicate_po_array)){
                            $response = [
                                "code" => 403,
                                "message" => "PO No. already exist in the company and supplier",
                                    "data" => $duplicate_po_array,
                            ];
                        }else if(isset($duplicate_ref_array)){
                            $response = [
                                "code" => 403,
                                "message" => "Ref No. already exist in the company and supplier",
                                    "data" => $duplicate_ref_array,
                            ];
                        }
                        else{
                            $response = 'Validated Insert Here';

                            // INSERT PO DETAILS
                            for($i=0;$i<$po_count;$i++){
                                $po_object = $fields['po_group'][$i];
                                $po_object = (object) $po_object;

                                $po_no =  $po_object->po_no;
                                $po_amount =(float) str_replace(',', '', $po_object->po_amount);
                                $po_qty =(float) str_replace(',', '', $po_object->po_qty);
                                $po_total_amount = $po_total_amount+$po_amount;
                                $po_total_qty = $po_total_qty+$po_qty;

                                $insert_po_group = POGroupBatches::create([
                                    'tag_id' => $tag_id
                                    , "po_no" => $po_no
                                ]);

                                $insert_po_batch = POBatch::create([
                                    'tag_id' => $tag_id,
                                    'po_no' => $po_no
                                    , "po_amount" => $po_amount
                                    , "po_qty" => $po_qty
                                ]);

                            }

                            for($i=0;$i<$ref_count;$i++){
                                $ref_object = $fields['referrence_group'][$i];
                                $ref_object = (object) $ref_object;

                                $ref_no =  $ref_object->referrence_no;
                                $ref_type =  $ref_object->referrence_type;
                                $ref_amount =(float) str_replace(',', '', $ref_object->referrence_amount);
                                $ref_qty =(float) str_replace(',', '', $ref_object->referrence_qty);
                                $ref_total_amount = $ref_total_amount+$ref_amount;
                                $ref_total_qty = $ref_total_qty+$ref_qty;

                                $insert_ref_group = ReferrenceGroupBatches::create([
                                    'tag_id' => $tag_id
                                    , "referrence_no" => $ref_no
                                    , "referrence_total_amount" => $ref_total_amount
                                ]);

                                $insert_po_batch = ReferrenceBatch::create([
                                    'tag_id' => $tag_id
                                    ,'referrence_type' => $ref_type
                                    ,'referrence_no' => $ref_no
                                    , "referrence_amount" => $ref_amount
                                    , "referrence_qty" => $ref_qty
                                ]);

                            }


                            // INSERT TRANSACTION DETAILS
                            $new_transaction = Transaction::create([
                            'transaction_id' => $transaction_id
                            , "users_id" => $fields['users_id']
                            , "id_prefix" => $fields['id_prefix']
                            , "id_no" => $fields['id_no']
                            , "first_name" => $fields['first_name']
                            , "middle_name" => $fields['middle_name']
                            , "last_name" => $fields['last_name']
                            , "suffix" => $fields['suffix']
                            , "department" => $fields['department']
                            , "document_id" => $fields['document_id']
                            , "document_type" => $fields['document_type']
                            , "payment_type" => $fields['payment_type']
                            , "category_id" => $fields['category_id']
                            , "category" => $fields['category']
                            , "company_id" => $fields['company_id']
                            , "company" => $fields['company']
                            , "document_no" => $fields['document_no']
                            , "supplier_id" => $fields['supplier_id']
                            , "supplier" => $fields['supplier']
                            , "document_date" => $fields['document_date']
                            , "document_amount" => $fields['document_amount']
                            , "remarks" => $fields['remarks']
                            , "referrence_total_amount" => $po_total_amount
                            , "referrence_total_qty" => $po_total_qty
                            , "po_total_amount" => $po_total_amount
                            , "po_total_qty" => $po_total_qty

                        , "tag_id" => $tag_id
                        , "tagging_tag_id" => 0
                            , "date_requested" => $date_requested
                            , "status" => $status,
                            ]);

                            $response = [
                                "code" => 200,
                                "message" => "Succefully Created",
                                "data" => $new_transaction,
                            ];

                        }


                }else{

                    $po_count = count($po);
                    $po_validation_count = count($po);

                    $po_validation_total_amount = 0;
                    $po_validation_total_qty = 0;
                    $rr_validation_total_qty = 0;

                    $po_total_amount = 0;
                    $po_total_qty = 0;
                    $rr_total_qty = 0;


                    // INSERT TRANSACTION DETAILS
                    $new_transaction = Transaction::create([
                    'transaction_id' => $transaction_id
                    , "users_id" => $fields['users_id']
                    , "id_prefix" => $fields['id_prefix']
                    , "id_no" => $fields['id_no']
                    , "first_name" => $fields['first_name']
                    , "middle_name" => $fields['middle_name']
                    , "last_name" => $fields['last_name']
                    , "suffix" => $fields['suffix']
                    , "department" => $fields['department']
                    , "document_id" => $fields['document_id']
                    , "document_type" => $fields['document_type']
                    , "payment_type" => $fields['payment_type']
                    , "category_id" => $fields['category_id']
                    , "category" => $fields['category']
                    , "company_id" => $fields['company_id']
                    , "company" => $fields['company']
                    , "document_no" => $fields['document_no']
                    , "supplier_id" => $fields['supplier_id']
                    , "supplier" => $fields['supplier']
                    , "document_date" => $fields['document_date']
                    , "document_amount" => $fields['document_amount']
                    , "remarks" => $fields['remarks']
                    , "referrence_total_amount" => $po_total_amount
                    , "referrence_total_qty" => $po_total_qty
                    , "po_total_amount" => $po_total_amount
                    , "po_total_qty" => $po_total_qty

                        , "tag_id" => $tag_id
                        , "tagging_tag_id" => 0
                    , "date_requested" => $date_requested
                    , "status" => $status,
                    ]);

                    $response = [
                        "code" => 200,
                        "message" => "Succefully Created",
                        "data" => $new_transaction,
                    ];

                }
            }else if($fields['payment_type'] == 'partial'){
                $po = $fields['po_group'];
                $referrence_group = $fields['referrence_group'];

                if((!empty($po)) && ((empty($referrence_group) )) ){

                    $po_count = count($po);
                    $po_validation_count = count($po);

                    $po_validation_total_amount = 0;
                    $po_validation_total_qty = 0;
                    $rr_validation_total_qty = 0;

                    $po_total_amount = 0;
                    $po_total_qty = 0;
                    $rr_total_qty = 0;

                    for($i=0;$i<$po_validation_count;$i++){

                        $po_validation_object = $fields['po_group'][$i];
                        $po_validation_object = (object) $po_validation_object;
                        $po_no =  $po_validation_object->po_no;

                        $po_validation_amount =(float) str_replace(',', '', $po_validation_object->po_amount);
                        $po_validation_qty =(float) str_replace(',', '', $po_validation_object->po_qty);
                        $po_validation_total_amount = $po_validation_total_amount+$po_validation_amount;
                        $po_validation_total_qty = $po_validation_total_qty+$po_validation_qty;

                        $po_list[] = $po_validation_object;
                        $po_no_list[] =  $po_validation_object->po_no;
                        $count_of_po_in_other_doc = 0;

                        if (GenericMethod::validateIfPONoExist($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no) > 0){
                            $existing_po_no[] =  $po_no;
                        }


                        if (GenericMethod::getUsedPO($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no,$fields['document_amount']) > 0){
                            $used_po[] =  $po_no;
                        }

                        $po_from_db_object = GenericMethod::getUsedPOFromDB($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no,$fields['document_amount']);

                            foreach($po_from_db_object as $specific_po_from_db_object){
                                $used_po_from_db[]  =   $specific_po_from_db_object->po_no;
                            }

                        $get_transaction = GenericMethod::getTagIDUsingPONo($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no,$fields['document_amount']);
                        foreach($get_transaction  as $get_specific_transaction){
                                $used_tag_id[] =  $get_specific_transaction->tag_id;
                        }


                        if(isset($used_tag_id)){
                            $used_tag_id = (array_values(array_unique($used_tag_id)));
                            if (GenericMethod::validateIfPOExistInOtherDocNo($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no,$used_tag_id) > 0){
                                $count_of_po_in_other_doc++;
                            }
                        }

                    }


                        if(isset($used_tag_id)){

                                // EXISTING TRANSACTION

                                // KAPAG WALANG used_po Ibig sabihin new PO ang prinoprocess kasi wala pa to sa database,
                                //  kapag meron existing ang PO sa database
                                // IF ISSET Means Need additional PO because Document Amount is higher than PO Amount


                                if(isset($used_po)){
                                    foreach ($po_list as $key => $specific_po_list) {
                                        foreach($used_po as $specific_used_po){
                                            if($specific_po_list->po_no == $specific_used_po) {
                                                unset($po_list[$key]);
                                            }
                                        }
                                    }
                                    $po_list = array_values($po_list);

                                    // ADDITIONAL PO

                                    if($po_list){
                                        $po_list_count = count($po_list);
                                        $po_list_total_amount = 0;
                                        for($i=0;$i<$po_list_count;$i++){
                                            $po_list_total_amount = $po_list_total_amount+$po_list[$i]->po_amount;
                                            $po_additional_pos[] =$po_list[$i]->po_no;
                                        }
                                        // GET BALANCE FROM OLD PO AND ADD IT TO THE TOTAL AMOUNT OF ADDITIONAL POS
                                        $balance_document_po_amount_object = GenericMethod::getBalanceAmountOfDocumentPO($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],end($used_po),$fields['document_amount']);

                                        if($balance_document_po_amount_object->isEmpty()){
                                            $balance_document_po_amount  = $balance_document_po_amount;
                                        }
                                        else{
                                            $balance_document_po_amount = $balance_document_po_amount_object[0]->balance_document_po_amount;
                                            $balance_document_po_amount = $balance_document_po_amount + $po_list_total_amount;
                                            $balance_document_po_amount = $balance_document_po_amount - $fields['document_amount'];
                                        }
                                        if ($po_list_total_amount < $fields['document_amount']){
                                            $response = [
                                                "code" => 403,
                                                "message" => "Document amount is higher than the old balance and total amount of additional POs ",
                                                    "data" => $po_additional_pos,
                                            ];
                                        }else{
                                                $response = "Validated";
                                        }

                                    }else{

                                        $response = [
                                            "code" => 403,
                                            "message" => "Either Document Amount is Higher than the Balance or Document Amount is Closed",
                                                "data" => $used_po,
                                        ];

                                    }
                                    // $response = "May Additional PO";

                                }else{
                                    $used_po_from_db = array_unique($used_po_from_db);
                                    if($po_no_list == $used_po_from_db){
                                        if ($po_validation_total_amount < $fields['document_amount']){
                                            $response = [
                                                "code" => 403,
                                                "message" => "Document amount is higher than the PO Amount  ",
                                                    "data" => $po_no_list,
                                            ];
                                        }else{
                                            $response = "Validated";
                                            $balance_document_po_amount = $po_validation_total_amount - $fields['document_amount'];

                                            //  GET BALANCE FROM OLD PO AND ADD IT TO THE TOTAL AMOUNT OF ADDITIONAL POS
                                            $balance_document_po_amount_object = GenericMethod::getBalanceAmountOfDocumentPO($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no,$fields['document_amount']);

                                            if($balance_document_po_amount_object->isEmpty()){

                                                $balance_document_po_amount  = $balance_document_po_amount;
                                            }
                                            else{

                                                $balance_document_po_amount = $balance_document_po_amount_object[0]->balance_document_po_amount;
                                                $balance_document_po_amount = $balance_document_po_amount - $fields['document_amount'];
                                            }
                                        }
                                    }else{
                                        $response = [
                                            "code" => 403,
                                            "message" => "Either 1. PO No. already exist from other PO Batch  2. Still have enough balance to your Old PO/s or 3. Old PO value is == '0', you need to increase your document amount value",
                                                "data" => null,
                                        ];
                                    }
                                }


                        }else{
                            // 1ST TRANSACTION

                            if ($po_validation_total_amount < $fields['document_amount']){
                                $response = [
                                    "code" => 403,
                                    "message" => "Document amount is higher than the PO Amount  ",
                                        "data" => $po_no_list,
                                ];
                            }else{
                                $response = "Validated";
                                $balance_document_po_amount = $po_validation_total_amount - $fields['document_amount'];

                                //  GET BALANCE FROM OLD PO AND ADD IT TO THE TOTAL AMOUNT OF ADDITIONAL POS
                                $balance_document_po_amount_object = GenericMethod::getBalanceAmountOfDocumentPO($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no,$fields['document_amount']);
                                if($balance_document_po_amount_object->isEmpty()){
                                    $balance_document_po_amount  = $balance_document_po_amount;
                                }
                                    else{

                                        $balance_document_po_amount = $balance_document_po_amount_object[0]->balance_document_po_amount;
                                        $balance_document_po_amount = $balance_document_po_amount - $fields['document_amount'];
                                    }
                            }
                        }

                    if($response == "Validated"){

                        $response =  $fields['document_amount'].' - '.$po_validation_total_amount.' = '.$balance_document_po_amount;

                        // INSERT PO DETAILS

                        for($i=0;$i<$po_count;$i++){
                            $po_object = $fields['po_group'][$i];
                            $po_object = (object) $po_object;

                            $po_no =  $po_object->po_no;
                            $po_amount =(float) str_replace(',', '', $po_object->po_amount);
                            $po_qty =(float) str_replace(',', '', $po_object->po_qty);
                            $po_total_amount = $po_total_amount+$po_amount;
                            $po_total_qty = $po_total_qty+$po_qty;

                            $insert_po_group = POGroupBatches::create([
                                'tag_id' => $tag_id
                                , "po_no" => $po_no
                            ]);

                            $insert_po_batch = POBatch::create([
                                'tag_id' => $tag_id,
                                'po_no' => $po_no
                                , "po_amount" => $po_amount
                                , "po_qty" => $po_qty
                            ]);

                        }

                        // // INSERT TRANSACTION DETAILS
                        $new_transaction = Transaction::create([
                        'transaction_id' => $transaction_id
                        , "users_id" => $fields['users_id']
                        , "id_prefix" => $fields['id_prefix']
                        , "id_no" => $fields['id_no']
                        , "first_name" => $fields['first_name']
                        , "middle_name" => $fields['middle_name']
                        , "last_name" => $fields['last_name']
                        , "suffix" => $fields['suffix']
                        , "department" => $fields['department']
                        , "document_id" => $fields['document_id']
                        , "document_type" => $fields['document_type']
                        , "payment_type" => $fields['payment_type']
                        , "category_id" => $fields['category_id']
                        , "category" => $fields['category']
                        , "company_id" => $fields['company_id']
                        , "company" => $fields['company']
                        , "document_no" => $fields['document_no']
                        , "supplier_id" => $fields['supplier_id']
                        , "supplier" => $fields['supplier']
                        , "document_date" => $fields['document_date']
                        , "document_amount" => $fields['document_amount']
                        , "remarks" => $fields['remarks']
                        , "po_total_amount" => $po_total_amount
                        , "po_total_qty" => $po_total_qty

                        , "tag_id" => $tag_id
                        , "tagging_tag_id" => 0
                        , "date_requested" => $date_requested
                        , "status" => $status
                        , "balance_document_po_amount" => $balance_document_po_amount
                        ]);

                        $response = [
                            "code" => 200,
                            "message" => "Succefully Created",
                            "data" => $new_transaction,
                        ];
                    }else{
                        $response = $response;
                    }
                }else{
                    $response = [
                        "code" => 404,
                        "message" => "PO is empty",
                        "data" => null,
                    ];
                }
            }else{
                $response =  [
                    "code" => 404,
                    "message" => "Payment Type does not exist",
                    "data" => null,
                ];
            }

        }
    }else{
        $response = [
            "code" => 404,
            "message" => "Document number is null",
            "data" => null,
        ];
    }
    return $response;

   }

   public static function contractorsBillingValidation($fields,$tag_id,$date_requested,$status,$transaction_id){
    if(isset($fields['document_no'])){

        if($fields['document_amount'] == 0){
            $response = [
                "code" => 403,
                "message" => "Document amount must not be equal to 0",
                    "data" => null,
            ];

            return $response;
        }
        if (GenericMethod::validateIfDocumentNoExist($fields['document_no']) > 0){
            $response = [
                "code" => 403,
                "message" => "Document No. already exist",
                    "data" => null,
            ];
        }else{

            if($fields['payment_type'] == 'partial'){
                $po = $fields['po_group'];
                $referrence_group = $fields['referrence_group'];

                if((!empty($po)) && ((empty($referrence_group) )) ){

                    $po_count = count($po);
                    $po_validation_count = count($po);

                    $po_validation_total_amount = 0;
                    $po_validation_total_qty = 0;
                    $rr_validation_total_qty = 0;

                    $po_total_amount = 0;
                    $po_total_qty = 0;
                    $rr_total_qty = 0;

                    for($i=0;$i<$po_validation_count;$i++){

                        $po_validation_object = $fields['po_group'][$i];
                        $po_validation_object = (object) $po_validation_object;
                        $po_no =  $po_validation_object->po_no;

                        $po_validation_amount =(float) str_replace(',', '', $po_validation_object->po_amount);
                        $po_validation_qty =(float) str_replace(',', '', $po_validation_object->po_qty);
                        $po_validation_total_amount = $po_validation_total_amount+$po_validation_amount;
                        $po_validation_total_qty = $po_validation_total_qty+$po_validation_qty;

                        $po_list[] = $po_validation_object;
                        $po_no_list[] =  $po_validation_object->po_no;
                        $count_of_po_in_other_doc = 0;

                        if (GenericMethod::validateIfPONoExist($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no) > 0){
                            $existing_po_no[] =  $po_no;
                        }


                        if (GenericMethod::getUsedPO($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no,$fields['document_amount']) > 0){
                            $used_po[] =  $po_no;
                        }

                        $po_from_db_object = GenericMethod::getUsedPOFromDB($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no,$fields['document_amount']);

                            foreach($po_from_db_object as $specific_po_from_db_object){
                                $used_po_from_db[]  =   $specific_po_from_db_object->po_no;
                            }

                        $get_transaction = GenericMethod::getTagIDUsingPONo($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no,$fields['document_amount']);
                        foreach($get_transaction  as $get_specific_transaction){
                                $used_tag_id[] =  $get_specific_transaction->tag_id;
                        }


                        if(isset($used_tag_id)){
                            $used_tag_id = (array_values(array_unique($used_tag_id)));
                            if (GenericMethod::validateIfPOExistInOtherDocNo($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no,$used_tag_id) > 0){
                                $count_of_po_in_other_doc++;
                            }
                        }

                    }


                        if(isset($used_tag_id)){

                                // EXISTING TRANSACTION

                                // KAPAG WALANG used_po Ibig sabihin new PO ang prinoprocess kasi wala pa to sa database,
                                //  kapag meron existing ang PO sa database
                                // IF ISSET Means Need additional PO because Document Amount is higher than PO Amount


                                if(isset($used_po)){
                                    foreach ($po_list as $key => $specific_po_list) {
                                        foreach($used_po as $specific_used_po){
                                            if($specific_po_list->po_no == $specific_used_po) {
                                                unset($po_list[$key]);
                                            }
                                        }
                                    }
                                    $po_list = array_values($po_list);

                                    // ADDITIONAL PO

                                    if($po_list){
                                        $po_list_count = count($po_list);
                                        $po_list_total_amount = 0;
                                        for($i=0;$i<$po_list_count;$i++){
                                            $po_list_total_amount = $po_list_total_amount+$po_list[$i]->po_amount;
                                            $po_additional_pos[] =$po_list[$i]->po_no;
                                        }
                                        // GET BALANCE FROM OLD PO AND ADD IT TO THE TOTAL AMOUNT OF ADDITIONAL POS
                                        $balance_document_po_amount_object = GenericMethod::getBalanceAmountOfDocumentPO($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],end($used_po),$fields['document_amount']);

                                        if($balance_document_po_amount_object->isEmpty()){
                                            $balance_document_po_amount  = $balance_document_po_amount;
                                        }
                                        else{
                                            $balance_document_po_amount = $balance_document_po_amount_object[0]->balance_document_po_amount;
                                            $balance_document_po_amount = $balance_document_po_amount + $po_list_total_amount;
                                            $balance_document_po_amount = $balance_document_po_amount - $fields['document_amount'];
                                        }
                                        if ($po_list_total_amount < $fields['document_amount']){
                                            $response = [
                                                "code" => 403,
                                                "message" => "Document amount is higher than the old balance and total amount of additional POs ",
                                                    "data" => $po_additional_pos,
                                            ];
                                        }else{
                                                $response = "Validated";
                                        }

                                    }else{

                                        $response = [
                                            "code" => 403,
                                            "message" => "Either Document Amount is Higher than the Balance or Document Amount is Closed",
                                                "data" => $used_po,
                                        ];

                                    }
                                    // $response = "May Additional PO";

                                }else{
                                    $used_po_from_db = array_unique($used_po_from_db);
                                    if($po_no_list == $used_po_from_db){
                                        if ($po_validation_total_amount < $fields['document_amount']){
                                            $response = [
                                                "code" => 403,
                                                "message" => "Document amount is higher than the PO Amount  ",
                                                    "data" => $po_no_list,
                                            ];
                                        }else{
                                            $response = "Validated";
                                            $balance_document_po_amount = $po_validation_total_amount - $fields['document_amount'];

                                            //  GET BALANCE FROM OLD PO AND ADD IT TO THE TOTAL AMOUNT OF ADDITIONAL POS
                                            $balance_document_po_amount_object = GenericMethod::getBalanceAmountOfDocumentPO($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no,$fields['document_amount']);

                                            if($balance_document_po_amount_object->isEmpty()){

                                                $balance_document_po_amount  = $balance_document_po_amount;
                                            }
                                            else{

                                                $balance_document_po_amount = $balance_document_po_amount_object[0]->balance_document_po_amount;
                                                $balance_document_po_amount = $balance_document_po_amount - $fields['document_amount'];
                                            }
                                        }
                                    }else{
                                        $response = [
                                            "code" => 403,
                                            "message" => "Either 1. PO No. already exist from other PO Batch  2. Still have enough balance to your Old PO/s or 3. Old PO value is == '0', you need to increase your document amount value",
                                                "data" => null,
                                        ];
                                    }
                                }


                        }else{
                            // 1ST TRANSACTION

                            if ($po_validation_total_amount < $fields['document_amount']){
                                $response = [
                                    "code" => 403,
                                    "message" => "Document amount is higher than the PO Amount  ",
                                        "data" => $po_no_list,
                                ];
                            }else{
                                $response = "Validated";
                                $balance_document_po_amount = $po_validation_total_amount - $fields['document_amount'];

                                //  GET BALANCE FROM OLD PO AND ADD IT TO THE TOTAL AMOUNT OF ADDITIONAL POS
                                $balance_document_po_amount_object = GenericMethod::getBalanceAmountOfDocumentPO($fields['payment_type'],$fields['company_id'],$fields['supplier_id'],$po_no,$fields['document_amount']);
                                if($balance_document_po_amount_object->isEmpty()){
                                    $balance_document_po_amount  = $balance_document_po_amount;
                                }
                                    else{

                                        $balance_document_po_amount = $balance_document_po_amount_object[0]->balance_document_po_amount;
                                        $balance_document_po_amount = $balance_document_po_amount - $fields['document_amount'];
                                    }
                            }
                        }

                    if($response == "Validated"){

                        $response =  $fields['document_amount'].' - '.$po_validation_total_amount.' = '.$balance_document_po_amount;

                        // INSERT PO DETAILS

                        for($i=0;$i<$po_count;$i++){

                            $po_object = $fields['po_group'][$i];
                            $po_object = (object) $po_object;

                            $po_no =  $po_object->po_no;
                            $po_amount =(float) str_replace(',', '', $po_object->po_amount);
                            $po_qty =(float) str_replace(',', '', $po_object->po_qty);
                            $po_total_amount = $po_total_amount+$po_amount;
                            $po_total_qty = $po_total_qty+$po_qty;

                            $insert_po_group = POGroupBatches::create([
                                'tag_id' => $tag_id
                                , "po_no" => $po_no
                            ]);

                            $insert_po_batch = POBatch::create([
                                'tag_id' => $tag_id,
                                'po_no' => $po_no
                                , "po_amount" => $po_amount
                                , "po_qty" => $po_qty
                            ]);

                        }

                        // // INSERT TRANSACTION DETAILS
                        $new_transaction = Transaction::create([
                        'transaction_id' => $transaction_id
                        , "users_id" => $fields['users_id']
                        , "id_prefix" => $fields['id_prefix']
                        , "id_no" => $fields['id_no']
                        , "first_name" => $fields['first_name']
                        , "middle_name" => $fields['middle_name']
                        , "last_name" => $fields['last_name']
                        , "suffix" => $fields['suffix']
                        , "department" => $fields['department']
                        , "document_id" => $fields['document_id']
                        , "document_type" => $fields['document_type']
                        , "payment_type" => $fields['payment_type']
                        , "category_id" => $fields['category_id']
                        , "category" => $fields['category']
                        , "company_id" => $fields['company_id']
                        , "company" => $fields['company']
                        , "document_no" => $fields['document_no']
                        , "supplier_id" => $fields['supplier_id']
                        , "supplier" => $fields['supplier']
                        , "document_date" => $fields['document_date']
                        , "document_amount" => $fields['document_amount']
                        , "remarks" => $fields['remarks']
                        , "po_total_amount" => $po_total_amount
                        , "po_total_qty" => $po_total_qty

                        , "tag_id" => $tag_id
                        , "tagging_tag_id" => 0
                        , "date_requested" => $date_requested
                        , "status" => $status
                        , "balance_document_po_amount" => $balance_document_po_amount
                        ]);

                        $response = [
                            "code" => 200,
                            "message" => "Succefully Created",
                            "data" => $new_transaction,
                        ];
                    }else{
                        $response = $response;
                    }
                }else{
                    $response = [
                        "code" => 404,
                        "message" => "PO is empty",
                        "data" => null,
                    ];
                }
            }else{
                $response =  [
                    "code" => 404,
                    "message" => "Payment Type does not exist",
                    "data" => null,
                ];
            }

        }
    }else{
        $response = [
            "code" => 404,
            "message" => "Document number is null",
            "data" => null,
        ];
    }
    return $response;
   }

   public static function utilitiesValidation($fields,$tag_id,$date_requested,$status,$transaction_id){

        if(!isset($fields['document_no'])){
           return $response = [
                "code" => 404,
                "message" => "Document number is null",
                "data" => null,
            ];
        }

        if (GenericMethod::validateIfDocumentNoExist($fields['document_no']) > 0){
           return $response = [
                "code" => 403,
                "message" => "Document No. already exist in Other Document Types",
                    "data" => null,
            ];
        }

        if (GenericMethod::validateIfUtilityExist($fields['payment_type'],$fields['company_id'],
        $fields['supplier_id'],$fields['utilities_from']
        ,$fields['utilities_to'],$fields['utilities_account_no']
        ,$fields['utilities_consumption'],$fields['utilities_uom']
        ,$fields['utilities_receipt_no']) > 0){
            return $response = [
                 "code" => 403,
                 "message" => "Utility transaction already exist",
                     "data" => null,
             ];
         }

         // INSERT TRANSACTION DETAILS
         $new_transaction = Transaction::create([
            'transaction_id' => $transaction_id
            , "users_id" => $fields['users_id']
            , "id_prefix" => $fields['id_prefix']
            , "id_no" => $fields['id_no']
            , "first_name" => $fields['first_name']
            , "middle_name" => $fields['middle_name']
            , "last_name" => $fields['last_name']
            , "suffix" => $fields['suffix']
            , "department" => $fields['department']
            , "document_id" => $fields['document_id']
            , "document_type" => $fields['document_type']
            , "payment_type" => $fields['payment_type']
            , "category_id" => $fields['category_id']
            , "category" => $fields['category']
            , "company_id" => $fields['company_id']
            , "company" => $fields['company']
            , "document_no" => $fields['document_no']
            , "supplier_id" => $fields['supplier_id']
            , "supplier" => $fields['supplier']
            , "document_date" => $fields['document_date']
            , "document_amount" => $fields['document_amount']
            , "remarks" => $fields['remarks']
            , "po_total_amount" => null
            , "po_total_qty" => null

            , "tag_id" => $tag_id
            , "tagging_tag_id" => 0
            , "date_requested" => $date_requested
            , "status" => $status

            , "utilities_from" => $fields['utilities_from']
            , "utilities_to" => $fields['utilities_to']
            , "utilities_account_no" => $fields['utilities_account_no']
            , "utilities_consumption" => $fields['utilities_consumption']
            , "utilities_uom" => $fields['utilities_uom']
            , "utilities_receipt_no" => $fields['utilities_receipt_no']
            ]);

            $response = [
                "code" => 200,
                "message" => "Succefully Created",
                "data" => $new_transaction,
            ];

        return $response;
   }

   public static function payrollValidation($fields,$tag_id,$date_requested,$status,$transaction_id){

    if(!isset($fields['document_no'])){
       return $response = [
            "code" => 404,
            "message" => "Document number is null",
            "data" => null,
        ];
    }

    if (GenericMethod::validateIfDocumentNoExist($fields['document_no']) > 0){
       return $response = [
            "code" => 403,
            "message" => "Document No. already exist in Other Document Types",
                "data" => null,
        ];
    }

    if (GenericMethod::validateIfPayrollExist($fields['payment_type'],$fields['company_id'],
    $fields['supplier_id'],$fields['payroll_from']
    ,$fields['payroll_to'],$fields['payroll_location']
    ,$fields['payroll_tax_category']) > 0){
        return $response = [
             "code" => 403,
             "message" => "Payroll transaction already exist",
                 "data" => null,
         ];
     }

     // INSERT TRANSACTION DETAILS
     $new_transaction = Transaction::create([
        'transaction_id' => $transaction_id
        , "users_id" => $fields['users_id']
        , "id_prefix" => $fields['id_prefix']
        , "id_no" => $fields['id_no']
        , "first_name" => $fields['first_name']
        , "middle_name" => $fields['middle_name']
        , "last_name" => $fields['last_name']
        , "suffix" => $fields['suffix']
        , "department" => $fields['department']
        , "document_id" => $fields['document_id']
        , "document_type" => $fields['document_type']
        , "payment_type" => $fields['payment_type']
        , "category_id" => $fields['category_id']
        , "category" => $fields['category']
        , "company_id" => $fields['company_id']
        , "company" => $fields['company']
        , "document_no" => $fields['document_no']
        , "supplier_id" => $fields['supplier_id']
        , "supplier" => $fields['supplier']
        , "document_date" => $fields['document_date']
        , "document_amount" => $fields['document_amount']
        , "remarks" => $fields['remarks']
        , "po_total_amount" => null
        , "po_total_qty" => null

        , "tag_id" => $tag_id
        , "tagging_tag_id" => 0
        , "date_requested" => $date_requested
        , "status" => $status

        , "payroll_from" => $fields['payroll_from']
        , "payroll_to" => $fields['payroll_to']
        , "payroll_location" => $fields['payroll_location']
        , "payroll_tax_category" => $fields['payroll_tax_category']
        ]);

        $response = [
            "code" => 200,
            "message" => "Succefully Created",
            "data" => $new_transaction,
        ];

    return $response;
   }

   public static function receiptValidation($fields,$tag_id,$date_requested,$status,$transaction_id){

        if(!isset($fields['document_no'])){
            return $response = [
                "code" => 404,
                "message" => "Document number is null",
                "data" => null,
            ];
        }

        if (GenericMethod::validateIfDocumentNoExist($fields['document_no']) > 0){
            return $response = [
                "code" => 403,
                "message" => "Document No. already exist in Other Document Types",
                    "data" => null,
            ];
        }

        if($fields['payment_type'] == 'full'){
        }else{

        }


        return $fields;


   }
 }
