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
    Route::get('referrences/', [ReferrenceController::class, 'index']);
    Route::patch('referrences/{id}', [ReferrenceController::class, 'change_status']);
    Route::resource('referrences', ReferrenceController::class);

    // SUPPLIER
    Route::get('suppliers/', [SupplierController::class, 'index']);
    Route::patch('suppliers/{id}', [SupplierController::class, 'change_status']);
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
    Route::get('utility-category/', [UtilityCategoryController::class, 'index']);
    Route::patch('utility-category/{id}', [UtilityCategoryController::class, 'change_status']);
    Route::resource('utility-category', UtilityCategoryController::class);

    // UTILITY LOCATION
    Route::get('utility-location', [UtilityLocationController::class, 'index']);
    Route::patch('utility-location/{id}', [UtilityLocationController::class, 'change_status']);
    Route::resource('utility-location', UtilityLocationController::class);

    // ACCOUNT TITLE
    Route::get('account-title/', [AccountTitleController::class, 'index']);
    Route::patch('account-title/{id}', [AccountTitleController::class, 'change_status']);
    Route::post('account-title/import',[AccountTitleController::class,'import']);
    Route::resource('account-title', AccountTitleController::class);

    // PAYROLL CLIENT
    Route::get('payroll-client/', [PayrollClientController::class, 'index']);
    Route::patch('payroll-client/{id}', [PayrollClientController::class, 'change_status']);
    Route::resource('payroll-client',PayrollClientController::class);

    // PAYROLL CATEGORY
    Route::get('payroll-category/', [PayrollCategoryController::class, 'index']);
    Route::patch('payroll-category/{id}', [PayrollCategoryController::class, 'change_status']);
    Route::resource('payroll-category',PayrollCategoryController::class);

    // PAYROLL TYPE
    Route::get('payroll-type/all/', [PayrollTypeController::class, 'all']);
    Route::post('payroll-type/archive/{id}', [PayrollTypeController::class, 'archive']);
    Route::post('payroll-type/restore/{id}', [PayrollTypeController::class, 'restore']);
    Route::post('payroll-type/search/', [PayrollTypeController::class, 'search']);
    Route::resource('payroll-type',PayrollTypeController::class);

    // ACCOUNT #
    Route::get('account-number/', [AccountNumberController::class, 'index']);
    Route::patch('account-number/{id}', [AccountNumberController::class, 'change_status']);
    Route::post('account-number/import/', [AccountNumberController::class, 'import']);
    Route::resource('account-number',AccountNumberController::class);

    // CREDIT CARD
    Route::get('credit-card/',[CreditCardController::class,'index']);
    Route::patch('credit-card/{id}', [CreditCardController::class, 'change_status']);
    Route::resource('credit-card',CreditCardController::class);

});


