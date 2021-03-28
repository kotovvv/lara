<?php

use App\Http\Controllers\API\v1\loginController;
use App\Http\Controllers\API\v1\ProvidersController;
use App\Http\Controllers\API\v1\UsersController;
use App\Http\Controllers\API\v1\LidsController;
use App\Http\Controllers\API\v1\LogsController;
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
Route::resource('users', UsersController::class);
Route::resource('lids', LidsController::class);

Route::get('statusall', [StatusesController::class,'getall'])->name('stasusall');
Route::get('providerall', [ProvidersController::class,'getall'])->name('providerall');
Route::post('status_provider', [ProvidersController::class,'status_provider'])->name('status_provider');
Route::post('status_users', [UsersController::class,'status_users'])->name('status_users');

Route::get('getusers', [UsersController::class,'getusers'])->name('getusers');
Route::get('users/getroles', [UsersController::class,'getroless'])->name('user.getroles');
Route::post('user/update', [UsersController::class,'update'])->name('user.update');
Route::get('userlids/{id}', [LidsController::class,'userLids'])->name('userlids');

Route::post('Lid/newlids', [LidsController::class,'newlids'])->name('Lid.newlids');
Route::post('Lid/updatelids', [LidsController::class,'updatelids'])->name('Lid.updatelids');
Route::post('Lid/changelidsuser', [LidsController::class,'changelidsuser'])->name('Lid.changelidsuser');
Route::post('log/add', [LogsController::class,'add'])->name('log.add');
Route::post('log/tellog', [LogsController::class,'tellog'])->name('log.tellog');

