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
use App\Http\Controllers\UtilityLocationController;
use App\Http\Controllers\TransactionFlowController;
use App\Http\Controllers\AccountNumberController;
use App\Http\Controllers\AccountTitleController;
use App\Http\Controllers\CreditCardController;
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
Route::post('/login', [UserController::class, 'login']);

// Protected Routes
// Route::middleware('auth:sanctum')->get('/authenticated', function (Request $request) {
//     return $request->user();
// });


Route::group(['middleware'=>'auth:sanctum'],function() {

    //MASTERLIST GENERIC METHOD
    Route::get('suppliers/dropdown/{status}', [MasterlistController::class, 'suppliersDropdown']);
    Route::get('account-number/dropdown/{status}', [MasterlistController::class, 'accountNumberDropdown']);
    Route::get('account-title/dropdown/{status}', [MasterlistController::class, 'accountTitlesDropdown']);
    Route::get('masterlist/getDocumentCategoryByUser',[MasterlistController::class,'getUserDocumentCategory']);
    Route::post('masterlist/restore',[MasterlistController::class,'restore']);
    Route::post('masterlist/category-document',[MasterlistController::class,'categoryPerDocument']);
    Route::resource('masterlist', MasterlistController::class);

    // USER
    Route::get('users/{status}/{row_per_page}',[UserController::class,'index']);
    Route::get('users/username-validation', [UserController::class, 'username_validation']);
    Route::get('users/id-validation', [UserController::class, 'id_validation']);
    Route::resource('users', UserController::class);
    Route::post('users/archive/{id}', [UserController::class, 'archive']);
    Route::post('users/restore/{id}', [UserController::class, 'restore']);
    Route::post('users/reset/{id}', [UserController::class, 'reset']);
    Route::post('users/search/{status}/{row_per_page}', [UserController::class, 'search']);
    Route::post('users/change-password', [UserController::class, 'change_password']);
    Route::post('/logout', [UserController::class, 'logout']);

    // CATEGORY
    Route::get('categories/', [CategoryController::class, 'index']);
    Route::patch('categories/{id}', [CategoryController::class, 'change_status']);
    Route::resource('categories', CategoryController::class);

    // DOCUMENTS
    Route::get('documents/', [DocumentController::class, 'index']);
    Route::patch('documents/{id}', [DocumentController::class, 'change_status']);
    Route::resource('documents', DocumentController::class);

    // COMPANY
    Route::resource('companies/', CompanyController::class);
    Route::post('companies/archive/{id}', [CompanyController::class, 'archive']);
    Route::post('companies/search/', [CompanyController::class, 'search']);

    // REASON
    Route::get('reasons/', [ReasonController::class, 'index']);
    Route::patch('reasons/{id}', [ReasonController::class, 'change_status']);
    Route::resource('reasons', ReasonController::class);

    // BANK
    Route::get('banks/', [BankController::class, 'index']);
    Route::patch('banks/{id}', [BankController::class, 'change_status']);
    Route::post('banks/import/', [BankController::class, 'import']);
    Route::resource('banks', BankController::class);

    // SUPPLIER TYPE
    Route::get('supplier-types/', [SupplierTypeController::class, 'index']);
    Route::patch('supplier-types/{id}', [SupplierTypeController::class, 'change_status']);
    Route::resource('supplier-types', SupplierTypeController::class);

    // REFERRENCE
    Route::get('referrences/all/{status}/', [ReferrenceController::class, 'all']);
    Route::get('referrences/{status}/{row_per_page}/', [ReferrenceController::class, 'index']);
    Route::post('referrences/restore/{id}', [ReferrenceController::class, 'restore']);
    Route::post('referrences/archive/{id}', [ReferrenceController::class, 'archive']);
    Route::post('referrences/search/{status}/{row_per_page}/', [ReferrenceController::class, 'search']);
    Route::resource('referrences', ReferrenceController::class);

    // SUPPLIER
    Route::get('suppliers/all/{status}/', [SupplierController::class, 'all']);
    Route::get('suppliers/{status}/{row_per_page}/', [SupplierController::class, 'index']);
    Route::post('suppliers/search/{status}/{row_per_page}/', [SupplierController::class, 'search']);
    Route::post('suppliers/archive/{id}', [SupplierController::class, 'archive']);
    Route::post('suppliers/restore/{id}', [SupplierController::class, 'restore']);
    Route::post('suppliers/import/', [SupplierController::class, 'import']);
    Route::resource('suppliers', SupplierController::class);

    // TRANSACTION
    Route::resource('transactions/', TransactionController::class);
    Route::get('transactions/status_group/',[TransactionController::class,'status_group']);

    // TRANSACTION FLOW
    Route::get('transactions/flow/',[TransactionFlowController::class,'pullRequest']);
    Route::get('transactions/flow/{id}',[TransactionFlowController::class,'pullSingleRequest']);
    Route::post('transactions/flow/update-status/{id}',[TransactionFlowController::class,'receivedRequest']);
    Route::post('transactions/flow/search',[TransactionFlowController::class,'searchRequest']);

    // ADDITIONAL MASTERLIST
    // DEPARTMENT
    Route::get('departments/all/', [DepartmentController::class, 'all']);
    Route::post('departments/archive/{id}', [DepartmentController::class, 'archive']);
    Route::post('departments/restore/{id}', [DepartmentController::class, 'restore']);
    Route::post('departments/search/', [DepartmentController::class, 'search']);
    Route::resource('departments',DepartmentController::class);

    // LOCATION
    Route::get('locations/all/', [LocationController::class, 'all']);
    Route::post('locations/archive/{id}', [LocationController::class, 'archive']);
    Route::post('locations/restore/{id}', [LocationController::class, 'restore']);
    Route::post('locations/search/', [LocationController::class, 'search']);
    Route::resource('locations',LocationController::class);

    // UTILITY CATEGORY
    Route::get('utility-category/all/{status}/', [UtilityCategoryController::class, 'all']);
    Route::get('utility-category/{status}/{row_per_page}/', [UtilityCategoryController::class, 'index']);
    Route::post('utility-category/search/{status}/{row_per_page}/', [UtilityCategoryController::class, 'search']);
    Route::post('utility-category/archive/{id}', [UtilityCategoryController::class, 'archive']);
    Route::post('utility-category/restore/{id}', [UtilityCategoryController::class, 'restore']);
    Route::resource('utility-category', UtilityCategoryController::class);

    // UTILITY LOCATION
    Route::get('utility-location/all/{status}/', [UtilityLocationController::class, 'all']);
    Route::get('utility-location/{status}/{row_per_page}', [UtilityLocationController::class, 'index']);
    Route::post('utility-location/search/{status}/{row_per_page}/', [UtilityLocationController::class, 'search']);
    Route::post('utility-location/archive/{id}', [UtilityLocationController::class, 'archive']);
    Route::post('utility-location/restore/{id}', [UtilityLocationController::class, 'restore']);
    Route::resource('utility-location', UtilityLocationController::class);

    // ACCOUNT TITLE
    Route::post('account-title/import',[AccountTitleController::class,'import']);
    Route::get('account-title/all/{status}/', [AccountTitleController::class, 'all']);
    Route::get('account-title/{status}/{row_per_page}/', [AccountTitleController::class, 'index']);
    Route::post('account-title/search/{status}/{row_per_page}/', [AccountTitleController::class, 'search']);
    Route::post('account-title/archive/{id}', [AccountTitleController::class, 'archive']);
    Route::post('account-title/restore/{id}', [AccountTitleController::class, 'restore']);
    Route::resource('account-title', AccountTitleController::class);

    // PAYROLL CLIENT
    Route::get('payroll-client/all/{status}', [PayrollClientController::class, 'all']);
    Route::get('payroll-client/{status}/{row_per_page}', [PayrollClientController::class, 'index']);
    Route::post('payroll-client/search/{status}/{row_per_page}/', [PayrollClientController::class, 'search']);
    Route::post('payroll-client/archive/{id}', [PayrollClientController::class, 'archive']);
    Route::post('payroll-client/restore/{id}', [PayrollClientController::class, 'restore']);
    Route::post('payroll-client/search/', [PayrollClientController::class, 'search']);
    Route::resource('payroll-client',PayrollClientController::class);

    // PAYROLL CATEGORY
    Route::get('payroll-category/all/{status}/', [PayrollCategoryController::class, 'all']);
    Route::post('payroll-category/archive/{id}', [PayrollCategoryController::class, 'archive']);
    Route::post('payroll-category/restore/{id}', [PayrollCategoryController::class, 'restore']);
    Route::post('payroll-category/search/{status}/{row_per_page}/', [PayrollCategoryController::class, 'search']);
    Route::get('payroll-category/{status}/{row_per_page}/', [PayrollCategoryController::class, 'index']);
    Route::resource('payroll-category',PayrollCategoryController::class);

    // PAYROLL TYPE
    Route::get('payroll-type/all/', [PayrollTypeController::class, 'all']);
    Route::post('payroll-type/archive/{id}', [PayrollTypeController::class, 'archive']);
    Route::post('payroll-type/restore/{id}', [PayrollTypeController::class, 'restore']);
    Route::post('payroll-type/search/', [PayrollTypeController::class, 'search']);
    Route::resource('payroll-type',PayrollTypeController::class);

    // ACCOUNT #
    Route::get('account-number/all/{status}', [AccountNumberController::class, 'all']);
    Route::get('account-number/{status}/{row_per_page}', [AccountNumberController::class, 'index']);
    Route::post('account-number/search/{status}/{row_per_page}/', [AccountNumberController::class, 'search']);
    Route::post('account-number/archive/{id}', [AccountNumberController::class, 'archive']);
    Route::post('account-number/restore/{id}', [AccountNumberController::class, 'restore']);
    Route::post('account-number/import/', [AccountNumberController::class, 'import']);
    Route::resource('account-number',AccountNumberController::class);

    // CREDIT CARD
    Route::get('credit-card/{status}/{row_per_page}',[CreditCardController::class,'index']);
    Route::post('credit-card/search/{status}/{row_per_page}',[CreditCardController::class,'search']);
    Route::post('credit-card/archive/{id}',[CreditCardController::class,'archive']);
    Route::post('credit-card/restore/{id}',[CreditCardController::class,'restore']);
    Route::resource('credit-card',CreditCardController::class);

});


