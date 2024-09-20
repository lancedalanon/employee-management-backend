<? 

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['status' => 'alive'], 200);
});
