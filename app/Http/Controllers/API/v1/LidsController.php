<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Log;
use App\Models\Lid;
use App\Models\Log;
use App\Models\Depozit;
use App\Models\User;
use App\Models\Provider;
use Barryvdh\Debugbar\Twig\Extension\Debug;
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

    $f_key =   DB::table('apikeys')->where('api_key', $insertItem['api_key'])->first();
    if (!$f_key) return response(['status' => 'Key incorect'], 403);

    $n_lid = new Lid;
    $n_lid->tel = $insertItem['umcfields']['phone'];
    $f_lid =  Lid::where('tel', '=', $n_lid->tel)->get();
    if (!$f_lid->isEmpty() &&  $n_lid->provider_id != '76') {
      $n_lid->status_id = 22;
    } else {
      $n_lid->status_id = 8;
    }
    $n_lid->name = $insertItem['umcfields']['name'];
    $n_lid->email = $insertItem['umcfields']['email'];
    $n_lid->afilyator = $insertItem['umcfields']['affiliate_user'];
    $n_lid->provider_id = $f_key->id;
    $n_lid->user_id = $insertItem['user_id'];
    $n_lid->office_id = User::where('id', (int) $insertItem['user_id'])->value('office_id');
    $n_lid->created_at = Now();
    $n_lid->updated_at = Now();
    $n_lid->active = 1;
    $n_lid->save();

    return response('Lid inserted', 200);
  }


  public function searchlids(Request $request)
  {
    $data = $request->all();
    if($data['search'] == '') {
      return response((object) []);
    }
    $office_id = session()->get('office_id');
    $search = $data['search'];
    if (isset($data['role_id']) && isset($data['group_id']) && $data['role_id'] == 2) {
      $a_user_ids = User::select('id')->where('group_id', $data['group_id']);
      return Lid::select('*')
        ->whereIn('user_id', $a_user_ids)
        ->when($office_id > 0, function ($query) use ($office_id) {
          return $query->where('office_id', $office_id);
        })
        ->where(function ($query) use ($search) {
          return $query->where('name', 'like', "%{$search}%")
            ->orwhere('tel', 'like', "%{$search}%")
            ->orwhere('email', 'like', "%{$search}%")
            ->orwhere('text', 'like', "%{$search}%");
        })
        ->get();
    } else {
      return Lid::select('*')
        ->when($office_id > 0, function ($query) use ($office_id) {
          return $query->where('office_id', $office_id);
        })
        ->where(function ($query) use ($search) {
          return $query->where('name', 'like', "%{$search}%")
            ->orwhere('tel', 'like', "%{$search}%")
            ->orwhere('email', 'like', "%{$search}%")
            ->orwhere('text', 'like', "%{$search}%");
        })
        ->get();
    }

    return response((object) []);
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
   * Store a newly updated resource in storage.
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
        'office_id' => User::where('id', (int) $data['user_id'])->value('office_id'),
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
        'office_id' => User::where('id', (int) $lid['user_id'])->value('office_id'),
        'updated_at' => Now()
      ];
      if (isset($lid['text']) && strlen(trim($lid['text'])) > 0) {
        $a_lid['text'] = $lid['text'];
      }
      $res =  DB::table('lids')->where('id', $lid['id'])->update($a_lid);


      $a_lid['lid_id'] = $lid['id'];
      $a_lid['tel'] = $lid['tel'];

      $a_lid['text'] = isset($lid['text']) ? $lid['text'] : '';
      $a_lid['created_at'] = Now();
      unset($a_lid['office_id']);

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
      $n_lid->office_id = User::where('id', (int) $data['user_id'])->value('office_id');
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
      if ($n_lid->provider_id == '76') {
        $n_lid->status_id = 8;
      }
      $n_lid->save();
    }
    return response('Lids imported', 200);
  }

  public function userLids($id)
  {
    $office_id = session()->get('office_id');
    $providers = Provider::select('id')->where('office_id', 'REGEXP', '[^0-9]' . $office_id . '[^0-9]')->get()->toArray();
    return Lid::select('lids.*', 'depozits.depozit')->distinct()->leftJoin('depozits', 'lids.id', '=', 'depozits.lid_id')->where('lids.user_id', $id)
      ->when($office_id > 0, function ($query) use ($office_id, $providers) {
        return $query->where('office_id', $office_id)->whereIn('provider_id', $providers);
      })->orderBy('lids.created_at', 'desc')->get();
  }

  public function statusLids(Request $request)
  {
    $data = $request->all();
    if (isset($data['role_id']) && isset($data['group_id']) && $data['role_id'] == 2) {
      $a_user_ids = User::select('id')->where('group_id', $data['group_id']);
      return Lid::select('lids.*', 'depozits.depozit')->leftJoin('depozits', 'lids.id', '=', 'depozits.lid_id')->whereIn('lids.user_id', $a_user_ids)->where('lids.status_id', $data['id'])->orderBy('lids.created_at', 'desc')->get();
    } else {
      $office_id = session()->get('office_id');
      return Lid::select('lids.*', 'depozits.depozit')->leftJoin('depozits', 'lids.id', '=', 'depozits.lid_id')->where('lids.status_id', $data['id'])->when($office_id > 0, function ($query) use ($office_id) {
        return $query->where('office_id', $office_id);
      })->orderBy('lids.created_at', 'desc')->get();
    }
  }

  public function InfoDeposit(Request $request)
  {
    $getparams = $request->all();
    $f_key =   DB::table('apikeys')->where('api_key', $getparams['api_key'])->first();
    if (!$f_key) return response(['status' => 'Key incorect'], 403);
    // $sql = 'SELECT  "Success" AS status,"1" AS status_code, `lid_id` AS order_lead_id, `created_at` AS ftd_date, "FTD=1" AS description  FROM `depozits` WHERE `lid_id` = ' . (int) $getparams['id'];
    $leads = Depozit::select(DB::raw('"Success" as status, 1 as status_code, `lid_id` as  order_lead_id, `created_at` as ftd_date, "FTD=1" as description'))->where('lid_id', (int) $getparams['id'])->first();
    $leads['dateAdd'] = date('Y-m-d H:i:s', strtotime(Lid::where('id', (int) $getparams['id'])->value('created_at')));
    $response = [];
    $response["status"] = "Success";
    $response["status_code"] = "1";
    if ($leads) {
      $response["leads"] = $leads;
    } else {
      $response["leads"] = 'no lids';
    }
    return response($response);
  }


  public function AllDeposits(Request $request)
  {
    $getparams = $request->all();


    $date = [$getparams['from_date'], $getparams['to_date']];
    // if ($getlid['api_key'] != env('API_KEY')) return response(['status'=>'Key incorect'], 403);
    $f_key =  DB::table('apikeys')->where('api_key', $getparams['api_key'])->first();

    if (!$f_key) return response(['status' => 'Key incorect'], 403);
    $sql = "SELECT
    d.`lid_id` AS `order_lead_id`
    , d.`created_at` AS `ftd_date`
    , l.`created_at` AS 'dateAdd'
    ,'FTD=1' AS description
FROM
    `depozits` d
    INNER JOIN `lids` l
        ON (d.`lid_id` = l.`id`)
WHERE (l.`provider_id` = '" . $f_key->id . "'
    AND d.`created_at` BETWEEN '" . $date[0] . "' AND  '" . $date[1] . "')";

    $leads =  DB::select(DB::raw($sql));
    $response = [];
    $response["status"] = "Success";
    $response["status_code"] = "1";
    if ($leads) {
      $response["leads"] = $leads;
    } else {
      $response["leads"] = 'no lids';
    }
    return response($response);
  }

  public function getuserLids(Request $request, $id)
  {
    $getlid = $request->all();
    $office_id = session()->get('office_id');
    // if ($getlid['api_key'] != env('API_KEY')) return response(['status'=>'Key incorect'], 403);
    $f_key =   DB::table('apikeys')->where('api_key', $getlid['api_key'])->first();
    if (!$f_key) return response(['status' => 'Key incorect'], 403);
    return Lid::all()->where('user_id', $id)->when($office_id > 0, function ($query) use ($office_id) {
      return $query->where('office_id', $office_id);
    });
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
      'provider_id' => $f_key,
    ];
    return Lid::select('id')->where($a_lid)->get();
  }

  public function getlidsontime(Request $request)
  {
    $req = $request->all();
    $f_key =   DB::table('apikeys')->where('api_key', $req['api_key'])->first();
    if (!$f_key) return response(['status' => 'Key incorect'], 403);
    return Lid::select('id')->where('provider_id', $f_key->id)->whereBetween('created_at', [$req['start'], $req['end']])->get();
  }


  public function getLidsOnDate(Request $request)
  {
    $req = $request->all();
    $where_status = '';
    if (isset($req['statuses']) && count($req['statuses']) > 0) {
      $where_status = ' l.status_id in (' . implode(', ', $req['statuses']) . ') AND ';
    }
    if (isset($req['office_ids'])) {
      $where_user = count($req['office_ids']) > 0 ? "  `office_id` in (" . implode(',', $req['office_ids']) . ") AND " . $where_status : $where_status;
    } else {
      $office_id = session()->get('office_id');
      $where = $office_id > 0 ? "  l.`office_id` = " . $office_id . " AND " : "";
      $where_user = isset($req['user_id']) && $req['user_id'] > 0 ? ' l.user_id = ' . (int) $req['user_id'] . ' AND ' : '1=1 AND ' . $where . $where_status;
    }

    $date = [date('Y-m-d', strtotime($req['datefrom'])) . ' ' . date("H:i:s", mktime(0, 0, 0)), date('Y-m-d', strtotime($req['dateto'])) . ' ' . date("H:i:s", mktime(23, 59, 59))];

    $sql = "SELECT l.`id`,l.`tel`,l.`name`,l.`email`,l.`provider_id`,l.`afilyator`,l.`status_id`,l.`user_id`,l.`created_at`,l.`updated_at`,l.`status_id`,l.`office_id`,if(l.`qtytel` > 0, l.`qtytel`,'') qtytel,l.`text`, (SELECT sum(depozit) depozit FROM depozits d WHERE l.id = d.lid_id AND d.created_at >= '" . $date[0] . "' AND d.created_at <= '" . $date[1] . "') depozit FROM lids l WHERE " . $where_user . " l.created_at >= '" . $date[0] . "' AND l.created_at <= '" . $date[1] . "'";

    return DB::select(DB::raw($sql));
  }

  public function getlidonid(Request $request, $id)
  {
    $req = $request->all();
    $f_key =   DB::table('apikeys')->where('api_key', $req['api_key'])->first();
    if (!$f_key) return response(['status' => 'Key incorect'], 403);

    return Lid::where('id', $id)->where('provider_id', $f_key->id)->get();
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

    if (!$data['ontime']) $data['ontime'] = null;
    $a_lid = [
      'ontime' => $data['ontime'],
      'updated_at' => Now()
    ];

    DB::table('lids')->where('id', $data['id'])->update($a_lid);

    return response('Lids add ontime' . $data['ontime'], 200);
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
    $n_lid->provider_id = $f_key->id;
    $n_lid->user_id = (int) $req['user_id'];
    $n_lid->office_id = User::where('id', (int) $req['user_id'])->value('office_id');

    $n_lid->created_at = Now();

    $f_lid =  Lid::where('tel', '=', $n_lid->tel)->get();

    if (!$f_lid->isEmpty() &&  $n_lid->provider_id != '76') {
       //$name = Provider::find($f_key->id)->value('name');

       $n_lid->afilyator = $f_key->name;
      $n_lid->provider_id = 75;
      $n_lid->user_id = 252;
      $n_lid->office_id = User::where('id', 252)->value('office_id');
      $n_lid->save();
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

  public function set_zaliv_post(Request $request)
  {
    $req = $request->all();

    $f_key =   DB::table('apikeys')->where('api_key', $req['api_key'])->first();

    if (!$f_key) return response(['status' => 'Key incorect'], 403);

    $n_lid = new Lid;

    $fio = $req['namedata'];
    $lastn = $req['lnamedata'];
    $email = $req['emaildata'];
    $phonestr = $req['phoneData'];
    $affiliate = $req['affiliatedata'];
    $fio = htmlspecialchars($fio);
    $lastn = htmlspecialchars($lastn);
    $email = htmlspecialchars($email);
    $affiliate = htmlspecialchars($affiliate);
    $fio = urldecode($fio);
    $lastn = urldecode($lastn);
    $email = urldecode($email);
    $affiliate = urldecode($affiliate);
    $phonestr = urldecode($phonestr);
    $fio = trim($fio);
    $lastn = trim($lastn);
    $email = trim($email);
    $phonestr = trim($phonestr);
    $affiliate = trim($affiliate);

    if ($fio) {
      $n_lid->name =  $fio;
    } else {
      $n_lid->name = time();
    }

    if ($phonestr) {
      $n_lid->tel =  $phonestr;
    } else {
      $n_lid->tel = time();
    }

    if ($email) {
      $n_lid->email = $email;
    } else {
      $n_lid->email = time() . '@none.com';
    }

    $n_lid->afilyator = $affiliate;
    $n_lid->provider_id = $f_key->id;
    $n_lid->user_id = (int) $req['user_id'];
    $n_lid->office_id = User::where('id', (int) $req['user_id'])->value('office_id');

    $n_lid->created_at = Now();

    $f_lid =  Lid::where('tel', '=', $n_lid->tel)->get();
    if (!$f_lid->isEmpty() &&  $n_lid->provider_id != '76') {
      //  $name = Provider::find($f_key->id)->value('name');
       $n_lid->afilyator = $f_key->name;
      $n_lid->provider_id = 75;
      $n_lid->user_id = 252;
      $n_lid->save();
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
    $n_lid->provider_id = $f_key->id;
    $n_lid->user_id = $req['user_id'];
    $n_lid->office_id = User::where('id', (int) $req['user_id'])->value('office_id');
    $n_lid->created_at = Now();

    $f_lid =  Lid::where('tel', '=', $n_lid->tel)->get();
    if (!$f_lid->isEmpty() &&  $n_lid->provider_id != '76') {
      //  $name = Provider::find($f_key->id)->value('name');
       $n_lid->afilyator = $f_key->name;
      $n_lid->provider_id = 75;
      $n_lid->user_id = 252;
      $n_lid->save();
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
    $n_lid->provider_id = $f_key->id;
    $n_lid->user_id = $req['user_id'];
    $n_lid->office_id = User::where('id', (int) $req['user_id'])->value('office_id');
    $n_lid->created_at = Now();

    $f_lid =  Lid::where('tel', '=', $n_lid->tel)->get();

    if (!$f_lid->isEmpty()) {
      $n_lid->status_id = 22;
    }
    if ($n_lid->provider_id == '76') {
      $n_lid->status_id = 8;
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

    $sql = "SELECT l.name,l.tel,l.afilyator,l.status_id,l.email,l.id,s.name statusName " . $date . " FROM `lids` l LEFT JOIN statuses s on (s.id = l.status_id ) where l.`provider_id` = '" . $f_key->id . "'";
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

  public function get_zaliv_p(Request $request)
  {
    /*
api_key=11e9c0056d4aa76c3c7b946737f089d4
starDate=24-12-2007
stopdate=12-12-2022
increment=1000
page=7
ftd=0  / ftd=1    (0 - всі ліди або 1 - то тільки депозити)
    */
    $req = $request->all();
    $f_key =   DB::table('apikeys')->where('api_key', $req['api_key'])->first();
    $limit = $onlydep = '';

    if (!$f_key) return response(['status' => 'Key incorect'], 403);
    $res['result'] = 'Error';
    if (!isset($req['increment'])) {
      $req['increment'] = 1000;
    }
     $req['page'] = (int) $req['page'] - 1;
    if (!isset($req['page']) || ((int) $req['page'] * (int) $req['increment']) == 0) {
      $limit = ' LIMIT ' .  (int) $req['increment'];
    } else {
      $limit = ' LIMIT ' . (int) $req['increment'] . ' OFFSET ' . (int) $req['page'] * (int) $req['increment'];
    }

    if (isset($req['ftd']) && $req['ftd'] == 1) {
      $onlydep = 'l.status_id = 10 AND ';
    }
    $sql = "SELECT l.name,l.tel,l.afilyator,l.status_id,l.email,l.id,s.name statusName, l.created_at , l.updated_at FROM `lids` l LEFT JOIN statuses s on (s.id = l.status_id ) where " . $onlydep . " DATE(l.`created_at`) >= '" . $req['startDate'] . "' AND DATE(l.`created_at`) <= '" . $req['stopDate'] . "' AND l.`provider_id` = '" . $f_key->id . "' " . $limit;
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
        if (isset($req['startDate'])) {
          $a1['datestart'] = $lid->created_at;
          $a1['dateupdate'] = $lid->updated_at;
        }
        $res['data'][] = $a1;
        $res['rows']++;
      }
    }else{
      $res['data'] = [];
      $res['result'] = "success";
      $res['rows'] = 0;
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
    $sql = "SELECT l.name,l.tel,l.afilyator,l.status_id,l.email,l.id,s.name statusName FROM `lids` l LEFT JOIN statuses s on (s.id = l.status_id ) WHERE DATE(l.`created_at`) >= " . $req['startDate'] . " AND DATE(l.`created_at`) <= " . $req['endDate'] . " AND l.`provider_id` = '" . $f_key->id . "'";

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
    $office_id = session()->get('office_id');
    $hm = Lid::where('user_id', $id)->when($office_id > 0, function ($query) use ($office_id) {
      return $query->where('office_id', $office_id);
    })->count();
    return response($hm);
  }
  public function changeDateBTC(Request $request)
  {
    $req = $request->all();
    $sql = "UPDATE `btc_list` SET `date_time` = NOW() WHERE `address` = '". $req['address']."'";
    DB::select(DB::raw($sql));
    return response('updated date BTC',200);
  }

  public function getBTC(Request $request)
  {
    $req = $request->all();

    $res = [];
    $res['message'] = 'no free used';
    $office_id = session()->get('office_id');
    if($office_id){
    $sql = "SELECT id, address FROM `btc_list` where `used` = false AND `office_id` = " . $office_id . " order by `id` ASC LIMIT 1";
    $btc = DB::select(DB::raw($sql));

    if ($btc) {
      $sql = "UPDATE `btc_list` SET `used` = true, `lid_id` = " . $req['id'] . ", `user_id` = " . $req['user_id'] . ", `date_time` = NOW() WHERE `id` = " . $btc[0]->id;
      DB::select(DB::raw($sql));
      $sql = "UPDATE `lids` SET `address` = '" . $btc[0]->address . "' WHERE `id` = " . $req['id'];
      DB::select(DB::raw($sql));

      $log = new Log;
      $log->tel = $req['tel'];
      $log->lid_id = $req['id'];
      $log->user_id = $req['user_id'];
      $log->text = $btc[0]->address;
      $log->save();

      $res['address'] = $btc[0]->address;
      $res['message'] = 'Used address ' . $btc[0]->address;
    }
  }
    return response($res);
  }

  public function qtytel(Request $request)
  {
    $req = $request->all();

    $sql = "INSERT INTO `qtytel` (`lid_id`,`user_id`,`date_time`) value ('" . $req['lid_id'] . "','" . $req['user_id'] . "',NOW())";
    DB::select(DB::raw($sql));
    Lid::where('id', $req['lid_id'])->increment('qtytel');
    $qtytel = Lid::where('id', $req['lid_id'])->value('qtytel');

    return response($qtytel);
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
