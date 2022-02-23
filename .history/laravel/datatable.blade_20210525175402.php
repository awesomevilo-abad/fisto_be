{{-- documentation: https://datatables.yajrabox.com/starter --}}
{{-- tutorial reference: https://www.positronx.io/laravel-datatables-example/ --}}

{{-- install yajra --}}
{{-- install all packages --}}
composer require yajra/laravel-datatables

{{-- installing only the oracle package --}}
composer require yajra/laravel-datatables-oracle 

{{-- add the foundational service of the package such as datatable service provider in providers and alias --}}
{{-- code in config/app.php file --}}
<?php 
return [
    'providers' => [Yajra\DataTables\DataTablesServiceProvider::class,],
    'aliases' => ['DataTables' => Yajra\DataTables\Facades\DataTables::class,]
];
?>

{{-- publish the configuration --}}
php artisan vendor:publish

{{-- publish with tags --}}
php artisan vendor:publish --tag=datatables

{{-- create a database request using datatable class --}}
<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use Yajra\DataTables\DataTables;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Student::latest()->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $btn = '<a href="javascript:void(0)" class="edit btn btn-success btn-sm">Edit</a> <a href="javascript:void(0)" class="delete btn btn-danger btn-sm">Delete</a>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('index');
    }
}
?>

{{-- render datatable in views --}}
{{-- require dataable assets --}}
<link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css" rel="stylesheet">

<div class="container mt-5">
    <table class="table table-bordered yajra-datatable">
        <thead>
            <tr>
                <th>No</th>
                <th>Name</th>
                <th>Email</th>
                <th>Username</th>
                <th>Phone</th>
                <th>DOB</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>  
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
<script type="text/javascript">
    $(function () {
        var table = $('.yajra-datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('student-list') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {data: 'name', name: 'name'},
                {data: 'email', name: 'email'},
                {data: 'username', name: 'username'},
                {data: 'phone', name: 'phone'},
                {data: 'dob', name: 'dob'},
                {
                    data: 'action', 
                    name: 'action', 
                    orderable: true, 
                    searchable: true
                },
            ]
        });
    });
</script>

{{-- passing params from ajax to server --}}
<script>
$('#datatable-oracle').DataTable({
    serverSide: true,
    ajax: {
        url: '{{route('route_name')}}',
        data: function(data) {
            data.params = {
                message: 'hello'
            }
        }
    }
});
</script>

{{-- getting the param from the request --}}
{{-- code in controller class --}}
<?php 
function index(Request $request) {
    $params = $request->params;
    $message = $params['message'];
}
?>

{{-- fast query sample --}}
<?php 
function index(Request $request) {
    if ($request->ajax()) {
        $query = DB::table('students')->select(['name', 'email', 'username', 'phone', 'dob']);

        return Datatables::of($query)->addIndexColumn()->make(true);
    }

    return view('welcome');
}
?>

{{-- yajra datatable methods --}}
<?php 
function index(Request $request) {
    if ($request->ajax()) {

        $query = DB::table('students')->select(['name', 'email', 'username', 'phone', 'dob']);

        return Datatables::of($query)
            ->addIndexColumn() // create an index 
            ->setRowClass('{{ "alert-success" }}') // set row class using blade syntax
            ->setRowId(function($query) { // set row id
                return $query->id;
            })
            ->setRowAttr(['align' => 'center']) // set row attribute
            ->setRowData(['data-name' => 'row-{{ $username }}']) // set row data using blade syntax
            ->setRowData([ // set row data using closure
                'data-id' => function($query) {
                    return 'row-'.$query->id;
                },
                'data-name' => function($query) {
                    return 'row-'.$query->name;
                },
            ])

            ->addColumn('column_name', '$name') // adding column using blade syntax
            ->addColumn('role', function(User $query) { // adding column using closure, and get data with relationship from another table
                return $query->role->name;
            })

            ->editColumn('created_at', function(User $query) { // modifying the value of a column
                return $query->created_at->diffForHumans();
            })

            // adding column with html elements
            // syntax: addColumn('column name', 'path to view')
            ->addColumn('action', 'updatebutton')
            ->rawColumns(['action']) // parse the html into element

            ->make(true);
    }

    return view('welcome');

    // VILO ADDITIONALS
    // ADD COLUMN TO EXISTING TABLE

    php artisan make:migration add_paid_to_users_table --table=users


    INSIDE MIGRATION USE (Schema::table())
 
    public function up()
    {
        Schema::table('users', function($table) {
            $table->integer('paid');
        });
    }

    public function down()
    {
        Schema::table('users', function($table) {
            $table->dropColumn('paid');
        });
    }

    // END ADD COLUMN TO EXISTING TABLE
}
?>