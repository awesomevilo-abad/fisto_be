{{-- basic database usage --}}
<?php DB::connection('connection_name'); ?>

{{-- running a select query --}}
<?php
$results = DB::select('select * from users where id = ?', [1]);
$results = DB::select('select * from users where id = :id', ['id' => 1]);
?>

{{-- running a general statement --}}
<?php DB::statement('drop table users'); ?>

{{-- listening for query events --}}
<?php 
DB::listen(function($sql, $bindings, $time) { 
    // code_here; 
}); 
?>

{{-- database transactions --}}
<?php
DB::transaction(function() {
    DB::table('users')->update(['votes' => 1]);
    DB::table('posts')->delete();
});

DB::beginTransaction();
DB::rollback();
DB::commit();
?>

{{-- retrieving all rows from a table --}}
<?php DB::table('name')->get(); ?>

{{-- chunking results from a table --}}
<?php
DB::table('users')->chunk(100, function($users) {
    foreach ($users as $user) {} 
});
?>

{{-- retrieving a single row from a table --}}
<?php
$user = DB::table('users')->where('name', 'John')->first();
DB::table('name')->first();
?>

{{-- retrieving a single column from a row --}}
<?php
$name = DB::table('users')->where('name', 'John')->pluck('name');
DB::table('name')->pluck('column');
?>

{{-- retrieving a list of column values --}}
<?php
$roles = DB::table('roles')->lists('title');
$roles = DB::table('roles')->lists('title', 'name');
?>

{{-- specifying a select clause --}}
<?php
$users = DB::table('users')->select('name', 'email')->get();
$users = DB::table('users')->distinct()->get();
$users = DB::table('users')->select('name as user_name')->get();
?>

{{-- adding a select clause to an existing query --}}
<?php
$query = DB::table('users')->select('name');
$users = $query->addSelect('age')->get();
?>

{{-- using where operators --}}
<?php
$users = DB::table('users')->where('votes', '>', 100)->get();

$users = DB::table('users')
    ->where('votes', '>', 100)
    ->orWhere('name', 'John')
    ->get();

$users = DB::table('users')->whereBetween('votes', [1, 100])->get();
$users = DB::table('users')->whereNotBetween('votes', [1, 100])->get();
$users = DB::table('users')->whereIn('id', [1, 2, 3])->get();
$users = DB::table('users')->whereNotIn('id', [1, 2, 3])->get();
$users = DB::table('users')->whereNull('updated_at')->get();

DB::table('name')->whereNotNull('column')->get();
?>

{{-- dynamic where clauses --}}
<?php
$admin = DB::table('users')->whereId(1)->first();

$john = DB::table('users')
    ->whereIdAndEmail(2, 'john@doe.com')
    ->first();

$jane = DB::table('users')
    ->whereNameOrAge('Jane', 22)
    ->first();
?>

{{-- order by, group by, and having --}}
<?php
$users = DB::table('users')
    ->orderBy('name', 'desc')
    ->groupBy('count')
    ->having('count', '>', 100)
    ->get();
?>

<?php
DB::table('name')->orderBy('column')->get();
DB::table('name')->orderBy('column','desc')->get();
DB::table('name')->having('count', '>', 100)->get();
?>

{{-- offset & limit --}}
<?php $users = DB::table('users')->skip(10)->take(5)->get(); ?>

{{-- laravel joins --}}
{{-- basic join statement --}}
<?php
DB::table('users')
    ->join('contacts', 'users.id', '=', 'contacts.user_id')
    ->join('orders', 'users.id', '=', 'orders.user_id')
    ->select('users.id', 'contacts.phone', 'orders.price')
    ->get();
?>

{{-- left join statement --}}
<?php
DB::table('users')
    ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
    ->get();
?>

{{-- parameter grouping --}}
<?php
// sample 1:
// raw query: "select * from `users` where `name` = ‘John’ or (`votes` > 100 and `title` <> ‘Admin’)"
DB::table('users')
    ->where('name', '=', 'John')
    ->orWhere(function($query) {
            $query->where('votes', '>', 100)->where('title', '<>', 'Admin');
    })
    ->get();

// sample 2:
// raw query: "select * from `users` where `gender` = ? and  (`birth_date` > ? or `birth_date` <= ?)"
DB::table('users')
    ->where('gender', '=', 'Male')
    ->where(function($query) {
        $query->where('birth_date', '>', now()->subYears(9))
            ->orWhere('birth_date', '<=', now()->subYears(65));
    })
    ->get();
?>

