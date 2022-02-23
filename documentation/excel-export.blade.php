{{-- using laravel excel package --}}
{{-- install laravel excel --}}
composer require maatwebsite/excel

{{-- register providers and alias in config/app.php --}}
<?php 
return [
    'providers' => [
        Maatwebsite\Excel\ExcelServiceProvider::class,
    ],

    'aliases' => [
        'Excel' => Maatwebsite\Excel\Facades\Excel::class,
    ]
];
?>

{{-- publish the config --}}
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config

{{-- creating the export class for the model to be exported --}}
{{-- file can be found in app/Exports: --}}
php artisan make:export [export name] --model=[model]
php artisan make:export UsersExport --model=User

{{-- code in export class --}}
<?php
namespace App\Exports;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;

class UsersExport implements FromCollection
{
    public function collection()
    {
        return User::all();
    }
}
?>

{{-- code in controller class --}}
<?php
namespace App\Http\Controllers;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;

class UsersController extends Controller 
{
    public function export() 
    {
        // change file extension for different type of excel file
        // list of supported formats: https://docs.laravel-excel.com/3.1/exports/export-formats.html
        return Excel::download(new UsersExport, 'users.xlsx');
    }
}
?>

{{-- create route for the export --}}
<?php Route::get('/users/export', [UserController::class, 'export']); ?>

{{-- using the exportable trait --}}
<?php
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;

class UsersExport implements FromCollection
{
    use Exportable; // use exportable trait to call different kind of methods

    public function collection()
    {
        return User::all();
    }
}
?>

{{-- code in controller --}}
<?php 
function export() {
    $user_export = new UsersExport();
    return $user_export->download('users.xlsx'); // using the exportable trait download method
}
?>

{{-- the FromCollection interface only exports collections of data --}}
{{-- to exports an array type of data use the Collection class --}}
{{-- ShouldAutoSize interface will automatically format column to fit into the content --}}
<?php
class UsersExport implements FromCollection, ShouldAutoSize
{
    public function collection()
    {
        $array = ['john', 'peter', 'mary'];

        return new Collection([$array]);
    }
}
?>

{{-- using FromArray interface --}}
{{-- exporting pure array data without using collection --}}
<?php
use Maatwebsite\Excel\Concerns\FromArray;

class UsersExport implements FromArray
{
    public function array(): array // declaring the return type
    {
        return [
            ['john', 'peter', 'mary']
        ];
    }
}
?>

{{-- using mapping interface --}}
{{-- customizing the column that you only want to export --}}
<?php
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, WithMapping
{
    public function collection()
    {
        return User::all();
    }

    // map() method automatically accepts the model variable returned by 
    // the collection() method (or other interface method)
    public function map($model): array
    {
        return [
            $model->id,
            $model->name
        ];
    }
}
?>

{{-- exporting data with relationship to the other table --}}
{{-- in this case a user model is related with the address model using hasOne relationship --}}
{{-- a user has one address --}}
{{-- code in the User model class --}}
<?php 
function address() {
    return $this->hasOne(Address::class);
}
?>

{{-- create the query with relationship --}}
{{-- then use mapping to extract the fields you want to export --}}
<?php 
class UsersExport implements FromCollection, ShouldAutoSize, WithMapping
{
    public function collection()
    {
        return User::with('address')->get();
    }

    public function map($us): array
    {
        return [
            $us->id,
            $us->name,
            $us->address->country,
            $us->created_at
        ];
    }
}
?>

{{-- creating column headers for the excel file --}}
{{-- using the WithHeadings interface --}}
<?php
class UsersExport implements FromCollection, WithHeadings
{
    public function headings(): array
    {
        // specify the heading names
        return [
            'ID',
            'NAME',
            'DATE CREATED'
        ];
    }
}
?>

{{-- registering events --}}
{{-- using the WithEvents interface class --}}
<?php 
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeSheet;

