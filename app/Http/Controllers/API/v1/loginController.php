<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Hash;
use App\Models\Provider;

class loginController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
    //
  }
  public function login(Request $request)
  {
    $provider = Provider::where('name', $request->name)->first();

    if ($provider && Hash::check($request->password, $provider['password'])) {
      $provider->role_id = 4;
      return response()->json([
        'status'   => 'success',
        'user' =>  $provider,
      ]);
    }
    if (Auth::attempt(['name' => $request->name, 'password' => $request->password])) {
      $user                  = Auth::user();
      return response()->json([
        'status'   => 'success',
        'user' => $user,
      ]);
    } else {
      return response()->json([
        'status' => 'error',
        'user'   => 'Unauthorized Access'
      ]);
    }
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request)
  {
    //
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show($id)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, $id)
  {
    //
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
    //
  }
}
