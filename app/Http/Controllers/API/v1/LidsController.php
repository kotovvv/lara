<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Lid;
use App\Models\Log;
use App\Models\Depozit;
use App\Models\User;
use App\Models\Provider;
use Illuminate\Support\Facades\DB;
use Debugbar;
use Carbon\Carbon;

class LidsController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index() {}

  public function provider_importlid(Request $request)
  {
    $req = $request->all();
    $data = [];
    $provider = Provider::select('providers.id', 'providers.name', 'providers.user_id')->where('providers.id', $req['provider_id'])->first();
    if (!$provider->user_id) return $request('no user', 400);
    $data['user_id'] = $provider['user_id'];
    $data['provider_id'] = $provider->id;
    foreach ($req['lids'] as $lid) {
      if (!isset($lid["tel"])) {
        continue;
      }
      $data['data'][] = [
        'name' => $lid['name'],
        'tel' => $lid["tel"],
        'email' => $lid['email'],
        'afilyator' => $lid['afilyator'],
      ];
    }
    $request = new Request();

    return $this->newlids($request->merge($data));
  }

  public function importlid(Request $request)
  {
    $insertItem = $request->all();

    $f_key =   DB::table('apikeys')->where('api_key', $insertItem['api_key'])->first();
    if (!$f_key) return response(['status' => 'Key incorect'], 403);

    $n_lid = new Lid;
    $n_lid->tel = $insertItem['umcfields']['phone'];
    $existingLid = Lid::where('tel', '' . $n_lid->tel)->whereIn('status_id', [9, 10, 11, 23, 27])->first();
    if ($existingLid) {
      $res['status'] = 'Дубль';
      $res['id'] = null;
      $res['insert'] = $n_lid->tel;
      return response($res, 400);
    }
    $n_lid->client_geo = $this->getGeo($n_lid->tel);
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
    $n_lid->rd = 'Registrations';
    $n_lid->save();

    return response('Lid inserted', 200);
  }


  public function searchlids(Request $request)
  {
    $data = $request->all();
    if ($data['search'] == '') {
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

  public function searchlids3(Request $request)
  {
    $data = $request->all();
    if ($data['search'] == '') {
      return response(['hm' => 0, 'lids' => []]);
    }
    if (isset($data['sortBy'])) {
      $sortBy = ["tel" => 'tel', "name" => 'name', "email" => 'email', "provider" => 'provider_id', "user" => 'user_id', "date_created" => 'created_at', "date_updated" => 'updated_at', 'afilyator' => 'afilyator', 'text' => 'text', 'qtytel' => 'qtytel', 'ontime' => 'ontime', 'rd' => 'rd', 'status' => 'status_id', 'depozit' => false][$data['sortBy']];
      $sortDesc = $data['sortDesc'] ? 'DESC' : 'ASC';
    } else {
      $sortBy = 'created_at';
      $sortDesc = 'DESC';
    }
    $limit = $page = 0;
    $response = [];
    if (isset($data['limit'])) {
      $limit = $data['limit'];
      $page = (int) $data['page'];
    }
    $office_id = session()->get('office_id');
    $search = $data['search'];
    if (isset($data['role_id']) && isset($data['group_id']) && $data['role_id'] == 2) {
      $a_user_ids = User::select('id')->where('group_id', $data['group_id']);
      $q_leads = Lid::select('*')
        ->whereIn('lids.user_id', $a_user_ids)
        ->when($office_id > 0, function ($query) use ($office_id) {
          return $query->where('lids.office_id', $office_id);
        })
        ->when(strpos($search, '@') != false, function ($query) use ($search) {
          return $query->where('email', $search);
        })
        ->when(strpos($search, '@') === false, function ($query) use ($search) {
          return $query->whereRaw('MATCH(lids.name,lids.tel,lids.email,lids.TEXT) AGAINST ("' . $search . '")');
        });
      $response['hm'] = $q_leads->count();

      $response['lids'] = $q_leads->orderBy('lids.created_at', 'desc')
        ->when($limit != 'all' && $page * $limit > $limit, function ($query) use ($limit, $page) {
          return $query->offset($limit * ($page - 1));
        })
        ->when($limit != 'all', function ($query) use ($limit) {
          return $query->limit($limit);
        })
        ->when($sortBy && !in_array($sortBy, ['provider_id', 'user_id']), function ($query) use ($sortBy, $sortDesc) {
          return $query->orderBy('lids.' . $sortBy, $sortDesc);
        })
        ->when($sortBy && $sortBy == 'provider_id', function ($query) use ($sortDesc) {
          return $query->leftJoin('providers', 'providers.id', '=', 'lids.provider_id')->orderBy('providers.name', $sortDesc);
        })
        ->when($sortBy && $sortBy == 'user_id', function ($query) use ($sortDesc) {
          return $query->leftJoin('users', 'users.id', '=', 'lids.user_id')->orderBy('users.name', $sortDesc);
        })
        ->get();

      return response($response);
    } else {
      // $office_ids = $data['office_ids'];
      $q_leads = Lid::select('*', 'lids.id as id', 'lids.tel as tel', 'lids.name as name', 'lids.email as email', 'lids.text as text', 'lids.office_id as office_id', 'lids.user_id as user_id', 'lids.provider_id as provider_id', 'lids.status_id as status_id', 'lids.created_at as created_at', 'lids.updated_at as updated_at')
        // ->when(in_array(0, $office_ids), function ($query) use ($office_ids) {
        //   return $query->whereIn('office_id', $office_ids);
        // })
        ->when($office_id > 0, function ($query) use ($office_id) {
          return $query->where('lids.office_id', $office_id);
        })
        ->when(strpos($search, '@') != false, function ($query) use ($search) {
          return $query->where('email', $search);
        })
        ->when(strpos($search, '@') === false, function ($query) use ($search) {
          return $query->whereRaw('MATCH(lids.name,lids.tel,lids.email,lids.text) AGAINST ("' . $search . '" IN NATURAL LANGUAGE MODE)');
        });
      $response['hm'] = $q_leads->count();

      $response['lids'] = $q_leads->orderBy('lids.created_at', 'desc')
        ->when($limit != 'all' && $page * $limit > $limit, function ($query) use ($limit, $page) {
          return $query->offset($limit * $page);
        })
        ->when($limit != 'all', function ($query) use ($limit) {
          return $query->limit($limit);
        })
        ->when($sortBy && !in_array($sortBy, ['provider_id', 'user_id']), function ($query) use ($sortBy, $sortDesc) {
          return $query->orderBy('lids.' . $sortBy, $sortDesc);
        })
        ->when($sortBy && $sortBy == 'provider_id', function ($query) use ($sortDesc) {
          return $query->leftJoin('providers', 'providers.id', '=', 'lids.provider_id')->orderBy('providers.name', $sortDesc);
        })
        ->when($sortBy && $sortBy == 'user_id', function ($query) use ($sortDesc) {
          return $query->leftJoin('users', 'users.id', '=', 'lids.user_id')->orderBy('users.name', $sortDesc);
        })
        ->get();
      return response($response);
    }

    return response(['hm' => 0, 'lids' => []]);
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
    $a_lid = [
      'user_id' => $data['user_id'],
      'office_id' => User::where('id', (int) $data['user_id'])->value('office_id'),
      'updated_at' => Now()
    ];
    if (isset($data['status_id'])) $a_lid['status_id'] = $data['status_id'];
    $res =  DB::table('lids')->whereIn('id', $data['data'])->update($a_lid);
    foreach ($data['date_provider'] as $date_provider) {
      //set update for imports
      DB::table('imports')->whereDate('start', $date_provider['created_at'])->where('provider_id', $date_provider['provider_id'])->update(['callc' => 1]);
      DB::table('imports_provider')->where('date', $date_provider['created_at'])->where('provider_id', $date_provider['provider_id'])->update(['callc' => 1]);
    }
    if ($res) {
      return response('Lids manager changed', 200);
    }
  }


  public function setTop(Request $request)
  {
    $data = $request->all();
    foreach ($data['data'] as $lid) {
      if ($lid['status_id'] == 8 || $lid['status_id'] == 33) {
        $a_lid = [
          'top' => $lid['top'],
          'updated_at' => Now()
        ];
        Lid::where('id', $lid['id'])->update($a_lid);
      }
    }
    return response('Lids top changed', 200);
  }


  public function updatelids(Request $request)
  {
    $data = $request->all();
    $res = [];
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
      //only for lids3
      if (isset($data['duplstat_id']) && in_array($lid['id'], $data['duplstat_id'])) {
        //count rows withs status id in not(22,8)
        $other_status = Log::select('status_id')->where('lid_id', $lid['id'])->whereNotIn('status_id', [22, 8])->count();

        if (!$other_status) {
          $a_lid['text'] = Log::where('lid_id', $lid['id'])->where('status_id', 22)->orderBy('created_at', 'desc')->value('text');
          //if not another status delete log for only duplicate
          Log::where('lid_id', $lid['id'])->delete();
        }
      }
      if (isset($lid['top']) && $lid['top'] == 1) {
        $a_lid['top'] = 0;
      }
      $res =  DB::table('lids')->where('id', $lid['id'])->update($a_lid);

      //set update for imports
      $l = Lid::select('created_at', 'provider_id')->where('id', $lid['id'])->first();
      if ($l) {
        $date_s = date('Y-m-d', strtotime($l->created_at));
        DB::table('imports')->whereDate('start', $date_s)->where('provider_id', $l->provider_id)->update(['callc' => 1]);
        DB::table('imports_provider')->where('date', $date_s)->where('provider_id', $l->provider_id)->update(['callc' => 1]);
      }

      $a_lid['lid_id'] = $lid['id'];
      if (isset($lid['tel'])) {
        $a_lid['tel'] = $lid['tel'];
      }

      $a_lid['text'] = isset($lid['text']) ? $lid['text'] : '';
      $a_lid['created_at'] = Now();
      unset($a_lid['office_id']);
      unset($a_lid['top']);

      DB::table('logs')->insert($a_lid);
    }
    if ($res) {
      return response('Lids updated', 200);
    }
  }

  public function changeStatusLids(Request $request)
  {
    $data = $request->all();
    $a_lids_id = $data['data'];
    $new_status_id = $data['status_id'];
    //$searchAll = isset($data['searchAll']) ? $data['searchAll'] : false;
    $res = [];
    $res = Lid::whereIn('id', $a_lids_id)->update(['status_id' => $new_status_id, 'updated_at' => Now()]);

    $a_l = Lid::selectRaw('DATE(created_at) as created_date, provider_id, COUNT(*) as count')
      ->whereIn('id', $a_lids_id)
      ->groupBy('created_date', 'provider_id')
      ->get();

    foreach ($a_l as $l) {
      $date_s = date('Y-m-d', strtotime($l->created_date));
      DB::table('imports')->whereDate('start', $date_s)->where('provider_id', $l->provider_id)->update(['callc' => 1]);
      DB::table('imports_provider')->where('date', $date_s)->where('provider_id', $l->provider_id)->update(['callc' => 1]);
    }

    if (!empty($a_lids_id)) {
      $logs = [];
      foreach ($a_lids_id as $lid_id) {
        $logs[] = [
          'lid_id' => $lid_id,
          'user_id' => session()->get('user_id'),
          'status_id' => $new_status_id,
          'created_at' => now(),
          'updated_at' => now(),
        ];
      }
      DB::table('logs')->insert($logs);
    }

    if ($res) {
      return response('Lids updated', 200);
    }
  }

  private function getGeo($tel)
  {
    $geo = '';
    $telcod = ["93" => "AF", "358" => "AX", "355" => "AL", "213" => "DZ", "1684" => "AS", "376" => "AD", "244" => "AO", "1264" => "AI", "672" => "AQ", "1268" => "AG", "54" => "AR", "374" => "AM", "297" => "AW", "61" => "AU", "43" => "AT", "994" => "AZ", "1242" => "BS", "973" => "BH", "880" => "BD", "1246" => "BB", "375" => "BY", "32" => "BE", "501" => "BZ", "229" => "BJ", "1441" => "BM", "975" => "BT", "591" => "BO", "387" => "BA", "267" => "BW", "55" => "BR", "246" => "IO", "673" => "BN", "359" => "BG", "226" => "BF", "257" => "BI", "855" => "KH", "237" => "CM", "1" => "CA", "238" => "CV", " 345" => "KY", "236" => "CF", "235" => "TD", "56" => "CL", "86" => "CN", "61" => "CX", "61" => "CC", "57" => "CO", "269" => "KM", "242" => "CG", "243" => "CD", "682" => "CK", "506" => "CR", "225" => "CI", "385" => "HR", "53" => "CU", "357" => "CY", "420" => "CZ", "45" => "DK", "253" => "DJ", "1767" => "DM", "1849" => "DO", "593" => "EC", "20" => "EG", "503" => "SV", "240" => "GQ", "291" => "ER", "372" => "EE", "251" => "ET", "500" => "FK", "298" => "FO", "679" => "FJ", "358" => "FI", "33" => "FR", "594" => "GF", "689" => "PF", "241" => "GA", "220" => "GM", "995" => "GE", "49" => "DE", "233" => "GH", "350" => "GI", "30" => "GR", "299" => "GL", "1473" => "GD", "590" => "GP", "1671" => "GU", "502" => "GT", "44" => "GG", "224" => "GN", "245" => "GW", "595" => "GY", "509" => "HT", "379" => "VA", "504" => "HN", "852" => "HK", "36" => "HU", "354" => "IS", "91" => "IN", "62" => "ID", "98" => "IR", "964" => "IQ", "353" => "IE", "44" => "IM", "972" => "IL", "39" => "IT", "1876" => "JM", "81" => "JP", "44" => "JE", "962" => "JO", "77" => "KZ", "254" => "KE", "686" => "KI", "850" => "KP", "82" => "KR", "965" => "KW", "996" => "KG", "856" => "LA", "371" => "LV", "961" => "LB", "266" => "LS", "231" => "LR", "218" => "LY", "423" => "LI", "370" => "LT", "352" => "LU", "853" => "MO", "389" => "MK", "261" => "MG", "265" => "MW", "60" => "MY", "960" => "MV", "223" => "ML", "356" => "MT", "692" => "MH", "596" => "MQ", "222" => "MR", "230" => "MU", "262" => "YT", "52" => "MX", "691" => "FM", "373" => "MD", "377" => "MC", "976" => "MN", "382" => "ME", "1664" => "MS", "212" => "MA", "258" => "MZ", "95" => "MM", "264" => "NA", "674" => "NR", "977" => "NP", "31" => "NL", "599" => "AN", "687" => "NC", "64" => "NZ", "505" => "NI", "227" => "NE", "234" => "NG", "683" => "NU", "672" => "NF", "1670" => "MP", "47" => "NO", "968" => "OM", "92" => "PK", "680" => "PW", "970" => "PS", "507" => "PA", "675" => "PG", "595" => "PY", "51" => "PE", "63" => "PH", "872" => "PN", "48" => "PL", "351" => "PT", "1939" => "PR", "974" => "QA", "40" => "RO", "7" => "RU", "250" => "RW", "262" => "RE", "590" => "BL", "290" => "SH", "1869" => "KN", "1758" => "LC", "590" => "MF", "508" => "PM", "1784" => "VC", "685" => "WS", "378" => "SM", "239" => "ST", "966" => "SA", "221" => "SN", "381" => "RS", "248" => "SC", "232" => "SL", "65" => "SG", "421" => "SK", "386" => "SI", "677" => "SB", "252" => "SO", "27" => "ZA", "211" => "SS", "500" => "GS", "34" => "ES", "94" => "LK", "249" => "SD", "597" => "SR", "47" => "SJ", "268" => "SZ", "46" => "SE", "41" => "CH", "963" => "SY", "886" => "TW", "992" => "TJ", "255" => "TZ", "66" => "TH", "670" => "TL", "228" => "TG", "690" => "TK", "676" => "TO", "1868" => "TT", "216" => "TN", "90" => "TR", "993" => "TM", "1649" => "TC", "688" => "TV", "256" => "UG", "380" => "UA", "971" => "AE", "44" => "GB", "1" => "US", "598" => "UY", "998" => "UZ", "678" => "VU", "58" => "VE", "84" => "VN", "1284" => "VG", "1340" => "VI", "681" => "WF", "967" => "YE", "260" => "ZM", "263" => "ZW"];
    for ($i = 2; $i < 5; $i++) {
      $first = substr($tel, 0, $i);
      if (array_key_exists($first, $telcod)) {
        $geo = $telcod[$first];
        break;
      }
    }
    return $geo;
  }

  public function newlids(Request $request)
  {
    $res = [];
    $res['date_start'] = date('Y-m-d H:i:s');
    $data = $request->all();

    $office_id = User::where('id', (int) $data['user_id'])->value('office_id');
    //Debugbar::info($data['data']);
    $load_mess = '';
    if (isset($data['message'])) {
      $load_mess = $data['message'];
    }
    $geo = '';

    foreach ($data['data'] as  $key => $lid) {
      $n_lid = new Lid;
      $n_lid->dep_reg = $data['dep_reg'];

      if (isset($lid['tel']) && preg_match('/^[0-9]+$/', $lid['tel'])) {
        $n_lid->tel =  preg_replace('/[^0-9]/', '', $lid['tel']);
        $n_lid->client_geo = $this->getGeo($n_lid->tel);
        if (strlen($n_lid->tel) < 6) {
          continue;
        }
        if ($geo == '') {
          $geo = $n_lid->client_geo;
        }
      } else {
        continue;
      }

      if (isset($lid['name'])) {
        $n_lid->name = mb_convert_encoding(substr(trim($lid['name']), 0, 50), 'UTF-8', 'UTF-8');
      }
      if (isset($lid['lastname'])) {
        $n_lid->name = $n_lid->name . ' ' . mb_convert_encoding(substr(trim($lid['lastname']), 0, 50), 'UTF-8', 'UTF-8');
        $n_lid->name = substr($n_lid->name, 0, 50);
      }



      if (isset($lid['email'])) {
        $n_lid->email = $lid['email'];
      } else {
        $n_lid->email = '';
      }

      if (isset($lid['deposit'])) {
        $n_lid->deposit = $lid['deposit'];
      } else {
        $n_lid->deposit = "";
      }

      if (isset($lid['text'])) {
        $n_lid->text = $lid['text'];
      } else {
        $n_lid->text = "";
      }

      $n_lid->load_mess = $load_mess;

      $n_lid->user_id = $data['user_id'];
      $n_lid->office_id = $office_id;
      $n_lid->created_at = $res['date_start'];

      if (isset($lid['afilyator'])) {
        $n_lid->afilyator = substr(trim($lid['afilyator']), 0, 50);
      } else {
        $n_lid->afilyator = '';
      }
      if (isset($data['provider_id'])) $n_lid->provider_id = $data['provider_id'];
      if (isset($data['status_id']))  $n_lid->status_id = $data['status_id'];

      $dup = Provider::where('id', $data['provider_id'])->value('dup');
      $weekdup = Provider::where('id', $data['provider_id'])->value('weekdup');
      if ($dup == 0) {
        $added_date =  Lid::where('tel', '=', '' . $n_lid->tel)->orderBy('created_at', 'desc')->value('created_at');
        if ($added_date != '') {
          $date = Carbon::now();
          $added_date = Carbon::parse($added_date);
          if ($date->diffInDays($added_date) < $weekdup * 7) {
            continue;
          }
        }
      } else {
        if (!(Lid::where('tel', '=', '' . $n_lid->tel)->get())->isEmpty()) {
          $n_lid->status_id = 22;
        }
      }
      $n_lid->rd = 'Depositors';
      if ($n_lid->provider_id == '76') {
        $n_lid->status_id = 8;
      }
      try {
        $n_lid->save();
        //save new lid to log
        $log = [
          'tel' => $n_lid->tel,
          'status_id' => $n_lid->status_id,
          'user_id' => $n_lid->user_id,
          'text' => $n_lid->text,
          'created_at' => $res['date_start'],
          'lid_id' => $n_lid->id
        ];
        Log::insert($log);
      } catch (\Throwable $th) {
        $res['error'] = $th;
      }
    }
    $res['date_end'] = date('Y-m-d H:i:s');
    $res['geo'] = $geo;
    return response($res, 200);
  }

  public function checkEmails(Request $request)
  {
    $data = $request->all();
    $data['emails'] = array_values(array_filter($data['emails']));
    // dd($data['emails']);

    $hmmonth = Carbon::now()->subMonths(6);
    if (isset($data['hmmonth'])) {

      $hmmonth = Carbon::now()->subMonths((int)$data['hmmonth']);
    }
    $results = $a_tel = [];
    $where_email_tel = $group_email_tel = '';
    if (isset($data['email_tel']) && $data['email_tel'] == 'tel') {
      foreach (array_filter($data['emails']) as $tel) {
        $a_tel[] = preg_replace('/[^0-9]/', '', $tel);
      }
      $where_email_tel = " WHERE l.created_at > '" . $hmmonth . "' AND l.`tel` IN (\"" . implode('","', array_filter($a_tel)) . "\")";
      $where_email_tel_next = " WHERE l.created_at < '" . $hmmonth . "' AND l.status_id IN (9,10,20,21) AND l.`tel` IN (\"" . implode('","', array_filter($a_tel)) . "\")";
      $where_email_tel_all = " WHERE l.`tel` IN (\"" . implode('","', array_filter($a_tel)) . "\")";
      $group_email_tel = " GROUP BY `tel`";
    } else {
      $where_email_tel = " WHERE  l.created_at > '" . $hmmonth . "' AND `email` IN (\"" . implode('","', $data['emails']) . "\")";

      $where_email_tel_next = " WHERE  l.created_at < '" . $hmmonth . "' AND l.status_id IN (9,10,20,21) AND `email` IN (\"" . implode('","', $data['emails']) . "\")";
      $where_email_tel_all = " WHERE  `email` IN (\"" . implode('","', $data['emails']) . "\")";
      $group_email_tel = " GROUP BY `email`";
    }
    if (isset($data['check'])) {
      $sql_all = "SELECT l.*, s.`name` status_name,  u.`name` user_name, p.`name` provider_name,  o.`name` office_name FROM `lids` l LEFT JOIN `providers` p ON (p.`id` = l.`provider_id`) LEFT JOIN `statuses` s ON (s.`id` = l.`status_id`) LEFT JOIN `users` u ON (u.`id` = l.`user_id`) LEFT JOIN `offices` o ON (o.`id` = l.`office_id`) " . $where_email_tel_all . " ORDER BY l.created_at ASC";
      $sql = "SELECT l.*, s.`name` status_name,  u.`name` user_name, p.`name` provider_name,  o.`name` office_name FROM `lids` l LEFT JOIN `providers` p ON (p.`id` = l.`provider_id`) LEFT JOIN `statuses` s ON (s.`id` = l.`status_id`) LEFT JOIN `users` u ON (u.`id` = l.`user_id`) LEFT JOIN `offices` o ON (o.`id` = l.`office_id`) " . $where_email_tel . " ORDER BY l.created_at ASC";
      // . " ORDER BY l.created_at ASC"
      $sql_next = "SELECT l.*, s.`name` status_name,  u.`name` user_name, p.`name` provider_name,  o.`name` office_name FROM `lids` l LEFT JOIN `providers` p ON (p.`id` = l.`provider_id`) LEFT JOIN `statuses` s ON (s.`id` = l.`status_id`) LEFT JOIN `users` u ON (u.`id` = l.`user_id`) LEFT JOIN `offices` o ON (o.`id` = l.`office_id`) " . $where_email_tel_next . " ORDER BY l.created_at ASC";

      $results['leads'] = DB::table(DB::raw("($sql) as first_query"))
        ->union(DB::table(DB::raw("($sql_next) as second_query")))
        ->get();

      $results['leads_all'] = DB::table(DB::raw("($sql_all) as first_query"))

        ->get();
    }

    DB::select(DB::raw("SET SQL_MODE = '';"));
    // $sql = "SELECT l.`tel`,l.`name`,8 AS status_id, l.`email`, 252 AS user_id, 75 AS provider_id, p.`name` AS afilyator,NOW() as created_at,3 AS office_id FROM `lids` l LEFT JOIN `providers` p ON (p.`id` = l.`provider_id`) " . $where_email_tel . $group_email_tel;
    $sql = "SELECT l.`tel`, LOWER(l.`email`) email FROM `lids` l " . $where_email_tel . $group_email_tel;
    $leads =  DB::select(DB::raw($sql));

    $leads = array_map(function ($item) {
      return (array) $item;
    }, $leads);
    $sql = "SELECT l.`tel`, LOWER(l.`email`) email FROM `lids` l " . $where_email_tel_all . $group_email_tel;
    $leads_all =  DB::select(DB::raw($sql));

    $leads_all = array_map(function ($item) {
      return (array) $item;
    }, $leads_all);
    // Lid::insert($leads);
    if (isset($data['email_tel']) && $data['email_tel'] == 'tel') {
      $results['emails'] = array_column($leads, 'tel');
      $results['emails_all'] = array_column($leads_all, 'tel');
    } else {
      $results['emails'] = array_column($leads, 'email');
      $results['emails_all'] = array_column($leads_all, 'email');
    }
    return response($results);
  }

  public function getLidsPost(Request $request)
  {
    $data = $request->all();
    $office_id = session()->get('office_id');
    $id = $data['id'];
    $status_id = $data['status_id'];
    $search = $data['search'];
    $tel = $data['tel'];
    $filterRD = $data['filterRD'];
    $limit = $data['limit'];
    $page = $data['page'];
    $providers = [];
    if (isset($data['provider_id']) && count($data['provider_id']) > 0) {
      $providers = $data['provider_id'];
    } else {
      $res = Provider::select('id')->where('office_id', 'REGEXP', '[^0-9]' . $office_id . '[^0-9]')->get()->toArray();
      foreach ($res as $item) {
        $providers[] = $item['id'];
      }
    }
    $response = [];
    $q_leads = Lid::select('lids.*', DB::Raw('(SELECT SUM(`depozit`) FROM `depozits` WHERE `lids`.`id` = `depozits`.`lid_id`) depozit'))
      ->where('lids.user_id', $id)
      ->when($office_id > 0, function ($query) use ($office_id) {
        return $query->where('office_id', $office_id);
      })
      ->when(count($providers) > 0, function ($query) use ($providers) {
        return $query->whereIn('provider_id', $providers);
      })
      ->when(count($status_id) > 0, function ($query) use ($status_id) {
        return $query->whereIn('status_id', $status_id);
      })
      ->when($filterRD, function ($query) use ($filterRD) {
        return $query->where('rd', $filterRD);
      })
      ->when($tel != '', function ($query) use ($tel) {
        return $query->where('tel', 'like', $tel . '%');
      })
      ->when($search != '', function ($query) use ($search) {
        return $query->where(function ($query) use ($search) {
          return $query->where('name', 'like', '%' . $search . '%')->orWhere('email', 'like', '%' . $search . '%');
        });
      });
    $response['hm'] = $q_leads->count();

    $response['lids'] = $q_leads->orderBy('lids.top', 'desc')->orderBy('lids.created_at', 'desc')
      ->when($limit != 'all' && $page * $limit > $limit, function ($query) use ($limit, $page) {
        return $query->offset($limit * ($page - 1));
      })
      ->when($limit != 'all', function ($query) use ($limit) {
        return $query->limit($limit);
      })
      ->get();

    return response($response);
  }


  public function getLids3(Request $request)
  {
    $data = $request->all();

    // Получаем office_ids только если нужно
    $office_ids = [session()->get('office_id')];
    if (in_array(0, $office_ids)) {
      $office_ids = $data['office_ids'];
    }

    $id = $data['id'];
    $status_id = $data['status_id'];
    $tel = $data['tel'];
    $limit = $data['limit'];
    $page = (int) $data['page'];
    $filterLang = $data['filterLang'];
    $filterGeo = $data['filterGeo'] ?? [];
    $filterRD = isset($data['filterRD']) && in_array($data['filterRD'], ['Depositors', 'Registrations']) ? $data['filterRD'] : null;

    $providers = [];
    $date = [];
    $users_ids = [];
    $where_date = '';
    $duplicate_tel = [];

    // Оптимизация сортировки
    $sortBy = false;
    $sortDesc = 'DESC';
    if (isset($data['sortBy'])) {
      $sortMap = [
        "tel" => 'tel',
        "name" => 'name',
        "email" => 'email',
        "provider" => 'provider_id',
        "provider_name" => 'provider_id',
        "office_name" => 'office_id',
        "user" => 'user_id',
        "date_created" => 'created_at',
        "date_updated" => 'updated_at',
        'afilyator' => 'afilyator',
        'dp' => 'dp',
        'text' => 'text',
        'qtytel' => 'qtytel',
        'ontime' => 'ontime',
        'status' => 'status_id',
        'depozit' => 'depozit',
        'client_geo' => 'client_geo'
      ];
      $sortBy = $sortMap[$data['sortBy']] ?? false;
      $sortDesc = $data['sortDesc'] ? 'DESC' : 'ASC';
    }

    // Дубликаты телефонов
    if (isset($data['duplicate_tel']) && $id) {
      $duplicate_tel = Lid::select('tel')->where('user_id', $id)->groupBy('tel')->havingRaw('count(tel) > 1');
    }

    // Группы пользователей
    if (!empty($data['group_ids']) && $data['group_ids'][0] != 0) {
      $users_ids = User::whereIn('group_id', $data['group_ids'])->pluck('id')->toArray();
    }

    // Дата
    if (isset($data['datefrom'])) {
      $date = [
        date('Y-m-d', strtotime($data['datefrom'])) . ' 00:00:00',
        date('Y-m-d', strtotime($data['dateto'])) . ' 23:59:59'
      ];
      $where_date = " AND created_at >= '" . $date[0] . "' AND created_at <= '" . $date[1] . "'";
    }

    // Провайдеры
    if (!empty($data['provider_id'])) {
      $providers = $data['provider_id'];
    } elseif (!in_array(0, $office_ids)) {
      $providers = Provider::whereIn('office_id', $office_ids)->pluck('id')->toArray();
    }

    // Основной запрос
    $q_leads = Lid::query()
      ->select('lids.*', DB::raw('(SELECT SUM(`depozit`) FROM `depozits` WHERE `lids`.`id` = `depozits`.`lid_id`' . $where_date . ') as depozit'))
      ->when(!in_array(0, $office_ids), fn($q) => $q->whereIn('lids.office_id', $office_ids))
      ->when(!is_array($id) && $id > 0 && empty($users_ids), fn($q) => $q->where('lids.user_id', $id))
      ->when(!empty($users_ids), fn($q) => $q->whereIn('lids.user_id', $users_ids))
      ->when(!empty($providers), fn($q) => $q->whereIn('lids.provider_id', $providers))
      ->when(!empty($status_id), fn($q) => $q->whereIn('lids.status_id', $status_id))
      ->when(!empty($tel), fn($q) => $q->where('lids.tel', 'like', $tel . '%'))
      ->when(isset($data['duplicate_tel']), fn($q) => $q->whereIn('lids.tel', $duplicate_tel))
      ->when(!empty($date), fn($q) => $q->whereBetween('lids.created_at', $date))
      ->when(!empty($filterLang) && $filterLang != null, fn($q) => $q->where('lids.client_lang', $filterLang))
      ->when(!empty($filterGeo), fn($q) => $q->whereIn('lids.client_geo', $filterGeo))
      ->when($filterRD !== null, fn($q) => $q->where('lids.rd', $filterRD))
      ->when(isset($data['callback']) && $data['callback'] == 1, function ($q) {
        return $q->whereRaw('(SELECT count(*) FROM `logs` WHERE `lids`.`id` = `logs`.`lid_id` AND `logs`.`status_id` = 9) > 0');
      });

    // Считаем количество без выборки всех строк
    $response['hm'] = (clone $q_leads)->count();

    // Получаем лиды с пагинацией и сортировкой
    $q_leads->when($limit !== 'all' && $page > 0, fn($q) => $q->offset($limit * ($page - 1)))
      ->when($limit !== 'all', fn($q) => $q->limit($limit));

    // Оптимизация сортировки
    if ($sortBy) {
      if ($sortBy === 'statuses') {
        $q_leads->leftJoin('statuses', 'statuses.id', '=', 'status_id')->orderBy('statuses.name', $sortDesc);
      } elseif ($sortBy === 'provider_id') {
        $q_leads->leftJoin('providers', 'providers.id', '=', 'lids.provider_id')->orderBy('providers.name', $sortDesc);
      } elseif ($sortBy === 'user_id') {
        $q_leads->leftJoin('users', 'users.id', '=', 'lids.user_id')->orderBy('users.name', $sortDesc);
      } elseif ($sortBy === 'depozit') {
        $q_leads->orderBy('depozit', $sortDesc);
      } else {
        $q_leads->orderBy('lids.' . $sortBy, $sortDesc);
      }
    }

    $response['lids'] = $q_leads->get();

    $subQuery = $q_leads
      ->select('status_id', DB::raw('COUNT(*) as hm'))
      ->groupBy('status_id');

    // Основной запрос с LEFT JOIN
    $response['statuses'] = DB::table('statuses as s')
      ->leftJoinSub($subQuery, 'cnt', 'cnt.status_id', '=', 's.id')
      ->select('s.id', 's.name', 's.color', DB::raw('IFNULL(cnt.hm, 0) as hm'))
      ->whereNotNull('cnt.hm')
      ->orderBy('s.order')
      ->get();

    // // Статусы (группировка по статусу)
    // $statusesQuery = Lid::query()
    //   ->select(DB::raw('count(lids.status_id) as hm'), 'statuses.id', 'statuses.name', 'statuses.color')
    //   ->join('statuses', 'statuses.id', '=', 'lids.status_id')
    //   ->when(!in_array(0, $office_ids), fn($q) => $q->whereIn('lids.office_id', $office_ids))
    //   ->when(!is_array($id) && $id > 0 && empty($users_ids), fn($q) => $q->where('lids.user_id', $id))
    //   ->when(!empty($users_ids), fn($q) => $q->whereIn('lids.user_id', $users_ids))
    //   ->when(!empty($providers), fn($q) => $q->whereIn('lids.provider_id', $providers))
    //   ->when(!empty($tel), fn($q) => $q->where('lids.tel', 'like', $tel . '%'))
    //   ->when(isset($data['duplicate_tel']), fn($q) => $q->whereIn('lids.tel', $duplicate_tel))
    //   ->when(!empty($date), fn($q) => $q->whereBetween('lids.created_at', $date))
    //   ->when($filterLang !== '' && $filterLang != null, fn($q) => $q->where('lids.client_lang', $filterLang))
    //   ->when(!empty($filterGeo), fn($q) => $q->whereIn('lids.client_geo', $filterGeo))
    //   ->when($filterRD !== null, fn($q) => $q->where('lids.rd', $filterRD))
    //   ->when(isset($data['callback']) && $data['callback'] == 1, function ($q) {
    //     return $q->whereRaw('(SELECT count(*) FROM `logs` WHERE `lids`.`id` = `logs`.`lid_id` AND `logs`.`status_id` = 9) > 0');
    //   })
    //   ->groupBy('statuses.id')
    //   ->orderBy('statuses.order', 'ASC');

    // $response['statuses'] = $statusesQuery->get();

    // Языки
    $langQuery = Lid::query()
      ->select('client_lang as name')
      ->whereNotNull('client_lang')
      ->when(!empty($date), fn($q) => $q->whereBetween('lids.created_at', $date))
      ->when(!in_array(0, $office_ids), fn($q) => $q->whereIn('lids.office_id', $office_ids))
      ->when(!is_array($id) && $id > 0 && empty($users_ids), fn($q) => $q->where('lids.user_id', $id))
      ->when(!empty($users_ids), fn($q) => $q->whereIn('lids.user_id', $users_ids))
      ->groupBy('client_lang')
      ->orderBy('client_lang', 'ASC');

    $response['languges'] = $langQuery->get();

    return response($response);
  }

  public function todaylids($id)
  {
    $date = date('Y-m-d');
    return Lid::where('user_id', (int) $id)
      ->whereNotNull('ontime')
      ->whereDate('ontime', '=', $date)
      ->orderBy('ontime', 'desc')
      ->get();
  }


  public function userLids($id)
  {
    $adnwhere = '';
    $a_providers = [];
    $office_id = session()->get('office_id');
    $providers = Provider::select('id')->where('office_id', 'REGEXP', '[^0-9]' . $office_id . '[^0-9]')->get()->toArray();
    foreach ($providers as $provider) {
      $a_providers[] = $provider['id'];
    }

    if ($office_id > 0) {
      $adnwhere = " AND office_id = $office_id AND provider_id in (" . implode(',', $a_providers) . ") ";
    }
    $sql = "SELECT DISTINCT `lids`.*, (SELECT SUM(`depozit`) FROM `depozits` WHERE `lids`.`id` = `depozits`.`lid_id`) depozit FROM `lids`  WHERE `lids`.`user_id` = " . (int) $id . $adnwhere . " ORDER BY `lids`.`updated_at` DESC";
    return DB::select(DB::raw($sql));
  }

  public function statusLids(Request $request)
  {
    $data = $request->all();
    if (isset($data['role_id']) && isset($data['group_id']) && $data['role_id'] == 2) {
      $a_user_ids = User::select('id')->where('group_id', $data['group_id']);
      return Lid::select('lids.*',  DB::Raw('(SELECT SUM(`depozit`) FROM `depozits` WHERE `lids`.`id` = `depozits`.`lid_id`) depozit'))->whereIn('lids.user_id', $a_user_ids)->where('lids.status_id', $data['id'])->orderBy('lids.created_at', 'desc')->get();
    } else {
      $office_id = session()->get('office_id');
      return Lid::select('lids.*',  DB::Raw('(SELECT SUM(`depozit`) FROM `depozits` WHERE `lids`.`id` = `depozits`.`lid_id`) depozit'))->where('lids.status_id', $data['id'])->when($office_id > 0, function ($query) use ($office_id) {
        return $query->where('office_id', $office_id);
      })->orderBy('lids.created_at', 'desc')->get();
    }
  }

  public function InfoDeposit(Request $request)
  {
    $getparams = $request->all();
    $lead_id = (int) $getparams['id'];
    $f_key =   DB::table('apikeys')->where('api_key', $getparams['api_key'])->first();
    if (!$f_key) return response(['status' => 'Key incorect'], 403);
    $leads = Depozit::select(DB::raw('"Success" as status, 1 as status_code, `lid_id` as  order_lead_id, `created_at` as ftd_date'))->where('lid_id', $lead_id)->first();
    $leads['dateAdd'] = date('Y-m-d H:i:s', strtotime(Lid::where('id', $lead_id)->value('created_at')));
    $leads['FTD'] = Lid::where('id', $lead_id)->value('status_id') == 10 ? 1 : 0;
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
    $f_key =  DB::table('apikeys')->where('api_key', $getparams['api_key'])->first();

    if (!$f_key) return response(['status' => 'Key incorect'], 403);
    $datefrom = date('Y-m-d', strtotime($getparams['from_date']));
    $dateto = date('Y-m-d', strtotime($getparams['to_date']));
    $sql = "SELECT
    d.`lid_id` AS `order_lead_id`
    , d.`created_at` AS `ftd_date`
    , l.`created_at` AS 'dateAdd'
    , IF(l.status_id = '10','1','0') AS FTD
FROM
    `depozits` d
    INNER JOIN `lids` l
        ON (d.`lid_id` = l.`id`)
WHERE l.`provider_id` = '" . $f_key->id . "' AND DATE(d.`created_at`) BETWEEN '" . $datefrom .  "' AND '" . $dateto . "' GROUP BY l.`id` ORDER BY l.`created_at` ASC";

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
      'provider_id' => $f_key->id,
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

  public function getlidsImportedProvider(Request $request)
  {
    $req = $request->all();
    $users_ids = [];
    $where_ids_off = '';
    $office_id = 0;
    $lids_prov = [];
    $geo = null;
    if (isset($req['geo'])) {
      $geo = $req['geo'];
      if (!is_array($geo)) {
        $geo = $geo ? [$geo] : [];
      }
    }

    if (session()->has('user_id')) {
      $user = User::where('id', (int) session()->get('user_id'))->first();

      if ($user['office_id'] > 0) {
        $office_id = $user['office_id'];
        $where_ids_off .= 'l.office_id = ' . $user['office_id'] . ' AND ';
      }
    }

    if (isset($req['provider'])) {
      $lids_prov = DB::table('imported_leads')
        ->select('lead_id')
        ->where('api_key_id', $req['provider_id'])
        ->whereBetween(DB::raw('DATE(upload_time)'), [$req['fromto'][0], $req['fromto'][1]])
        ->when(isset($req['geo']), function ($query) use ($geo) {
          return $query->whereIn('geo', $geo);
        })
        ->pluck('lead_id')
        ->toArray();
    }
    if (isset($req['date'])) {
      $lids_prov = DB::table('imported_leads')
        ->select('lead_id')
        ->where('api_key_id', $req['provider_id'])
        ->where(DB::raw('DATE(upload_time)'), $req['date'])
        ->whereIn('geo', $geo)
        ->pluck('lead_id')
        ->toArray();
    }
    if (isset($req['dates'])) {
      $lids_prov = DB::table('imported_leads')
        ->select('lead_id')
        ->where('api_key_id', $req['provider_id'])
        ->whereIn(DB::raw('DATE(upload_time)'), $req['dates'])
        ->where('geo', $geo)
        ->pluck('lead_id')
        ->toArray();
    }
    if (isset($req['dates']) || isset($req['date']) || isset($req['provider'])) {
      return Lid::select('lids.*', 'lids.client_geo as geo', 'users.fio AS  user', 'offices.name AS office', 'users.group_id')
        ->whereIn('lids.id', $lids_prov)
        ->leftJoin('users', 'users.id', '=', 'user_id')
        ->leftJoin('offices', 'offices.id', '=', 'lids.office_id')
        ->when($office_id > 0, function ($query) use ($office_id) {
          return $query->where('lids.office_id', $office_id);
        })
        ->when(isset($req['geo']), function ($query) use ($geo) {
          return $query->whereIn('client_geo', $geo);
        })
        ->get();
    }

    if (isset($req['end']) && isset($req['message'])) {
      $date_end = substr($req['end'], 0, 10);

      $lids =   Lid::select('lids.*', 'users.fio AS  user', 'users.group_id', 'offices.name AS office')
        ->where('load_mess', $req['message'])
        ->whereDate('lids.created_at', $date_end)
        ->leftJoin('users', 'users.id', '=', 'user_id')
        ->leftJoin('offices', 'offices.id', '=', 'lids.office_id')
        ->when($office_id > 0, function ($query) use ($office_id) {
          return $query->where('lids.office_id', $office_id);
        })
        ->when(count($users_ids) > 0, function ($query) use ($users_ids) {
          return $query->whereIn('user_id', $users_ids);
        })
        ->get();

      return $lids;
    } elseif (isset($req['provider_id']) && isset($req['start']) && isset($req['end'])) {
      $lids = Lid::select('lids.*', 'users.fio AS  user', 'users.group_id', 'offices.name AS office')
        ->leftJoin('users', 'users.id', '=', 'user_id')
        ->leftJoin('offices', 'offices.id', '=', 'lids.office_id')
        ->where('provider_id', (int) $req['provider_id'])
        ->whereBetween('lids.created_at', [$req['start'], $req['end']])
        ->when($office_id > 0, function ($query) use ($office_id) {
          return $query->where('office_id', $office_id);
        })
        ->when(count($users_ids) > 0, function ($query) use ($users_ids) {
          return $query->whereIn('user_id', $users_ids);
        })
        ->get();
      if ($lids->count()) {
        return $lids;
      } else {
        $message = DB::table('imports')->where('start', $req['start'])->where('end', $req['end'])->where('provider_id', $req['provider_id'])->first()->message;

        return  Lid::select('lids.*', 'users.fio AS  user', 'users.group_id', 'offices.name AS office')
          ->where('load_mess', $message)
          ->leftJoin('users', 'users.id', '=', 'user_id')
          ->leftJoin('offices', 'offices.id', '=', 'lids.office_id')
          ->when($office_id > 0, function ($query) use ($office_id) {
            return $query->where('office_id', $office_id);
          })
          ->when(count($users_ids) > 0, function ($query) use ($users_ids) {
            return $query->whereIn('user_id', $users_ids);
          })
          ->get();
      }
    } elseif (isset($req['start'])) {
      $sql = "SELECT l.*,l.client_geo as geo,users.fio as user, users.group_id, offices.name as office FROM `lids` l left join users on (users.id = l.user_id) left join offices on (offices.id = l.office_id) WHERE  " . $where_ids_off . " l.`id` IN (SELECT `lead_id` FROM `imported_leads` WHERE `api_key_id` = " . $req['provider_id'] . " AND DATE(`upload_time`) = '" . $req['start'] . "' AND geo = '" . $geo . "') ";
      $lids = DB::select(DB::raw($sql));
      $office_ids = [];
      foreach ($lids as $key => $lid) {
        $office_ids[] = $lid->office_id;
      }
      $office_ids = array_values(array_unique($office_ids));

      if (count($office_ids)) {
        DB::table('imports_provider')->where('id', $req['id'])->update(['office_ids' => json_encode($office_ids)]);
      }

      return $lids;
    }
    return response('Some not good(', 404);
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

    if (!isset($data['ontime'])) $data['ontime'] = null;
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
  public function updateLiads(Request $request)
  {
    $data = $request->all();
    try {
      Lid::whereIn('id', $data['lids'])->update($data['param']);
      return response('Ok', 200);
    } catch (\Throwable $th) {
      return response($th, 403);
    }
  }

  public function deletelids(Request $request)
  {
    $lids = $request->all();
    // Debugbar::info($data);

    //Lid::whereIn('id', $lids)->delete();
    //Log::whereIn('lid_id', $lids)->delete();
  }

  public function deleteImportedLids(Request $request)
  {
    $imported = $request->all();
    $request = new Request();
    if ($imported['message'] == '') {
      return response('Empty load_mess', 404);
    } else {
      Lid::where('load_mess', $imported['message'])->delete();
      $lids = Lid::select('id')->where('provider_id', $imported['provider_id'])->whereBetween('created_at', [$imported['start'], $imported['end']])->get()->toArray();
      if (count($lids) > 0) {
        $this->deletelids($request->merge($lids));
      }
    }

    DB::table('imports')->where('id', $imported['id'])->delete();
    DB::table('historyimport')->where('imports_id', $imported['id'])->delete();

    return response('Delete lids ' . count($lids), 200);
  }

  public function clearLiads(Request $request)
  {
    $lids = $request->all();
    $user_id = session()->get('user_id');
    // Получаем данные для вставки в logs
    $logs = Lid::whereIn('lids.id', $lids)
      ->join('users', 'lids.user_id', '=', 'users.id')
      ->select(
        DB::raw("'$user_id' as user_id"),
        'lids.id as lid_id',
        DB::raw("'' as tel"),
        'lids.status_id',
        DB::raw("CONCAT(users.name, ' : ', lids.text) as text"),
        DB::raw('1 as last_log'),
        DB::raw('NOW() as created_at')
      )
      ->get()
      ->toArray();

    // Вставляем в таблицу logs
    if (!empty($logs)) {
      DB::table('logs')->insert($logs);
    }

    $isAdmin = $this->isAdmin(session()->get('user_id'));
    Lid::whereIn('id', $lids)
      ->when(!$isAdmin, function ($query) {
        return $query->where('status_id', '!=', 32);
      })
      ->update(['status_id' => 8, 'text' => '', 'qtytel' => 0]);
    $del = Lid::select('id')->whereIn('id', $lids)->when(!$isAdmin, function ($query) {
      return $query->where('status_id', '!=', 32);
    })->get();
    Log::whereIn('lid_id', $del)->where('last_log', 0)->delete();
  }

  public function isAdmin($user_id)
  {
    $user = User::where('id', $user_id)->first();
    return ($user && $user->role_id == 1) ? true : false; //&& $user->office_id == 0
  }
  // /api/set_zaliv?api_key=rdfgsdfgsdfgsghethsdghdsf&user_id=383&umcfields[name]=name&umcfields[phone]=phone&umcfields[email]=email&text=text&umcfields[affiliate_user]=affiliate_user&client_lang=client_lang&client_geo=client_geo&client_funnel=client_funnel&client_answers=client_answers&company_name=company_name
  public function set_zaliv(Request $request)
  {
    $req = $request->all();

    $f_key = DB::table('apikeys')->where('api_key', $req['api_key'])->first();

    if (!$f_key) return response(['status' => 'Key incorect'], 403);
    $n_lid = new Lid;
    $n_lid->office_id = User::where('id', (int) $req['user_id'])->value('office_id');

    if (isset($req['umcfields']['name']) && strlen($req['umcfields']['name']) > 1) {
      $n_lid->name =  $req['umcfields']['name'];
    } else {
      $n_lid->name = time();
    }
    $geo = '';
    if (isset($req['umcfields']['phone']) && strlen($req['umcfields']['phone']) > 1) {
      $n_lid->tel =  preg_replace('/[^0-9]/', '', $req['umcfields']['phone']);
      $existingLid = Lid::where('tel', '' . $n_lid->tel)->whereIn('status_id', [9, 10, 11, 23, 27])->first();
      if ($existingLid) {
        $res['status'] = 'Дубль';
        $res['id'] = null;
        $res['insert'] = $n_lid->tel;
        return response($res, 400);
      }

      $n_lid->client_geo = $this->getGeo($n_lid->tel);
      $geo = $n_lid->client_geo;
      $dup = Provider::where('id', $f_key->id)->value('dup');
      $weekdup = Provider::where('id', $f_key->id)->value('weekdup');
      if ($dup == 0) {
        $added_date =  Lid::where('tel', '=', '' . $n_lid->tel)->orderBy('created_at', 'desc')->value('created_at');
        if ($added_date != '') {
          $date = Carbon::now();
          $added_date = Carbon::parse($added_date);
          if ($date->diffInDays($added_date) < $weekdup * 7) {
            $res['status'] = 'error';
            $res['message'] = 'Duplicate ';

            return response($res);
          }
        }
      } else {
        if (!(Lid::where('tel', '=', '' . $n_lid->tel)->get())->isEmpty()) {
          $res['status'] = 'error';
          $res['message'] = 'Duplicate ';

          return response($res);
        }
      }
    } else {
      return response('No tel');
    }


    if (isset($req['umcfields']['email']) && strlen($req['umcfields']['email']) > 1) {
      $n_lid->email = $req['umcfields']['email'];
    }
    if (isset($req['text']) && strlen($req['text']) > 1) {
      $n_lid->text = $req['text'];
    } else {
      $n_lid->text = '';
    }
    if (isset($req['client_lang'])) {
      $n_lid->client_lang = $req['client_lang'];
    } else {
      $n_lid->client_lang = '';
    }

    if (isset($req['client_funnel'])) {
      $n_lid->client_funnel = $req['client_funnel'];
    } else {
      $n_lid->client_funnel = '';
    }
    if (isset($req['client_answers'])) {
      $n_lid->client_answers = $req['client_answers'];
    } else {
      $n_lid->client_answers = '';
    }
    if (isset($req['company_name'])) {
      $n_lid->company_name = $req['company_name'];
    } else {
      $n_lid->company_name = '';
    }

    if (isset($req['umcfields']['affiliate_user'])) {
      $n_lid->afilyator = $req['umcfields']['affiliate_user'];
    } else {
      $n_lid->afilyator = '';
    }

    $n_lid->provider_id = $f_key->id;
    $n_lid->user_id = (int) $req['user_id'];
    $n_lid->rd = 'Registrations';

    $n_lid->created_at = Now();

    $n_lid->save();
    $id = $n_lid->id;

    $insert = DB::table('imported_leads')->insert(['lead_id' => $id, 'api_key_id' => $f_key->id, 'upload_time' => $n_lid->created_at, 'geo' => $geo]);
    $lids = DB::table('imported_leads')->select('lead_id')->where('api_key_id', $f_key->id)->where('upload_time', $n_lid->created_at)->where('geo', $geo);
    $a_office_ids = json_encode(Lid::whereIn('id', $lids)->groupBy('office_id')->orderBy('id', 'ASC')->pluck('office_id')->toArray());
    DB::table('imports_provider')->updateOrInsert(['date' => date('Y-m-d'), 'provider_id' => $f_key->id, 'geo' => $geo, 'office_ids' => $a_office_ids], ['date' => date('Y-m-d'), 'office_ids' => $a_office_ids, 'callc' => 1]);

    if ($n_lid->text != '') {
      $log = [
        'tel' => $n_lid->tel,
        // 'status_id' => $n_lid->status_id,
        'user_id' => $n_lid->user_id,
        'text' => $n_lid->text,
        'created_at' => $n_lid->created_at,
        'lid_id' => $n_lid->id
      ];
      Log::insert($log);
    }

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
    }
    $geo = '';
    if ($phonestr) {
      $n_lid->tel = preg_replace('/[^0-9]/', '', $phonestr);
      $existingLid = Lid::where('tel', '' . $n_lid->tel)->whereIn('status_id', [9, 10, 11, 23, 27])->first();
      if ($existingLid) {
        $res['status'] = 'Дубль';
        $res['id'] = null;
        $res['insert'] = $n_lid->tel;
        return response($res, 400);
      }
      $n_lid->client_geo = $this->getGeo($n_lid->tel);
      $geo = $n_lid->client_geo;
      $dup = Provider::where('id', $f_key->id)->value('dup');
      $weekdup = Provider::where('id', $f_key->id)->value('weekdup');
      if ($dup == 0) {
        $added_date =  Lid::where('tel', '=', '' . $n_lid->tel)->orderBy('created_at', 'desc')->value('created_at');
        if ($added_date != '') {
          $date = Carbon::now();
          $added_date = Carbon::parse($added_date);
          if ($date->diffInDays($added_date) < $weekdup * 7) {
            $res['status'] = 'error';
            $res['message'] = 'Duplicate ';

            return response($res);
          }
        }
      } else {
        if (!(Lid::where('tel', '=', '' . $n_lid->tel)->get())->isEmpty()) {
          $res['status'] = 'error';
          $res['message'] = 'Duplicate ';

          return response($res);
        }
      }
    } else {
      return response('No tel');
    }

    if ($email) {
      $n_lid->email = $email;
    } else {
      $n_lid->email = time() . '@none.com';
    }
    if (isset($req['client_lang'])) {
      $n_lid->client_lang = $req['client_lang'];
    } else {
      $n_lid->client_lang = '';
    }

    if (isset($req['client_funnel'])) {
      $n_lid->client_funnel = $req['client_funnel'];
    } else {
      $n_lid->client_funnel = '';
    }
    if (isset($req['client_answers'])) {
      $n_lid->client_answers = $req['client_answers'];
    } else {
      $n_lid->client_answers = '';
    }
    if (isset($req['company_name'])) {
      $n_lid->company_name = $req['company_name'];
    } else {
      $n_lid->company_name = '';
    }
    $n_lid->afilyator = $affiliate;
    $n_lid->provider_id = $f_key->id;
    $n_lid->user_id = (int) $req['user_id'];
    $n_lid->office_id = User::where('id', (int) $req['user_id'])->value('office_id');
    $n_lid->rd = 'Registrations';
    $n_lid->created_at = Now();

    /*     $f_lid =  Lid::where('tel', '=', $n_lid->tel)->get();
    if (!$f_lid->isEmpty() &&  $n_lid->provider_id != '76') {
      //  $name = Provider::find($f_key->id)->value('name');
      $n_lid->afilyator = $f_key->name;
      $n_lid->provider_id = 75;
      $n_lid->user_id = 252;
      $n_lid->office_id = User::where('id', 252)->value('office_id');
      $n_lid->save();
      return response('duplicate');
    } */
    $n_lid->save();
    $id = $n_lid->id;
    $insert = DB::table('imported_leads')->insert(['lead_id' => $id, 'api_key_id' => $f_key->id, 'upload_time' => Now(), 'geo' => $geo]);
    DB::table('imports_provider')->updateOrInsert(['date' => date('Y-m-d'), 'provider_id' => $f_key->id, 'geo' => $geo], ['date' => date('Y-m-d'), 'callc' => 1]);

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

    if (isset($req['umcfields[name]']) && strlen($req['umcfields[name]']) > 1) {
      $n_lid->name =  $req['umcfields[name]'];
    } else {
      $n_lid->name = time();
    }
    $geo = "";
    if (isset($req['umcfields[phone]']) && strlen($req['umcfields[phone]']) > 1) {
      $n_lid->tel =  preg_replace('/[^0-9]/', '', $req['umcfields[phone]']);
      $existingLid = Lid::where('tel', '' . $n_lid->tel)->whereIn('status_id', [9, 10, 11, 23, 27])->first();
      if ($existingLid) {
        $res['status'] = 'Дубль';
        $res['id'] = null;
        $res['insert'] = $n_lid->tel;
        return response($res, 400);
      }
      $n_lid->client_geo = $this->getGeo($n_lid->tel);
      $geo = $n_lid->client_geo;
      $dup = Provider::where('id', $f_key->id)->value('dup');
      $weekdup = Provider::where('id', $f_key->id)->value('weekdup');
      if ($dup == 0) {
        $added_date =  Lid::where('tel', '=', '' . $n_lid->tel)->orderBy('created_at', 'desc')->value('created_at');
        if ($added_date != '') {
          $date = Carbon::now();
          $added_date = Carbon::parse($added_date);
          if ($date->diffInDays($added_date) < $weekdup * 7) {
            $res['status'] = 'error';
            $res['message'] = 'Duplicate ';

            return response($res);
          }
        }
      } else {
        if (!(Lid::where('tel', '=', '' . $n_lid->tel)->get())->isEmpty()) {
          $res['status'] = 'error';
          $res['message'] = 'Duplicate ';

          return response($res);
        }
      }
    } else {
      return response('No tel');
    }

    if (isset($req['umcfields[email]']) && strlen($req['umcfields[email]']) > 1) {
      $n_lid->email = $req['umcfields[email]'];
    } else {
      $n_lid->email = time() . '@none.com';
    }
    if (isset($req['client_lang'])) {
      $n_lid->client_lang = $req['client_lang'];
    } else {
      $n_lid->client_lang = '';
    }

    if (isset($req['client_funnel'])) {
      $n_lid->client_funnel = $req['client_funnel'];
    } else {
      $n_lid->client_funnel = '';
    }
    if (isset($req['client_answers'])) {
      $n_lid->client_answers = $req['client_answers'];
    } else {
      $n_lid->client_answers = '';
    }
    if (isset($req['company_name'])) {
      $n_lid->company_name = $req['company_name'];
    } else {
      $n_lid->company_name = '';
    }
    $n_lid->afilyator = $req['umcfields[affiliate_user]'];
    $n_lid->provider_id = $f_key->id;
    $n_lid->user_id = $req['user_id'];
    $n_lid->office_id = User::where('id', (int) $req['user_id'])->value('office_id');
    $n_lid->rd = 'Registrations';
    $n_lid->created_at = Now();

    $n_lid->save();
    $id = $n_lid->id;
    $insert = DB::table('imported_leads')->insert(['lead_id' => $id, 'api_key_id' => $f_key->id, 'upload_time' => Now(), 'geo' => $geo]);
    DB::table('imports_provider')->updateOrInsert(['date' => date('Y-m-d'), 'provider_id' => $f_key->id, 'geo' => $geo], ['date' => date('Y-m-d'), 'callc' => 1]);

    $res['status'] = 'error';
    $res['id'] = $id;
    $res['insert'] = $insert;

    return response($res);
  }


  public function  set_zalivDub(Request $request)
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
    $geo = '';
    if (isset($req['umcfields']['phone']) && strlen($req['umcfields']['phone']) > 1) {
      $n_lid->tel =  $req['umcfields']['phone'];
      $existingLid = Lid::where('tel', '' . $n_lid->tel)->whereIn('status_id', [9, 10, 11, 23, 27])->first();
      if ($existingLid) {
        $res['status'] = 'Дубль';
        $res['id'] = null;
        $res['insert'] = $n_lid->tel;
        return response($res, 400);
      }
      $n_lid->client_geo = $this->getGeo($n_lid->tel);
      $geo = $n_lid->client_geo;
      $dup = Provider::where('id', $f_key->id)->value('dup');
      $weekdup = Provider::where('id', $f_key->id)->value('weekdup');
      if ($dup == 0) {
        $added_date =  Lid::where('tel', '=', '' . $n_lid->tel)->orderBy('created_at', 'desc')->value('created_at');
        if ($added_date != '') {
          $date = Carbon::now();
          $added_date = Carbon::parse($added_date);
          if ($date->diffInDays($added_date) < $weekdup * 7) {
            $res['status'] = 'error';
            $res['message'] = 'Duplicate ';

            return response($res);
          }
        }
      } else {
        if (!(Lid::where('tel', '=', '' . $n_lid->tel)->get())->isEmpty()) {
          $res['status'] = 'error';
          $res['message'] = 'Duplicate ';

          return response($res);
        }
      }
    } else {
      return response('No tel');
    }

    $n_lid->email = $req['umcfields']['email'];
    $n_lid->afilyator = $req['umcfields']['affiliate_user'];
    $n_lid->provider_id = $f_key->id;
    $n_lid->user_id = $req['user_id'];
    $n_lid->office_id = User::where('id', (int) $req['user_id'])->value('office_id');
    if (isset($req['client_lang'])) {
      $n_lid->client_lang = $req['client_lang'];
    } else {
      $n_lid->client_lang = '';
    }

    if (isset($req['client_funnel'])) {
      $n_lid->client_funnel = $req['client_funnel'];
    } else {
      $n_lid->client_funnel = '';
    }
    if (isset($req['client_answers'])) {
      $n_lid->client_answers = $req['client_answers'];
    } else {
      $n_lid->client_answers = '';
    }
    if (isset($req['company_name'])) {
      $n_lid->company_name = $req['company_name'];
    } else {
      $n_lid->company_name = '';
    }
    $n_lid->created_at = Now();

    $f_lid =  Lid::where('tel', '=', $n_lid->tel)->get();

    if (!$f_lid->isEmpty()) {
      $n_lid->status_id = 22;
    }
    if ($n_lid->provider_id == '76') {
      $n_lid->status_id = 8;
    }
    $n_lid->rd = 'Registrations';
    $n_lid->save();
    $id = $n_lid->id;
    $insert = DB::table('imported_leads')->insert(['lead_id' => $id, 'api_key_id' => $f_key->id, 'upload_time' => Now(), 'geo' => $geo]);
    DB::table('imports_provider')->updateOrInsert(['date' => date('Y-m-d'), 'provider_id' => $f_key->id, 'geo' => $geo], ['date' => date('Y-m-d'), 'callc' => 1]);

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
        $a_date_ftd = [];
        if ($ftd) {
          $date_ftd = DB::select(DB::raw("SELECT created_at as date FROM depozits WHERE `lid_id` = " . $lid->id . " ORDER by created_at ASC LIMIT 1"));
          if ($date_ftd) {
            $a_date_ftd = ['ftd_date' => $date_ftd[0]->date];
          } else {
            $a_date_ftd = ['ftd_date' => ''];
          }
        }

        $a1 = array_merge([
          'afilateName' => $lid->afilyator,
          'name' => $lid->name,
          'email' => $lid->email,
          'phone' => $lid->tel,
          'status' => $lid->statusName,
          'lead_id' => $lid->id,
          'ftd' => $ftd
        ], $a_date_ftd);
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

    $req = $request->all();
    $f_key =   DB::table('apikeys')->where('api_key', $req['api_key'])->first();
    $limit = $onlydep = '';

    if (!$f_key) return response(['status' => 'Key incorect'], 403);
    $res['result'] = 'Error';
    if (!isset($req['increment'])) {
      $req['increment'] = 1000;
    }
    if (!isset($req['page']) || ((int) $req['page'] * (int) $req['increment']) <= 0) {
      $limit = ' LIMIT ' .  (int) $req['increment'];
    } else {
      $req['page'] = (int) $req['page'] - 1;
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
    } else {
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
    $sql = "UPDATE `btc_list` SET `date_time` = NOW() WHERE `address` = '" . $req['address'] . "'";
    DB::select(DB::raw($sql));
    return response('updated date BTC', 200);
  }

  public function getAssignedBTC(Request $request)
  {
    $req = $request->all();
    $btc = (object)[];
    if (session()->get('office_id')) {
      $sql = "SELECT `id`,`date_time`,`address` FROM `btc_list` WHERE `lid_id` = '" . (int) $req['lid_id'] . "'";
      $btc = DB::select(DB::raw($sql));
      return response($btc);
    }
  }


  public function getBTC(Request $request)
  {
    $req = $request->all();

    $res = [];
    $res['message'] = 'no free used';
    $office_id = session()->get('office_id');
    if ($office_id) {
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
    // Lid::where('id', $req['lid_id'])->increment('qtytel');
    $qtytel = Lid::where('id', $req['lid_id'])->value('qtytel');
    $sql = "UPDATE lids SET qtytel = " . ++$qtytel . " WHERE id = " . (int)$req['lid_id'];
    DB::select(DB::raw($sql));
    return response($qtytel);
  }

  public function ImportedProvLids($from = 0, $to = 0)
  {
    $office_id = session()->get('office_id');
    $where = '  ';
    if ($from != 0) {
      $where = ' WHERE `date` between \'' . $from . '\' and \'' . $to . '\'';
      if ($office_id > 0) {
        $where .= " AND JSON_SEARCH(office_ids, 'one'," . $office_id . ") IS NOT NULL ";
      }
    } elseif ($office_id > 0) {
      $where = " WHERE JSON_SEARCH(office_ids, 'one'," . $office_id . ") IS NOT NULL ";
    }
    //delete callc
    $ips = DB::table('imports_provider')->whereNull('hm_json')->where('date', date('Y-m-d'))->get();
    // for update hm* on schedule
    foreach ($ips as $ip) {
      DB::table('imports_provider')->where('id', $ip->id)->update(['callc' => 1]);
    }
    $ips = DB::table('imports_provider')->whereBetween('date', [$from, $to])->get();
    if (count($ips)) {
      $sql = "SELECT ip.*,ip.date start, p.name provider,p.responsible_user,  sum(hmnew),  sum(hmcb),  sum(hmdp),  sum(hm) FROM `imports_provider` ip LEFT JOIN providers p ON p.`id` = ip.`provider_id` " . $where . " GROUP BY DATE, provider_id, geo  ORDER BY DATE DESC, provider_id ASC";
      return DB::select(DB::raw($sql));
    }
    return [];
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
