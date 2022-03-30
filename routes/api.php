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

    Route::post('logout/', [UserController::class, 'logout']);
    Route::put('users/change-password', [UserController::class, 'change_password']);
    Route::post('users/username-validation', [UserController::class, 'username_validation']);
    Route::post('users/id-validation', [UserController::class, 'id_validation']);
    
    //MASTERLIST GENERIC METHOD
    Route::get('users/dropdown/{status}', [MasterlistController::class, 'newUserDropdown']);
    Route::get('documents/dropdown/{status}', [MasterlistController::class, 'documentsDropdown']);
    Route::get('suppliers/dropdown/{status}', [MasterlistController::class, 'suppliersDropdown']);
    Route::get('account-number/dropdown/{status}', [MasterlistController::class, 'accountNumberDropdown']);
    Route::get('credit-card/dropdown/{status}', [MasterlistController::class, 'creditCardDropdown']);
    Route::get('banks/dropdown/{status}', [MasterlistController::class, 'banksDropdown']);
    Route::get('masterlist/getDocumentCategoryByUser',[MasterlistController::class,'getUserDocumentCategory']);
    Route::post('masterlist/restore',[MasterlistController::class,'restore']);
    Route::post('masterlist/category-document',[MasterlistController::class,'categoryPerDocument']);
    Route::resource('masterlist', MasterlistController::class);
    
    Route::group(['prefix' => 'admin', 'middleware' => ['auth'=>'is_admin']], function(){
         // CATEGORY
        Route::get('categories/', [CategoryController::class, 'index']);
        Route::patch('categories/{id}', [CategoryController::class, 'change_status']);
        Route::resource('categories', CategoryController::class);

        // DOCUMENTS
        Route::get('documents/', [DocumentController::class, 'index']);
        Route::patch('documents/{id}', [DocumentController::class, 'change_status']);
        Route::resource('documents', DocumentController::class);
      
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
        
        // SUPPLIER
        Route::get('suppliers/', [SupplierController::class, 'index']);
        Route::patch('suppliers/{id}', [SupplierController::class, 'change_status']);
        Route::post('suppliers/import/', [SupplierController::class, 'import']);
        Route::resource('suppliers', SupplierController::class);
    
        // REFERRENCE
        Route::get('referrences/', [ReferrenceController::class, 'index']);
        Route::patch('referrences/{id}', [ReferrenceController::class, 'change_status']);
        Route::resource('referrences', ReferrenceController::class);
       
        // ACCOUNT TITLE
        Route::get('account-title/', [AccountTitleController::class, 'index']);
        Route::patch('account-title/{id}', [AccountTitleController::class, 'change_status']);
        Route::post('account-title/import',[AccountTitleController::class,'import']);
        Route::resource('account-title', AccountTitleController::class);
        
        // ACCOUNT #
        Route::get('account-number/', [AccountNumberController::class, 'index']);
        Route::patch('account-number/{id}', [AccountNumberController::class, 'change_status']);
        Route::post('account-number/import/', [AccountNumberController::class, 'import']);
        Route::resource('account-number',AccountNumberController::class);
        
        // PAYROLL CLIENT
        Route::get('payroll-client/', [PayrollClientController::class, 'index']);
        Route::patch('payroll-client/{id}', [PayrollClientController::class, 'change_status']);
        Route::resource('payroll-client',PayrollClientController::class);

        // PAYROLL CATEGORY
        Route::get('payroll-category/', [PayrollCategoryController::class, 'index']);
        Route::patch('payroll-category/{id}', [PayrollCategoryController::class, 'change_status']);
        Route::resource('payroll-category',PayrollCategoryController::class);

        // UTILITY CATEGORY
        Route::get('utility-category/', [UtilityCategoryController::class, 'index']);
        Route::patch('utility-category/{id}', [UtilityCategoryController::class, 'change_status']);
        Route::resource('utility-category', UtilityCategoryController::class);

        // UTILITY LOCATION
        Route::get('utility-location', [UtilityLocationController::class, 'index']);
        Route::patch('utility-location/{id}', [UtilityLocationController::class, 'change_status']);
        Route::resource('utility-location', UtilityLocationController::class);
       
        // CREDIT CARD
        Route::get('credit-card/',[CreditCardController::class,'index']);
        Route::patch('credit-card/{id}', [CreditCardController::class, 'change_status']);
        Route::resource('credit-card',CreditCardController::class);
      
        // USER
        Route::get('users/',[UserController::class,'index']);
        Route::patch('users/{id}', [UserController::class, 'change_status']);
        Route::patch('users/reset/{id}', [UserController::class, 'reset']);
        Route::resource('users', UserController::class);
        
        //MASTERLIST GENERIC METHOD
        Route::get('dropdown/document',[MasterlistController::class,'documentDropdown']);
        Route::get('dropdown/category',[MasterlistController::class,'categoryDropdown']);
        Route::get('dropdown/supplier-reference',[MasterlistController::class,'supplierRefDropdown']);
        Route::get('dropdown/location-category-supplier',[MasterlistController::class,'loccatsupDropdown']);
        Route::get('dropdown/location-category',[MasterlistController::class,'loccatDropdown']);
        Route::get('dropdown/account-title',[MasterlistController::class,'accountTitleDropdown']);
        Route::get('dropdown/company',[MasterlistController::class,'companyDropdown']);
        Route::get('dropdown/associate',[MasterlistController::class,'associateDropdown']);
        Route::get('dropdown/charging',[MasterlistController::class,'chargingDropdown']);

        // COMPANY
        Route::get('companies/', [CompanyController::class, 'index']);
        Route::patch('companies/{id}', [CompanyController::class, 'change_status']);
        Route::resource('companies', CompanyController::class);
         
        // DEPARTMENT
        Route::get('departments/', [DepartmentController::class, 'index']);
        Route::post('departments/import',[DepartmentController::class,'import']);
        Route::patch('departments/{id}', [DepartmentController::class, 'change_status']);
        Route::resource('departments', DepartmentController::class);
         
        // LOCATION
        Route::get('locations/', [LocationController::class, 'index']);
        Route::post('locations/import',[LocationController::class,'import']);
        Route::patch('locations/{id}', [LocationController::class, 'change_status']);
        Route::resource('locations', LocationController::class);
         
    });


    // TRANSACTION
    Route::resource('transactions/', TransactionController::class);
    Route::get('transactions/status_group/',[TransactionController::class,'status_group']);
    Route::get('transactions/get_po_details',[TransactionController::class,'getPODetails']);
    Route::post('transactions/validate_document_no',[TransactionController::class,'validateDocumentNo']);
    // TRANSACTION FLOW
    Route::get('transactions/flow/',[TransactionFlowController::class,'pullRequest']);
    Route::get('transactions/flow/{id}',[TransactionFlowController::class,'pullSingleRequest']);
    Route::post('transactions/flow/update-status/{id}',[TransactionFlowController::class,'receivedRequest']);
    Route::post('transactions/flow/search',[TransactionFlowController::class,'searchRequest']);
});


