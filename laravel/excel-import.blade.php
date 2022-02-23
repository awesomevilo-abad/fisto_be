{{-- create the route and controller for import page --}}
<?php 
Route::get('/users/import', [UsersImportController::class, 'show']); 
Route::post('/users/import', [UsersImportController::class, 'store']);
?>

{{-- code in controller file: --}}
<?php 
function show() 
{
    return view('users.import');
}

function store(Request $request) 
{
    $file = $request->file('user-import');
    
    return 'ok';
}
?>

{{-- create import class for the model --}}
{{-- directory: app/Imports --}}
php artisan make:import [import name] --model=[model]
php artisan make:import UsersImport --model=User

{{-- code in import class: --}}
<?php
namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;

class UsersImport implements ToModel
{
    // the model() method accepts the $row variable that represents a single row in excel file
    public function model(array $row)
    {
        return new User([
            'name' => $row[1], // 'model property name' => 'column index in excel file'
            'email' => $row[2],
            'password' => Hash::make('password')
        ]);
    }
}
?>

{{-- import data to database --}}
{{-- code in controller file: --}}
<?php 
class UsersImportController extends Controller
{
    public function store(Request $request) 
    {
        $file = $request->file('user-import');
        Excel::import(new UsersImport, $file);

        return back()->with('status', 'Excel Imported!');
    }
}
?>

{{-- importing data using the Importable trait --}}
{{-- code in import class --}}
<?php 
class UsersImport implements ToModel
{
    use Importable;
}
?>

{{-- code in controller class --}}
<?php 
function store(Request $request) 
{
    $file = $request->file('user-import');
    $import = new UsersImport();
    $import->import($file);

    return back()->with('status', 'Excel Imported!');
}
?>

{{-- importing large file by storing it into the server --}}
<?php 
function store(Request $request) 
{
    // store file in the storage with name 'import'
    $file = $request->file('user-import')->store('import');
}
?>

{{-- using WithHeadingRow interface --}}
{{-- the import will ignore the first row usually the heading during the import --}}
<?php 
class UsersImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new User([
            'name' => $row['name'], // $row['heading name'], then you can use the heading as a key to get the row data
            'email' => $row['email'],
            'password' => Hash::make('password')
        ]);
    }
}
?>

{{-- using the SkipsOnError interface --}}
{{-- skips the row when there is an error --}}
<?php 
class UsersImport implements ToModel, WithHeadingRow, SkipsOnError
{
    // use SkipsErrors trait to access its method
    use Importable, SkipsErrors;

    // eliminate this method if you want to use the SkipsErrors trait to catch error in the controller
    public function onError(Throwable $e) {}
}
?>

{{-- catch the row with error using error() method --}}
{{-- code in controller file --}}
<?php 
function store(Request $request) 
{
    $file = $request->file('user-import')->store('import');
    $import = new UsersImport();
    $import->import($file);

    dd($import->errors()); // dump all rows with errors

    return back()->with('status', 'Excel Imported!');
}
?>

{{-- using WithValidation interface --}}
{{-- validate rows before inserting into the database --}}
{{-- note: if there was an error encountered the importing stops at the 
row with error then rollback all the inserted data --}}
<?php 
class UsersImport implements ToModel, WithHeadingRow, SkipsOnError, WithValidation
{
    // rules are the same as a request validation rules
    public function rules(): array
    {
        return [
            // asterisk (*) means validate all rows
            // syntax using headings: '*.email'
            // syntax using index: '*.1'
            '*.email' => ['email', 'unique:users,email']
        ];
    }
}
?>

{{-- displaying the errors in blade file --}}
@if (isset($errors) && $errors->any())
    @foreach ($errors->all() as $error)
        <p>{{ $error }}</p>
    @endforeach
@endif

{{-- using the SkipsOnFailure interface --}}
{{-- skipping only the row with an error --}}
<?php 
class UsersImport implements ToModel, WithHeadingRow, SkipsOnError, WithValidation, SkipsOnFailure
{
    // use SkipsFailures trait to access its method
    use Importable, SkipsErrors, SkipsFailures;

