<?php

use App\Http\Controllers\API\v1\loginController;
use App\Http\Controllers\API\v1\ProvidersController;
use App\Http\Controllers\API\v1\UsersController;
use App\Http\Controllers\API\v1\LidsController;
use App\Http\Controllers\API\v1\StatusesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/login',[loginController::class,'login']);

Route::resource('provider', ProvidersController::class);
Route::resource('statuses', StatusesController::class);
Route::get('users/getusers', [UsersController::class,'getusers'])->name('user.getusers');

Route::post('Lid/newlids', [LidsController::class,'newlids'])->name('Lid.newlids');
Route::post('Lid/updatelids', [LidsController::class,'updatelids'])->name('Lid.updatelids');
Route::get('userlids/{id}', [LidsController::class,'userLids'])->name('userlids');

Route::resource('lids', LidsController::class);
