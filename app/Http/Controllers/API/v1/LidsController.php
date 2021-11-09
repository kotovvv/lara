<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Log;
use App\Models\Lid;
use App\Models\Log;
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

  public function importlid(Request $request)
  {
    $insertItem = $request->all();
    if ($insertItem['api_key'] != env('API_KEY')) return response('Key incorect', 403);
    $n_lid = new Lid;
    $n_lid->tel = $insertItem['umcfields']['phone'];
    $f_lid =  Lid::where('tel', '=', $n_lid->tel)->get();
    if (!$f_lid->isEmpty()) {
      $n_lid->status_id = 22;
    } else {
      $n_lid->status_id = 8;
    }
    $n_lid->name = $insertItem['umcfields']['name'];
    $n_lid->email = $insertItem['umcfields']['email'];
    $n_lid->afilyator = $insertItem['umcfields']['affiliate_user'];
    $n_lid->provider_id = $insertItem['afilat_id'];
    $n_lid->user_id = $insertItem['user_id'];
    $n_lid->created_at = Now();
    $n_lid->updated_at = Now();
    $n_lid->active = 1;
    $n_lid->save();

    // $res= DB::table('lids')->insert($a_lid);
    //$res = Lid::updateOrCreate(['tel' => $a_lid['tel'], 'provider_id' => $a_lid['provider_id'], 'afilyator' =>  $a_lid['afilyator']], $a_lid);


    return response('Lid inserted', 200);
  }


  public function searchlids(Request $request)
  {
    $searchTerm = $request->all();
    //  Debugbar::info($searchTerm);
    //SELECT * FROM lids WHERE `tel` LIKE '%488%' OR `name` LIKE '%488%' OR email LIKE '%488%' OR `text` LIKE '%488%'

    return Lid::select('*')
      ->where('name', 'like', "%{$searchTerm['search']}%")
      ->orwhere('tel', 'like', "%{$searchTerm['search']}%")
      ->orwhere('email', 'like', "%{$searchTerm['search']}%")
      ->orwhere('text', 'like', "%{$searchTerm['search']}%")
      ->get();
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
    foreach ($data['data'] as $lid) {


      $a_lid = [
        'status_id' => $lid['status_id'],
        'user_id' => $lid['user_id'],
        'updated_at' => Now()
      ];
      $res =  DB::table('lids')->where('id', $lid['id'])->update($a_lid);


      $a_lid['lid_id'] = $lid['id'];
      $a_lid['tel'] = $lid['tel'];
      $a_lid['created_at'] = Now();

      DB::table('logs')->insert($a_lid);
    }
    if ($res) {

      return response('Lids updated', 200);
    }
  }


  public function newlids(Request $request)
  {
    $data = $request->all();
    // Debugbar::info($data['data']);
    foreach ($data['data'] as $lid) {
      $n_lid = new Lid;

      $n_lid->name =  strval($lid['name']);
      $n_lid->tel =  $lid['tel'];
      $n_lid->email = $lid['email'];
      $n_lid->user_id = $data['user_id'];
      $n_lid->created_at = Now();

      if (isset($lid['afilyator'])) {
        $n_lid->afilyator = $lid['afilyator'];
      } else {
        $n_lid->afilyator = '';
      }
      if (isset($data['provider_id'])) $n_lid->provider_id = $data['provider_id'];
      if (isset($data['status_id']))  $n_lid->status_id = $data['status_id'];
      $f_lid =  Lid::where('tel', '=', $lid['tel'])->get();

      if (!$f_lid->isEmpty()) {
        $n_lid->status_id = 22;
      }
      $n_lid->save();
    }
    return response('Lids imported', 200);
  }

  public function userLids($id)
  {
    return Lid::where('user_id', $id)->orderBy('created_at', 'desc')->get();
  }

  public function statusLids($id)
  {
    return Lid::where('status_id', $id)->orderBy('created_at', 'desc')->get();
  }

  public function getuserLids(Request $request, $id)
  {
    $getlid = $request->all();
    if ($getlid['api_key'] != env('API_KEY')) return response('Key incorect', 403);
    return Lid::all()->where('user_id', $id);
  }

  public function getlidid(Request $request)
  {

    $getlidid = $request->all();
    if ($getlidid['api_key'] != env('API_KEY')) return response('Key incorect', 403);
    $a_lid = [
      'tel' => $getlidid['umcfields']['phone'],
      'afilyator' => $getlidid['umcfields']['affiliate_user'],
      'provider_id' => $getlidid['provider_id'],
    ];
    return Lid::select('id')->where($a_lid)->get();
  }

  public function getlidsontime(Request $request)
  {
    $req = $request->all();
    if ($req['api_key'] != env('API_KEY')) return response('Key incorect', 403);
    return Lid::select('id')->whereBetween('created_at', [$req['start'], $req['end']])->get();
  }


  public function getLidsOnDate($date)
  {
    $date = explode(',',$date);

    if(count($date) == 2){
      return Lid::whereBetween('created_at', [$date[0], $date[1]])->get();
    } else {
      return Lid::whereDate('created_at', $date[0])->get();
    }
  }

  public function getlidonid(Request $request, $id)
  {
    $req = $request->all();
    if ($req['api_key'] != env('API_KEY')) return response('Key incorect', 403);

    return Lid::where('id', $id)->get();
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function ontime(Request $request)
  {
    $data = $request->all();
    // Debugbar::info($data);

    $a_lid = [
      'ontime' => $data['ontime'],
      'updated_at' => Now()
    ];

    DB::table('lids')->where('id', $data['id'])->update($a_lid);

    return response('Lids add ontime', 200);
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

  public function deletelids(Request $request)
  {
    $lids = $request->all();
    // Debugbar::info($data);

    Lid::whereIn('id', $lids)->delete();
    Log::whereIn('lid_id', $lids)->delete();
  }


  public function set_zaliv(Request $request)
  {
    $req = $request->all();
    $f_key =   DB::table('apikeys')->where('api_key', $req['api_key'])->first();

    if (!$f_key) return response('Key incorect', 403);

    $n_lid = new Lid;

    $n_lid->name =  $req['umcfields']['name'];
    $n_lid->tel =  $req['umcfields']['phone'];
    $n_lid->email = $req['umcfields']['email'];
    $n_lid->afilyator = $req['umcfields']['affiliate_user'];
    $n_lid->provider_id = $req['afilat_id'];
    $n_lid->user_id = $req['user_id'];
    $n_lid->created_at = Now();

    $f_lid =  Lid::where('tel', '=', $n_lid->tel)->get();

    if (!$f_lid->isEmpty()) {
      return response('duplicate');
    }

    $n_lid->save();
    $id = $n_lid->id;
    $insert = DB::table('imported_leads')->insert(['lead_id' => $id, 'api_key_id' => $f_key->id, 'upload_time' => Now()]);


    $res['status'] = 'OK';
    $res['id'] = $id;
    $res['insart'] = $insert;
    return response($res);
  }

  public function get_zaliv(Request $request)
  {
    $req = $request->all();
    $f_key =   DB::table('apikeys')->where('api_key', $req['api_key'])->first();

    if (!$f_key) return response('Key incorect', 403);
$sql = "SELECT `lead_id` FROM `imported_leads` WHERE `api_key_id` = '".$f_key->id."' AND `lead_id` = '".$req['lead_id']."'";
$res['status'] = 'Error';
if( DB::select(DB::raw($sql))){
    $sql = "SELECT `name` FROM `statuses` WHERE `id` IN ( SELECT `status_id` FROM `lids` WHERE `id` = ".$req['lead_id'].")";
    $lid_status = DB::select(DB::raw($sql));
    $res['status'] = $lid_status;
    $res['id'] = $req['lead_id'];
}
    return response( $res);
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