    // eliminate this method if you want to use the SkipsFailures trait to catch error in the controller
    public function onFailure(Failure ...$failures) {}
}
?>

{{-- code in controller file --}}
<?php 
function store(Request $request) 
{
    $file = $request->file('user-import')->store('import'); // store file in the storage with name 'import'
    $import = new UsersImport();
    $import->import($file);

    dd($import->failures()); // dump failures

    $failures = $import->failures();

    if ($failures->isNotEmpty()) {
        return back()->with('failures', $failures); // return back row with failures
    }

    return back()->with('status', 'Excel Imported!');
}
?>

{{-- display failures in blade file --}}
{{-- structure is based on failures collections --}}
@if (session()->has('failures'))
    <table class="table table-danger">
        <tr>
            <th>Row</th>
            <th>Attributes</th>
            <th>Errors</th>
            <th>Value</th>
        </tr>
        @foreach (session()->get('failures') as $failure)
            <tr>
                <td>{{ $failure->row() }}</td>
                <td>{{ $failure->attribute() }}</td>
                <td>
                    <ul>
                        @foreach ($failure->errors() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </td>
                <td>
                    {{ $failure->values()[$failure->attribute()] }}
                </td>
            </tr>
        @endforeach
    </table>
@endif

{{-- using batch insert --}}
{{-- speed up importing by creating a batch of model into a single query --}}
{{-- depends on server resources --}}
{{-- note: batch insert can only be use for ToModel interface --}}
<?php 
class UsersImport implements WithBatchInserts
{
    public function batchSize(): int
    {
        return 50;
    }
}
?>

{{-- using chunking when importing large file size --}}
<?php
class UsersImport implements WithChunkReading
{
    public function chunkSize(): int
    {
        return 50;
    }
}
?>

{{-- using collection to import data with relationship to other table --}}
{{-- ToModel interface is a good choice for importing single entity mode --}}
{{-- but for relationship you have to use the collection interface --}}
<?php 
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class UsersImport implements ToCollection
{
    public function collection(Collection $rows) // here we inject the illuminate collection class
    {
        foreach ($rows as $row) {
            $user = User::create([
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => Hash::make('password')
            ]);

            $user->address()->create([
                'country' => $row['country']
            ]);
        }
    }
}
?>

{{-- inserting data using query builder and collection --}}
<?php 
function collection(Collection $rows) 
{
    $data=[]; 

    foreach($rows as $row) {
        $data[] = [ // store every rows of collection in the array
            'name' => $row['name'],
            'email' => $row['email'],
            'password' => 'password'
        ];
    }

    DB::table('users')->insert($data);
}
?>

{{-- custom validation message --}}
{{-- using customValidationMessages method on import class --}}
<?php 
function customValidationMessages(): array
{
    return [
        'field.rule' => 'custom message validation',
    ];
}
?>

{{-- validating rows with ToCollection interface --}}
{{-- using laravel validator class --}}
<?php 
use Illuminate\Support\Facades\Validator;

class LocationsImport implements ToCollection, WithHeadingRow, SkipsOnFailure, WithValidation
{
    use Importable, SkipsFailures;

    private $errors = []; 

    public function collection(Collection $rows)
    {
        $rows = $rows->toArray();

        foreach ($rows as $key => $row) {

            $validator = Validator::make($row, $this->rules());

            if ($validator->fails()) {

                foreach ($validator->errors()->messages() as $messages) {
                    foreach ($messages as $error) {
                        $this->errors[] = $error; // collecting row errors
                    }
                }

            } else {

                $location = new Location();
                $location->location_name = $row['location_name'];
                $location->save();
            }
        }
    }

    public function rules(): array
    {
        return [
            'location_name' => ['required', 'regex:/^[0-9\pL\s\.,_-]+$/u'],
            'created_at' => ['required']
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'location_name.required' => 'kailangan ang location field',
            'location_name.regex' => 'special chars are not allowed',
        ];
    }
}
?>

{{-- inserting data with WithChunkReading interface, collect() and chunk() lareavel method --}}
<?php 
class LocationsImport implements ToCollection, WithHeadingRow, SkipsOnFailure, WithValidation, WithChunkReading
{
    use Importable, SkipsFailures;

    private $errors = [];

    public function collection(Collection $rows)
    {
        $data = [];

        foreach ($rows as $row) {
            $data[] = [ // store every rows of collection in the array
                'location_name' => $row['location_name']
            ];
        }

        $data = collect($data);
        $chunks = $data->chunk(500);

        foreach ($chunks as $chunk) {
            DB::table('locations')->insert($chunk->toArray());
        }
    }

    public function chunkSize(): int
    {
        return 2500;
    }
}
?>


{{-- inserting 50k rows --}}
{{-- inserting data with WithChunkReading interface, array_chunk() method without using collect() --}}
<?php 
class LocationsImport implements ToCollection, WithHeadingRow, SkipsOnFailure, WithValidation, WithChunkReading
{
    use Importable, SkipsFailures;

    public function collection(Collection $rows)
    {
        $data = [];

        foreach ($rows as $row) {
            $data[] = [ // store every rows of collection in the array
                'location_name' => $row['location_name'],
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ];
        }

        $chunks = array_chunk($data, 2500);

        foreach ($chunks as $chunk) {
            DB::table('locations')->insert($chunk);
        }
    }

    public function chunkSize(): int
    {
        return 2500;
    }
}
?>

{{-- importing with queue --}}
{{-- using the ShouldQueue interface --}}
<?php 
use Illuminate\Contracts\Queue\ShouldQueue;
class LocationsImport implements ToCollection, ShouldQueue {}
?>

{{-- in .env file change the queue connection to database --}}
QUEUE_CONNECTION=database

{{-- create a jobs table migration --}}
{{-- then migrate to create the table --}}
php artisan queue:table

{{-- running the queue worker in the background --}}
{{-- the worker will execute the jobs in the table --}}
php artisan queue:work

{{-- run a function when import is finished --}}
{{-- using the WithEvents interface, and RegistersEventListeners trait --}}
<?php 
class LocationsImport implements ToCollection, ShouldQueue, WithEvents
{
    use RegistersEventListeners;

    // use the afterImport function to do something when import finished
    public static function afterImport(AfterImport $event) {}
}
?>

{{-- you can catch errors when a import failed during job process --}}
{{-- using the onFailure method --}}
<?php 
class UsersImport implements ToCollection, ShouldQueue, WithEvents, SkipsOnError, WithValidation, SkipsOnFailure
{
    // use SkipsFailures trait to access its method
    use Importable, SkipsErrors, SkipsFailures, RegistersEventListeners;

    // eliminate this method if you want to use the SkipsFailures trait to catch error in the controller
    public function onFailure(Failure ...$failures) {}
}
?>

{{-- using fastexcel --}}
{{-- importing data with validation --}}
<?php 
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class UserImportController extends Controller
{
    public function store(Request $request)
    {
        $file = $request->file('user-import');
        $users = fastexcel()->import($file);
        $data = [];
        $errors = [];

        foreach ($users as $user) {
            $validator = Validator::make($user, $this->rules(), $this->customValidationMessages());

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $messages) {
                    foreach ($messages as $error) {
                        $errors[] = [
                            'data' => $user,
                            'message' => $error
                        ];
                    }
                }
            } else {
                $data[] = [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'password' => 'password'
                ];
            }
        }

        $chunks = array_chunk($data, 10000);

        foreach ($chunks as $chunk) {
            DB::table('users')->insert($chunk);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'regex:/^[0-9\pL\s\.,_-]+$/u']
        ];
    }

    public function customValidationMessages() 
    {
        return [
            'name.required' => 'kailangan lagyan ang name field',
            'name.regex' => 'ayusin ang format ng name'
        ];
    }
}
?>

