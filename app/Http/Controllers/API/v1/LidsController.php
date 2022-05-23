<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Log;
use App\Models\Lid;
use App\Models\Log;
use App\Models\Depozit;
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
    // if ($insertItem['api_key'] != env('API_KEY')) return response(['status'=>'Key incorect'], 403);
    $f_key =   DB::table('apikeys')->where('api_key', $insertItem['api_key'])->first();
    if (!$f_key) return response(['status' => 'Key incorect'], 403);

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
      $a_lid['text'] = $lid['text'] ?$lid['text']:'';
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

      if (isset($lid['name'])) {
        $n_lid->name =  $lid['name'];
      } else {
        $n_lid->name = time();
      }

      if (isset($lid['tel'])) {
        $n_lid->tel =   $lid['tel'];
      } else {
        $n_lid->tel = time();
      }

      if (isset($lid['email'])) {
        $n_lid->email = $lid['email'];
      } else {
        $n_lid->email = time() . '@none.com';
      }


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
    return Lid::select('lids.*', 'depozits.depozit')->leftJoin('depozits', 'lids.id', '=', 'depozits.lid_id')->where('lids.user_id', $id)->orderBy('lids.created_at', 'desc')->get();
  }

  public function statusLids($id)
  {
    return Lid::select('lids.*', 'depozits.depozit')->leftJoin('depozits', 'lids.id', '=', 'depozits.lid_id')->where('lids.status_id', $id)->orderBy('lids.created_at', 'desc')->get();
  }

  public function getuserLids(Request $request, $id)
  {
    $getlid = $request->all();
    // if ($getlid['api_key'] != env('API_KEY')) return response(['status'=>'Key incorect'], 403);
    $f_key =   DB::table('apikeys')->where('api_key', $getlid['api_key'])->first();
    if (!$f_key) return response(['status' => 'Key incorect'], 403);
    return Lid::all()->where('user_id', $id);
  }

  public function getlidid(Request $request)
  {

    $getlidid = $request->all();
    // if ($getlidid['api_key'] != env('API_KEY')) return response(['status'=>'Key incorect'], 403);
    $f_key =   DB::table('apikeys')->where('api_key', $getlidid['api_key'])->first();
    if (!$f_key) return response(['status' => 'Key incorect'], 403);
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
    // if ($req['api_key'] != env('API_KEY')) return response(['status'=>'Key incorect'], 403);
    $f_key =   DB::table('apikeys')->where('api_key', $req['api_key'])->first();
    if (!$f_key) return response(['status' => 'Key incorect'], 403);
    return Lid::select('id')->whereBetween('created_at', [$req['start'], $req['end']])->get();
  }


  public function getLidsOnDate(Request $request)
  {
    $req = $request->all();

    $date = [$req['datefrom'], $req['dateto']];


    $where_user = $req['user_id'] > 0 ? ' l.user_id = ' . (int) $req['user_id'] . ' AND ' : '';
    $sql = "SELECT l.*,d.depozit FROM lids l LEFT JOIN depozits d ON (l.id = d.lid_id) WHERE " . $where_user . " l.created_at >= '" . $date[0] . "' AND l.created_at <= '" . $date[1] . "'";
    return DB::select(DB::raw($sql));


    // if (count($date) == 2) {
    $sql = "SELECT l.*,d.depozit FROM lids l LEFT JOIN depozits d ON (l.id = d.lid_id) WHERE l.created_at >= '" . $date[0] . "' AND l.created_at <= '" . $date[1] . "'";
    return DB::select(DB::raw($sql));
    // } else {
    //   return Lid::whereDate('created_at', $date[0])->get();
    // }
  }

  public function getlidonid(Request $request, $id)
  {
    $req = $request->all();
    $f_key =   DB::table('apikeys')->where('api_key', $req['api_key'])->first();
    if (!$f_key) return response(['status' => 'Key incorect'], 403);

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

    if (!$f_key) return response(['status' => 'Key incorect'], 403);

    $n_lid = new Lid;

    if (isset($req['umcfields']['name']) && strlen($req['umcfields']['name']) > 1) {
      $n_lid->name =  $req['umcfields']['name'];
    } else {
      $n_lid->name = time();
    }

    if (isset($req['umcfields']['phone']) && strlen($req['umcfields']['phone']) > 1) {
      $n_lid->tel =  $req['umcfields']['phone'];
    } else {
      $n_lid->tel = time();
    }

    if (isset($req['umcfields']['email']) && strlen($req['umcfields']['email']) > 1) {
      $n_lid->email = $req['umcfields']['email'];
    } else {
      $n_lid->email = time() . '@none.com';
    }

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
    $res['insert'] = $insert;
    return response($res);
  }


  public function set_zalivjs(Request $request)
  {
    $req = $request->all();
    $f_key =   DB::table('apikeys')->where('api_key', $req['api_key'])->first();

    if (!$f_key) return response(['status' => 'Key incorect'], 403);

    $n_lid = new Lid;

    if (isset($req['umcfields']['name']) && strlen($req['umcfields']['name']) > 1) {
      $n_lid->name =  $req['umcfields']['name'];
    } else {
      $n_lid->name = time();
    }

    if (isset($req['umcfields']['phone']) && strlen($req['umcfields']['phone']) > 1) {
      $n_lid->tel =  $req['umcfields']['phone'];
    } else {
      $n_lid->tel = time();
    }

    if (isset($req['umcfields']['email']) && strlen($req['umcfields']['email']) > 1) {
      $n_lid->email = $req['umcfields']['email'];
    } else {
      $n_lid->email = time() . '@none.com';
    }

    $n_lid->afilyator = $req['umcfields']['affiliate_user'];
    $n_lid->provider_id = $req['afilat_id'];
    $n_lid->user_id = $req['user_id'];
    $n_lid->created_at = Now();

    $f_lid =  Lid::where('tel', '=', $n_lid->tel)->get();

    if (!$f_lid->isEmpty()) {

      $res['status'] = 'duplicate';
      return response($res);
    }

    $n_lid->save();
    $id = $n_lid->id;
    $insert = DB::table('imported_leads')->insert(['lead_id' => $id, 'api_key_id' => $f_key->id, 'upload_time' => Now()]);

    $res['status'] = 'OK';
    $res['id'] = $id;
    $res['insert'] = $insert;
    return response($res);
  }


  public function  set_zalivDub(Request $request)
  {
    $req = $request->all();
    $f_key =   DB::table('apikeys')->where('api_key', $req['api_key'])->first();

    if (!$f_key) return response(['status' => 'Key incorect'], 403);
    // http://91.192.102.34/api/set_zaliv?user_id=152&afilat_id=62&api_key=11e9c0056d4aa76c3c7b946737f089d4&umcfields[email]=$email&umcfields[name]=$fio%20$lastn&umcfields[phone]=$phonestr&umcfields[affiliate_user]=$affiliate
    $n_lid = new Lid;

    if (isset($req['umcfields']['name']) && strlen($req['umcfields']['name']) > 1) {
      $n_lid->name =  $req['umcfields']['name'];
    } else {
      $n_lid->name = time();
    }

    if (isset($req['umcfields']['phone']) && strlen($req['umcfields']['phone']) > 1) {
      $n_lid->tel =  $req['umcfields']['phone'];
    } else {
      $n_lid->tel = time();
    }
    $n_lid->email = $req['umcfields']['email'];
    $n_lid->afilyator = $req['umcfields']['affiliate_user'];
    $n_lid->provider_id = $req['afilat_id'];
    $n_lid->user_id = $req['user_id'];
    $n_lid->created_at = Now();

    $f_lid =  Lid::where('tel', '=', $n_lid->tel)->get();

    if (!$f_lid->isEmpty()) {
      $n_lid->status_id = 22;
    }

    $n_lid->save();
    $id = $n_lid->id;
    $insert = DB::table('imported_leads')->insert(['lead_id' => $id, 'api_key_id' => $f_key->id, 'upload_time' => Now()]);

    $res['status'] = 'OK';
    $res['id'] = $id;
    $res['insert'] = $insert;
    return response($res);
  }

  public function get_zaliv(Request $request)
  {
    $req = $request->all();
    $f_key =   DB::table('apikeys')->where('api_key', $req['api_key'])->first();

    if (!$f_key) return response(['status' => 'Key incorect'], 403);
    $sql = "SELECT `lead_id` FROM `imported_leads` WHERE `api_key_id` = '" . $f_key->id . "' AND `lead_id` = '" . $req['lead_id'] . "'";
    $res['result'] = 'Error';
    if (DB::select(DB::raw($sql))) {
      $sql = "SELECT `name` FROM `statuses` WHERE `id` IN ( SELECT `status_id` FROM `lids` WHERE `id` = " . $req['lead_id'] . ")";
      $lid_status = DB::select(DB::raw($sql));
      $sql = "SELECT * FROM `lids` WHERE `id` = " . $req['lead_id'] . " LIMIT 1";
      $lid = DB::select(DB::raw($sql));
      $res['result'] = "success";
      $res['afilateName'] = $lid[0]->afilyator;
      $res['name'] = $lid[0]->name;
      $res['email'] = $lid[0]->email;
      $res['phone'] = $lid[0]->tel;
      $res['status'] = $lid_status[0]->name;
      $res['lead_id'] = $req['lead_id'];
      $res['ftd'] = 0;
      if ($req['lead_id'] == 10) $res['ftd'] = 1;
    }
    return response($res);
  }

  public function get_zaliv_all(Request $request)
  {
    $req = $request->all();
    $f_key =   DB::table('apikeys')->where('api_key', $req['api_key'])->first();
    if (!$f_key) return response(['status' => 'Key incorect'], 403);
    $res['result'] = 'Error';
    $date = '';
    if (isset($req['date'])) {
      $date = $req['date'] == 'y' ? ', l.created_at , l.updated_at' : '';
    }

    $sql = "SELECT l.name,l.tel,l.afilyator,l.status_id,l.email,l.id,s.name statusName " . $date . " FROM `lids` l LEFT JOIN statuses s on (s.id = l.status_id ) WHERE l.`id` IN (SELECT `lead_id` FROM `imported_leads` WHERE `api_key_id` = " . $f_key->id . ")";
    $lids = DB::select(DB::raw($sql));
    if ($lids) {
      $res['data'] = [];
      $res['result'] = "success";
      $res['rows'] = 0;
      foreach ($lids as $lid) {
        $ftd = $lid->status_id == 10 ? 1 : 0;
        $a1 = [
          'afilateName' => $lid->afilyator,
          'name' => $lid->name,
          'email' => $lid->email,
          'phone' => $lid->tel,
          'status' => $lid->statusName,
          'lead_id' => $lid->id,
          'ftd' => $ftd
        ];
        if (isset($req['date'])) {
          $a1['datestart'] = $lid->created_at;
          $a1['dateupdate'] = $lid->updated_at;
        }
        $res['data'][] = $a1;
        $res['rows']++;
      }
    }
    return response($res);
  }

  public function get_zaliv_allTime(Request $request)
  {
    // get_zaliv_allTime?api_key=857193747ca93f651b1f32dcf426ab42&startDate=10.07.2021&endDate=14.01.2022

    $req = $request->all();

    $f_key = DB::table('apikeys')->where('api_key', $req['api_key'])->first();
    if (!$f_key) return response(['status' => 'Key incorect'], 403);
    $res['result'] = 'Error';
    $sql = "SELECT l.name,l.tel,l.afilyator,l.status_id,l.email,l.id,s.name statusName FROM `lids` l LEFT JOIN statuses s on (s.id = l.status_id ) WHERE l.`id` IN (SELECT `lead_id` FROM `imported_leads` WHERE `api_key_id` = " . $f_key->id . " AND DATE(`upload_time`) >= " . $req['startDate'] . " AND DATE(`upload_time`) <= " . $req['endDate'] . " )";

    $lids = DB::select(DB::raw($sql));
    if ($lids) {
      $res['data'] = [];
      $res['result'] = "success";
      $res['rows'] = 0;
      foreach ($lids as $lid) {
        $ftd = $lid->status_id == 10 ? 1 : 0;
        $res['data'][] = [
          'afilateName' => $lid->afilyator,
          'name' => $lid->name,
          'email' => $lid->email,
          'phone' => $lid->tel,
          'status' => $lid->statusName,
          'lead_id' => $lid->id,
          'ftd' => $ftd
        ];
        $res['rows']++;
      }
    }
    return response($res);
  }


  public function setDepozit(Request $request)
  {
    $req = $request->all();
    $req['created_at'] = Now();
    $res = Depozit::create($req);

    return response($res);
  }

  public function getHmLidsUser($id)
  {

    $hm = Lid::where('user_id', $id)->count();

    return response($hm);
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