class UsersExport implements FromCollection, WithEvents 
{
    public function registerEvents(): array
    {
        $style = [
            'font' => [
                'bold' => true
            ]
        ];

        return [
            BeforeSheet::class => function(BeforeSheet $event) {
                // set a specific cell value using events
                // inserting custom value
                $event->sheet->setCellValue('A1', 'Custom Value'); // syntax: setCellValue('cell', 'value');
            },

            AfterSheet::class => function(AfterSheet $event) use ($style) {
                // syntax: getStyle('from:to');
                $event->sheet->getStyle('A1:D1')->applyFromArray($style);
                $event->sheet->insertNewRowBefore(7, 3); // syntax: insertNewRowBefore('starting point', 'number of rows');
                $event->sheet->insertNewColumnBefore('C', 2); // syntax: insertNewRowBefore('starting point', 'number of columns');

                // set cell value with computation
                $event->sheet->setCellValue('E26', '=SUM(E2:25)');
            }
        ];
    }
}
?>

{{-- exporting large dataset --}}
{{-- using optimize formQuery interface --}}
{{-- query executes in chunks --}}
<?php 
use Maatwebsite\Excel\Concerns\FromQuery;

class UsersExport implements FromQuery
{
    public function query()
    {
        return User::query()->with('address');
    }
}
?>

{{-- multiple sheets --}}
{{-- create another export class for multisheet export --}}
php artisan make:export UserMultiSheetExport

{{-- creating multiple sheet for user created on specified month --}}
{{-- code in controller class --}}
<?php 
function export() {
    $user_export = new UserMultiSheetExport(2020); // passing year argument into the multisheet class
    return $user_export->download('users.xlsx');
}

// code in multisheet export
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UserMultiSheetExport implements WithMultipleSheets
{
    use Exportable;

    private $year;

    public function __construct(int $year) // accepts argument by class constructor
    {
        $this->year = $year;
    }

    public function sheets(): array
    {
        $sheets = []; // array container for sheets

        for ($month = 1; $month <= 12; $month++) { // loop for every month
            $sheets[] = new UsersExport($this->year, $month); // store individual user class into the sheet array
        }

        return $sheets;
    }
}

// code in user export class
class UsersExport implements FromQuery, WithTitle
{
    use Exportable;
    
    private $year;
    private $month;

    public function __construct(int $year, int $month)
    {
        $this->year = $year;
        $this->month = $month;
    }

    // creating the query that will fetch user with created_at equal to given year and month
    public function query()
    {
        return User::query()
        ->with('address')
        ->whereYear('created_at', $this->year)
        ->whereMonth('created_at', $this->month);
    }

    // customizing title of every sheets
    // using the WithTitle interface
    public function title(): string
    {
        // converting to month name format
        return DateTime::createFromFormat('!m', $this->month)->format('F');
    }
}
?>

{{-- using fastexcel package --}}
{{-- exporting file --}}
composer require rap2hpoutre/fast-excel

<?php 
use Rap2hpoutre\FastExcel\FastExcel;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;

class UserControlller extends Controller
{
    public function export() {
        function usersGenerator() {
            $users = DB::table('users')->select(['id', 'name', 'email'])->orderBy('id', 'desc');

            foreach ($users->cursor() as $user) {
                yield $user;
            }
        }

        $generator = usersGenerator();

        // store the file for frontend access 
        (new FastExcel($users))->export('users-fastexcel.xlsx');

        // download the file directly without storing
        return (new FastExcel($generator))->download('users-fastexcel.xlsx');
    }
}
?>

{{-- styling row and header --}}
<?php 
class UserControlller extends Controller
{
    public function export() {
        $header_style = (new StyleBuilder())
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor(Color::rgb(55, 55, 55)) // setBackgroundColor("EDEDED")
            ->setFontBold()
            ->build();

        $rows_style = (new StyleBuilder())
            ->setFontSize(15)
            ->setShouldWrapText(false)
            ->build();

        function usersGenerator() {
            $users = DB::table('users')->select(['id', 'name', 'email'])->orderBy('id', 'desc');

            foreach ($users->cursor() as $user) {
                yield $user;
            }
        }

        $generator = usersGenerator();

        return (new FastExcel($generator))
            ->headerStyle($header_style)
            ->rowsStyle($rows_style)
            ->download('users-fastexcel.xlsx');
    }
}
?>