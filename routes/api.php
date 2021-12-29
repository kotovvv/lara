<?php

use App\Http\Controllers\API\v1\loginController;
use App\Http\Controllers\API\v1\ProvidersController;
use App\Http\Controllers\API\v1\UsersController;
use App\Http\Controllers\API\v1\LidsController;
use App\Http\Controllers\API\v1\LogsController;
use App\Http\Controllers\API\v1\StatusesController;
use App\Http\Controllers\API\v1\ImportsController;
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
Route::resource('imports', ImportsController::class);

Route::get('statusall', [StatusesController::class,'getall'])->name('stasusall');
Route::get('providerall', [ProvidersController::class,'getall'])->name('providerall');
Route::post('status_provider', [ProvidersController::class,'status_provider'])->name('status_provider');
Route::post('status_users', [UsersController::class,'status_users'])->name('status_users');

Route::get('getusers', [UsersController::class,'getusers'])->name('getusers');
Route::post('getusers', [UsersController::class,'getrelatedusers'])->name('getrelatedusers');
Route::get('users/getroles', [UsersController::class,'getroless'])->name('user.getroles');
Route::post('user/update', [UsersController::class,'update'])->name('user.update');
Route::get('userlids/{id}', [LidsController::class,'userLids'])->name('userlids');
Route::get('statuslids/{id}', [LidsController::class,'statusLids'])->name('statuslids');
Route::get('getuserlids/{id}', [LidsController::class,'getuserLids'])->name('getuserlids');
Route::delete('provider/{id}', [ProvidersController::class,'destroy']);
Route::delete('user/{id}', [UsersController::class,'deleteuser']);
Route::get('lastBalans/{id}', [UsersController::class,'lastBalans']);
Route::get('getBalansMonth/{id}', [UsersController::class,'getBalansMonth']);
Route::get('getStatusesMonth/{id}', [UsersController::class,'getStatusesMonth']);
Route::get('getDepozitsMonth/{id}', [UsersController::class,'getDepozitsMonth']);
Route::get('getCallsMonth/{id}', [UsersController::class,'getCallsMonth']);

Route::post('Lid/newlids', [LidsController::class,'newlids'])->name('Lid.newlids');
Route::post('Lid/updatelids', [LidsController::class,'updatelids'])->name('Lid.updatelids');
Route::get('Lid/searchlids', [LidsController::class,'searchlids'])->name('Lid.searchlids');
Route::get('getlidid', [LidsController::class,'getlidid'])->name('getlidid');
Route::get('getlidsontime', [LidsController::class,'getlidsontime'])->name('getlidsontime');
Route::get('getLidsOnDate/{date}', [LidsController::class,'getLidsOnDate'])->name('getLidsOnDate');
Route::get('getlidonid/{id}', [LidsController::class,'getlidonid'])->name('getlidonid');
Route::get('set_data', [LidsController::class,'importlid'])->name('Lid.importlid');
Route::post('Lid/changelidsuser', [LidsController::class,'changelidsuser'])->name('Lid.changelidsuser');
Route::post('Lid/ontime', [LidsController::class,'ontime'])->name('Lid.ontime');
Route::post('Lid/deletelids', [LidsController::class,'deletelids'])->name('Lid.deletelids');
Route::post('log/add', [LogsController::class,'add'])->name('log.add');
Route::post('log/tellog', [LogsController::class,'tellog'])->name('log.tellog');
Route::get('getlogonid/{id}', [LogsController::class,'getlogonid'])->name('getlogonid');
Route::get('StasusesOfId/{id}', [LogsController::class,'StasusesOfId'])->name('StasusesOfId');
Route::get('set_zaliv', [LidsController::class,'set_zaliv'])->name('set_zaliv');
Route::get('get_zaliv', [LidsController::class,'get_zaliv'])->name('get_zaliv');
Route::get('get_zaliv_all', [LidsController::class,'get_zaliv_all'])->name('get_zaliv_all');
Route::post('setDepozit', [LidsController::class,'setDepozit'])->name('setDepozit');
Route::get('getHmLidsUser/{id}', [LidsController::class,'getHmLidsUser'])->name('getHmLidsUser');
