<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\BankController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ReasonController;
use App\Http\Controllers\ReferrenceController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierTypeController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MasterlistController;
use App\Http\Controllers\UtilityCategoryController;
use App\Http\Controllers\TransactionFlowController;
use App\Http\Controllers\AccountNumberController;
use App\Http\Controllers\AccountTitleController;
use App\Http\Controllers\PayrollClientController;
use App\Http\Controllers\PayrollCategoryController;
use App\Http\Controllers\PayrollTypeController;




/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

//  Public Routes

//     return true;
// });

Route::post('/login', [UserController::class, 'login']);
// Protected Routes

Route::middleware('auth:sanctum')->get('/authenticated', function (Request $request) {
    return $request->user();
});


Route::group(['middleware'=>'auth:sanctum'],function(){

    //MASTERLIST GENERIC METHOD
    Route::get('masterlist/getDocumentCategoryByUser',[MasterlistController::class,'getUserDocumentCategory']);
    Route::post('masterlist/restore',[MasterlistController::class,'restore']);
    Route::post('masterlist/category-document',[MasterlistController::class,'categoryPerDocument']);

    // USER
    Route::get('users/username-validation', [UserController::class, 'username_validation']);
    Route::get('users/id-validation', [UserController::class, 'id_validation']);
    Route::resource('users', UserController::class);
    Route::post('users/archive/{id}', [UserController::class, 'archive']);
    Route::post('users/search/', [UserController::class, 'search']);
    Route::post('users/change-password/{id}', [UserController::class, 'change_password']);
    Route::post('/logout', [UserController::class, 'logout']);

    // CATEGORY
    Route::get('categories/all/', [CategoryController::class, 'categories']);
    Route::resource('categories', CategoryController::class);
    Route::post('categories/archive/{id}', [CategoryController::class, 'archive']);
    Route::post('categories/search/', [CategoryController::class, 'search']);

    // DOCUMENTS
    Route::get('documents/all/', [DocumentController::class, 'documents']);
    Route::resource('documents', DocumentController::class);
    Route::post('documents/archive/{id}', [DocumentController::class, 'archive']);
    Route::post('documents/search/', [DocumentController::class, 'search']);

    // COMPANY
    Route::resource('companies', CompanyController::class);
    Route::post('companies/archive/{id}', [CompanyController::class, 'archive']);
    Route::post('companies/search/', [CompanyController::class, 'search']);

    // DEPARTMENT
    Route::post('departments/archive/{id}', [DepartmentController::class, 'archive']);
    Route::post('departments/restore/{id}', [DepartmentController::class, 'restore']);
    Route::post('departments/search/', [DepartmentController::class, 'search']);
    Route::resource('departments',DepartmentController::class);

    // LOCATION
    Route::post('locations/archive/{id}', [LocationController::class, 'archive']);
    Route::post('locations/restore/{id}', [LocationController::class, 'restore']);
    Route::post('locations/search/', [LocationController::class, 'search']);
    Route::resource('locations',LocationController::class);

    // REASON
    Route::resource('reasons', ReasonController::class);
    Route::post('reasons/archive/{id}', [ReasonController::class, 'archive']);
    Route::post('reasons/search/', [ReasonController::class, 'search']);

    // BANK
    Route::resource('banks', BankController::class);
    Route::post('banks/archive/{id}', [BankController::class, 'archive']);
    Route::post('banks/search/', [BankController::class, 'search']);

    // SUPPLIER TYPE
    Route::get('supplier-types/all/', [SupplierTypeController::class, 'all']);
    Route::resource('supplier-types', SupplierTypeController::class);
    Route::post('supplier-types/archive/{id}', [SupplierTypeController::class, 'archive']);
    Route::post('supplier-types/search/', [SupplierTypeController::class, 'search']);

    // REFERRENCE
    Route::get('referrences/all/', [ReferrenceController::class, 'all']);
    Route::resource('referrences', ReferrenceController::class);
    Route::post('referrences/archive/{id}', [ReferrenceController::class, 'archive']);
    Route::post('referrences/search/', [ReferrenceController::class, 'search']);

    // SUPPLIER
    Route::resource('suppliers', SupplierController::class);
    Route::post('suppliers/archive/{id}', [SupplierController::class, 'archive']);
    Route::post('suppliers/search/', [SupplierController::class, 'search']);

    // TRANSACTION
    Route::resource('transactions/', TransactionController::class);
    Route::get('transactions/status_group/',[TransactionController::class,'status_group']);

    // TRANSACTION FLOW
    Route::get('transactions/flow/',[TransactionFlowController::class,'pullRequest']);
    Route::get('transactions/flow/{id}',[TransactionFlowController::class,'pullSingleRequest']);
    Route::post('transactions/flow/update-status/{id}',[TransactionFlowController::class,'receivedRequest']);
    Route::post('transactions/flow/search',[TransactionFlowController::class,'searchRequest']);




    // ADDITIONAL MASTERLIST
    // UTILITY CATEGORY
    Route::get('utility-category/all/', [UtilityCategoryController::class, 'all']);
    Route::post('utility-category/archive/{id}', [UtilityCategoryController::class, 'archive']);
    Route::post('utility-category/restore/{id}', [UtilityCategoryController::class, 'restore']);
    Route::post('utility-category/search/', [UtilityCategoryController::class, 'search']);
    Route::resource('utility-category',UtilityCategoryController::class);

    // ACCOUNT TITLE
    Route::get('account-title/all/', [AccountTitleController::class, 'all']);
    Route::post('account-title/archive/{id}', [AccountTitleController::class, 'archive']);
    Route::post('account-title/restore/{id}', [AccountTitleController::class, 'restore']);
    Route::post('account-title/search/', [AccountTitleController::class, 'search']);
    Route::resource('account-title',AccountTitleController::class);

    // PAYROLL CLIENT
    Route::get('payroll-client/all/', [PayrollClientController::class, 'all']);
    Route::post('payroll-client/archive/{id}', [PayrollClientController::class, 'archive']);
    Route::post('payroll-client/restore/{id}', [PayrollClientController::class, 'restore']);
    Route::post('payroll-client/search/', [PayrollClientController::class, 'search']);
    Route::resource('payroll-client',PayrollClientController::class);

    // PAYROLL CATEGORY
    Route::get('payroll-category/all/', [PayrollCategoryController::class, 'all']);
    Route::post('payroll-category/archive/{id}', [PayrollCategoryController::class, 'archive']);
    Route::post('payroll-category/restore/{id}', [PayrollCategoryController::class, 'restore']);
    Route::post('payroll-category/search/', [PayrollCategoryController::class, 'search']);
    Route::resource('payroll-category',PayrollCategoryController::class);

    // PAYROLL TYPE
    Route::get('payroll-type/all/', [PayrollTypeController::class, 'all']);
    Route::post('payroll-type/archive/{id}', [PayrollTypeController::class, 'archive']);
    Route::post('payroll-type/restore/{id}', [PayrollTypeController::class, 'restore']);
    Route::post('payroll-type/search/', [PayrollTypeController::class, 'search']);
    Route::resource('payroll-type',PayrollTypeController::class);

    // ACCOUNT #
    Route::post('account-number/archive/{id}', [AccountNumberController::class, 'archive']);
    Route::post('account-number/restore/{id}', [AccountNumberController::class, 'restore']);
    Route::post('account-number/search/', [AccountNumberController::class, 'search']);
    Route::resource('account-number',AccountNumberController::class);

});



