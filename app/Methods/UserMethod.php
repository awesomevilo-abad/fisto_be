<?php

namespace App\Methods;

use App\Exceptions\FistoException;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;
use Illuminate\Routing\Route;

class UserMethod{

    public static function validateIfTransactionExist($id){
        return Transaction::where('users_id',$id)
        ->where(function($query){
            $query->where('status','!=','Filed')
            ->where('state','!=','void');
        })
        ->exists();
    }

    public static function validateIfExist($model,$id){
        if (!$model) {
            throw new FistoException("No records found.", 404, NULL, []);     
        }       
    }

    public static function redefinedUserForSaving($user,$specific_user){
        $user->role = $specific_user['role'];
        $user->first_name = $specific_user['first_name'];
        $user->middle_name = $specific_user['middle_name'];
        $user->last_name = $specific_user['last_name'];
        $user->suffix = $specific_user['suffix'];
        $user->department = $specific_user['department'];
        $user->position = $specific_user['position'];
        $user->permissions = $specific_user['permissions'];
        $user->document_types = [];
        return $user;     
    }
    
    public static function synchWithSedarValidation($specific_user,$id){
        
        $user = User::withTrashed()->find($id);
        $user->role = $specific_user->role;
        $user->first_name = $specific_user->first_name;
        $user->middle_name = $specific_user->middle_name;
        $user->last_name = $specific_user->last_name;
        $user->suffix = $specific_user->suffix;
        $user->department = $specific_user->department;
        $user->position = $specific_user->position;
        $changed_keys = array_keys($user->getDirty());

         $transaction_exist =  Transaction::where('users_id',$id)
        ->where(function($query){
            $query->where('status','!=','Filed')
            ->where('state','!=','void');
        })
        ->exists();
        
       if((in_array('position',$changed_keys) || in_array('role',$changed_keys) || in_array('department',$changed_keys)) && $transaction_exist){
        throw new FistoException("Cannot modify user with on-going transactions.", 409, NULL, []);
       }

    }
    
    public static function userDeleteValidation($specific_user,$id){
        
        $user = User::withTrashed()->find($id);
        $user->role = $specific_user->role;
        $user->first_name = $specific_user->first_name;
        $user->middle_name = $specific_user->middle_name;
        $user->last_name = $specific_user->last_name;
        $user->suffix = $specific_user->suffix;
        $user->department = $specific_user->department;
        $user->position = $specific_user->position;
        $changed_keys = array_keys($user->getDirty());

       if(in_array('position',$changed_keys) || in_array('department',$changed_keys)){
        //    return "Position or Department";
        throw new FistoException("Cannot archive user with pending transactions.", 409, NULL, []);
       }
    }
}