{{-- laravel aggregates --}}
<?php
$users = DB::table('users')->count();
$price = DB::table('orders')->max('price');
$price = DB::table('orders')->min('price');
$price = DB::table('orders')->avg('price');
$total = DB::table('users')->sum('votes');

DB::table('name')->remember(5)->get();
DB::table('name')->remember(5, 'cache-key-name')->get();
DB::table('name')->cacheTags('my-key')->remember(5)->get();
DB::table('name')->cacheTags(array('my-first-key','my-second-key'))->remember(5)->get();
?>

{{-- laravel raw expressions --}}
<?php
$users = DB::table('users')
    ->select(DB::raw('count(*) as user_count, status'))
    ->where('status', '<>', 1)
    ->groupBy('status')
    ->get();
?>

{{-- return rows --}}
<?php DB::select('select * from users where id = ?', array('value')); ?>

{{-- return nr affected rows --}}
<?php
DB::insert('insert into foo set bar=2');
DB::update('update foo set bar=2');
DB::delete('delete from bar');
?>

{{-- returns void --}}
<?php DB::statement('update foo set bar=2'); ?>

{{-- raw expression inside a statement --}}
<?php DB::table('name')->select(DB::raw('count(*) as count, column2'))->get(); ?>

{{-- laravel inserts / updates / deletes / unions / pessimistic locking --}}
{{-- inserts --}}
<?php
DB::table('users')->insert(
    ['email' => 'john@example.com', 'votes' => 0]
);

$id = DB::table('users')->insertGetId(
    ['email' => 'john@example.com', 'votes' => 0]
);

DB::table('users')->insert([
    ['email' => 'taylor@example.com', 'votes' => 0],
    ['email' => 'dayle@example.com', 'votes' => 0]
]);
?>

{{-- updates --}}
<?php
DB::table('users')
    ->where('id', 1)
    ->update(['votes' => 1]);

DB::table('users')->increment('votes');
DB::table('users')->increment('votes', 5);
DB::table('users')->decrement('votes');
DB::table('users')->decrement('votes', 5);
DB::table('users')->increment('votes', 1, ['name' => 'John']);
?>

{{-- deletes --}}
<?php
DB::table('users')->where('votes', '<', 100)->delete();
DB::table('users')->delete();
DB::table('users')->truncate();
?>

{{-- unions. the unionAll() method is also available, and has the same method signature as union. --}}
<?php
$first = DB::table('users')->whereNull('first_name');
$users = DB::table('users')->whereNull('last_name')->union($first)->get();
?>

{{-- pessimistic locking --}}
<?php
DB::table('users')->where('votes', '>', 100)->sharedLock()->get();
DB::table('users')->where('votes', '>', 100)->lockForUpdate()->get();
?>

{{-- getting the latest row --}}
<?php $last_row = DB::table('jobbands')->latest('order')->first(); ?>

{{-- updating multiple row with specific id's --}}
{{-- 
payload: [
    {id: 4, employee_id: 1, form_setting_id: 2, level: 1, action: "note", approved_mark: "alright",…},
    {id: 1, employee_id: 1, form_setting_id: 2, level: 2, action: "approve",…},
    {id: 5, employee_id: 1, form_setting_id: 2, level: 3, action: "assess", approved_mark: "asdfaf",…}
];
--}}
{{-- code in route: --}}
<?php Route::put('form_approvers/order', [FormApproverController::class, 'changeOrder']); ?>
{{-- code in controller: --}}
<?php 
function changeOrder() {
    $form_approvers = request('payload');

    foreach ($form_approvers as $form_approver) {
        FormApprover::find($form_approver['id'])->update(['level' => $form_approver['level']]); // update each 'level' column
    }

    return response()->json($form_approvers);
}
?>

{{-- fetching the latest record with join relationship --}}
<?php 
$data = DB::table('employees')
    ->leftJoin('employee_statuses', function($join) { 
        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
    })
    ->select([
        'employees.id',
        'employees.first_name',
        'employee_statuses.employment_type',
    ])
    ->where('employee_statuses.employment_type', '=', 'probationary')
    ->get();
?>

{{-- fetching record if id not exist in another table --}}
<?php 
$form_type = $request->input('form_type');

$subunits = DB::table('subunits')
    ->select([
        'subunits.id', 
        'subunits.code',
        'subunits.department_id', 
        'subunits.subunit_name', 
        'subunits.created_at',
    ])
    ->whereNotExists(function ($query) use ($form_type) {
        $query->select(DB::raw(1))
        ->from('forms')
        ->whereRaw('subunits.id = forms.subunit_id')
        ->where('forms.form_type', '=', $form_type);
    })
    ->get();
?>