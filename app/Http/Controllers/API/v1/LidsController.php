<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Lid;
use DB;
use Debugbar;

class LidsController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function create()
  {
    //
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
    Log::alert($request);
  }
  public function changelidsuser(Request $request)
  {
    $data = $request->all();
    $res = 0;
    foreach ($data['data'] as $lid) {
      $a_lid = [
        'user_id' => $data['user_id'],
        'updated_at' => Now()
      ];
      if (isset($data['status_id'])) $a_lid['status_id'] = $data['status_id'];
      $res =  DB::table('lids')->where('id', $lid['id'])->update($a_lid);
    }
    if ($res) {
      return response('Lids manager changed', 200);
    }
  }


  public function updatelids(Request $request)
  {
    $data = $request->all();
    // $data['data']['updated_at'] = Now();
    $res = 0;
    // foreach ($data['data'] as $lid) {
      // Debugbar::info($data['data']);
      $a_lid = [
        'status_id' => $data['data']['status_id'],
        'user_id' => $data['data']['user_id'],
        'updated_at' => Now()
      ];
      $res =  DB::table('lids')->where('id', $data['id'])->update($a_lid);

      $a_lid['tel'] = $data['data']['tel'];
      $a_lid['created_at'] = Now();

      DB::table('logs')->insert($a_lid);
    // }
    if ($res) {

      return response('Lids updated', 200);
    }
  }
  public function newlids(Request $request)
  {
    //  Log::alert($request);
    // $data = $this->validate($request, [
    //     'provider_id' => 'requered',
    //     'user_id' => 'requered',
    //     'data' => 'requered'
    //    ]);
    // Log::alert($request->all());
    $data = $request->all();


    foreach ($data['data'] as $lid) {
      $a_lid = [
        'name' => $lid['name'],
        'email' => $lid['email'],
        'user_id' => $data['user_id'],
        'created_at' => Now()
      ];
      if (isset($data['provider_id']))  $a_lid['provider_id'] = $data['provider_id'];
      if (isset($data['status_id']))  $a_lid['status_id'] = $data['status_id'];

      // $status_id = !empty($data['status_id']) ? $data['status_id'] : null;
      Lid::updateOrCreate(['tel' => $lid['tel']], $a_lid);
    }
    return response('Lids imported', 200);
  }

  public function userLids($id)
  {
    return Lid::all()->where('active', 1)->where('user_id', $id);
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
   * Show the form for editing the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function edit($id)
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
