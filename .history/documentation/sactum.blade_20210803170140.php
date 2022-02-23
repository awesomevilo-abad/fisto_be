{{-- refer to the documentation for installation: --}}
{{-- https://laravel.com/docs/8.x/sanctum#installation --}}

{{-- install sanctum --}}
composer require laravel/sanctum

{{-- publish sanctum configuration --}}
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

{{-- migrate sanctum migration --}}
php artisan migrate

{{-- add sanctum middleware to api group --}}
{{-- code in app/Http/Kernel.php --}}
<?php
$middlewareGroups = [
    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ]
];
?>

{{-- code in config/cors.php --}}
<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false, // change value to 'true' if spa client is hosted in another domain
];
?>

{{-- code in api.php --}}
<?php
// protect route using sanctum middleware
Route::middleware('auth:sanctum')->get('/secrets', [SecretController::class, 'index']);

Route::post('/token', function (Request $request) {
    if (Auth::attempt($request->only('email', 'password'))) {
        // create token from login request
        $token = $request->user()->createToken('developer-access');

        // get all tokens of the user
        $tokens = [];
        foreach ($user->tokens as $token) {
            $tokens[] = $token;
        }

        // delete a token
        $user->tokens()->delete();

        // create token with abilities
        $user = User::find(9);
        $token = $user->createToken('developer-access', ['categories-list']);

        return ['token' => $token->plainTextToken]; // return unhashed token
    }
});

Route::post('/login', function (Request $request) {
    $data = $request->validate([
        'email' => 'required',
        'password' => 'required'
    ]);

    if (Auth::attempt($request->only('email', 'password'))) {
        return auth()->user(); // return authenticated user data
    }
});
?>

{{-- code in controller --}}
<?php
class SecretController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); // protect controller
    }

    public function index(Request $request)
    {
        $id = Auth::user()->id; // get authenticated user id
        $user = User::find($id);

        // if (!$user->tokenCan('categories-show')) {
        //     abort(401, 'Unauthorized');
        // }

        // check if user has the token permission
        if (!auth()->user()->tokenCan('categories-show')) {
            abort(401, 'Unauthorized');
        }

        return $request->user()->secrets;
    }
}
?>

{{-- front end setup --}}
{{-- vue spa --}}
{{-- code in app.js --}}
<script>
    window.axios.defaults.withCredentials = true;
</script>

{{-- method in handling login --}}
<script>
    handleLogin() {
        axios.get('/sanctum/csrf-cookie').then(response => {
            console.log(response);

            axios.post('api/login', this.formData).then(response => {
                console.log(response);
            });
        });
    },
</script>
