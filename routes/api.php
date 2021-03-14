<?php

use App\Http\Controllers\API\v1\ProvidersController;
use App\Http\Controllers\API\v1\UsersController;
use App\Http\Controllers\API\v1\LidsController;
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

Route::resource('provider', ProvidersController::class);
Route::get('users/getusers', [UsersController::class,'getusers'])->name('user.getusers');

Route::post('Lid/newlids', [LidsController::class,'newlids'])->name('Lid.newlids');
