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
use DB;
use Debugbar;
use Carbon\Carbon;

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
      $sortBy = ["tel" => 'tel', "name" => 'name', "email" => 'email', "provider" => 'provider_id', "user" => 'user_id', "date_created" => 'created_at', "date_updated" => 'updated_at', 'afilyator' => 'afilyator', 'text' => 'text', 'qtytel' => 'qtytel', 'ontime' => 'ontime', 'status' => 'status_id', 'depozit' => false][$data['sortBy']];
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
        ->whereIn('user_id', $a_user_ids)
        ->when($office_id > 0, function ($query) use ($office_id) {
          return $query->where('office_id', $office_id);
        })
        ->when(strpos($search, '@') != false, function ($query) use ($search) {
          return $query->where('email', $search);
        })
        ->when(strpos($search, '@') === false, function ($query) use ($search) {
          return $query->whereRaw('MATCH(NAME,tel,email,TEXT) AGAINST ("' . $search . '")');
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
      $q_leads = Lid::select('*')
        // ->when(in_array(0, $office_ids), function ($query) use ($office_ids) {
        //   return $query->whereIn('office_id', $office_ids);
        // })
        ->when(strpos($search, '@') != false, function ($query) use ($search) {
          return $query->where('email', $search);
        })
        ->when(strpos($search, '@') === false, function ($query) use ($search) {
          return $query->whereRaw('MATCH(lids.name,lids.tel,lids.email,lids.text) AGAINST (\'"' . $search . '"\' IN NATURAL LANGUAGE MODE)');
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
    $res = [];
    $res['date_start'] = date('Y-m-d H:i:s');

    $data = $request->all();
    $office_id = User::where('id', (int) $data['user_id'])->value('office_id');
    //Debugbar::info($data['data']);

    foreach ($data['data'] as $lid) {
      $n_lid = new Lid;
      $n_lid->load_key = strtotime($res['date_start']);
      if (isset($lid['name'])) {
        $n_lid->name =  substr(trim($lid['name']), 0, 50);
      }

      if (isset($lid['lastname'])) {
        $n_lid->name =  $n_lid->name . ' ' . substr(trim($lid['lastname']), 0, 50);
      }

      if (isset($lid['tel'])) {
        $n_lid->tel =  preg_replace('/[^0-9]/', '', $lid['tel']);
      } else {
        continue;
      }

      if (isset($lid['email'])) {
        $n_lid->email = $lid['email'];
      }


      $n_lid->user_id = $data['user_id'];
      $n_lid->office_id = $office_id;
      $n_lid->created_at = Now();

      if (isset($lid['afilyator'])) {
        $n_lid->afilyator = substr(trim($lid['afilyator']), 0, 50);
      } else {
        $n_lid->afilyator = '';
      }
      if (isset($data['provider_id'])) $n_lid->provider_id = $data['provider_id'];
      if (isset($data['status_id']))  $n_lid->status_id = $data['status_id'];
      $f_lid =  Lid::where('tel', '=', "" . $lid['tel'])->get();

      if (!$f_lid->isEmpty()) {
        $n_lid->status_id = 22;
      }
      if ($n_lid->provider_id == '76') {
        $n_lid->status_id = 8;
      }
      $n_lid->save();
    }
    $res['date_end'] = date('Y-m-d H:i:s');
    return response($res, 200);
  }

  public function checkEmails(Request $request)
  {
    $data = $request->all();
    $results = $a_tel = [];
    $where_email_tel = $group_email_tel = '';
    if (isset($data['email_tel']) && $data['email_tel'] == 'tel') {
      foreach (array_filter($data['emails']) as $tel) {
        $a_tel[] = preg_replace('/[^0-9]/', '', $tel);
      }
      $where_email_tel = " WHERE l.`tel` IN (\"" . implode('","', array_filter($a_tel)) . "\")";
      $group_email_tel = " GROUP BY `tel`";
    } else {
      $where_email_tel = " WHERE `email` IN (\"" . implode('","', array_filter($data['emails'])) . "\")";
      $group_email_tel = " GROUP BY `email`";
    }
    if (isset($data['check'])) {
      $sql = "SELECT l.`id`, l.`tel`,l.`name`,l.`text`,l.`created_at` created, l.`updated_at` updated, l.`office_id`, l.`provider_id`,l.`status_id`, s.`name` status_name, l.`email`, u.`name` user_name, p.`name` provider_name, l.`afilyator`, o.`name` office_name FROM `lids` l LEFT JOIN `providers` p ON (p.`id` = l.`provider_id`) LEFT JOIN `statuses` s ON (s.`id` = l.`status_id`) LEFT JOIN `users` u ON (u.`id` = l.`user_id`) LEFT JOIN `offices` o ON (o.`id` = l.`office_id`) " . $where_email_tel . " ORDER BY l.created_at ASC";
      $results['leads'] =  DB::select(DB::raw($sql));
    }

    DB::select(DB::raw("SET SQL_MODE = '';"));
    // $sql = "SELECT l.`tel`,l.`name`,8 AS status_id, l.`email`, 252 AS user_id, 75 AS provider_id, p.`name` AS afilyator,NOW() as created_at,3 AS office_id FROM `lids` l LEFT JOIN `providers` p ON (p.`id` = l.`provider_id`) " . $where_email_tel . $group_email_tel;
    $sql = "SELECT l.`tel`, LOWER(l.`email`) email FROM `lids` l " . $where_email_tel . $group_email_tel;
    $leads =  DB::select(DB::raw($sql));
    $leads = array_map(function ($item) {
      return (array) $item;
    }, $leads);
    // Lid::insert($leads);
    if (isset($data['email_tel']) && $data['email_tel'] == 'tel') {
      $results['emails'] = array_column($leads, 'tel');
    } else {
      $results['emails'] = array_column($leads, 'email');
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
      ->when($tel != '', function ($query) use ($tel) {
        return $query->where('tel', 'like', $tel . '%');
      })
      ->when($search != '', function ($query) use ($search) {
        return $query->where(function ($query) use ($search) {
          return $query->where('name', 'like', '%' . $search . '%')->orWhere('email', 'like', '%' . $search . '%');
        });
      });
    $response['hm'] = $q_leads->count();

    $response['lids'] = $q_leads->orderBy('lids.created_at', 'desc')
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

    $office_ids = [session()->get('office_id')];
    if (in_array(0, $office_ids)) {
      $office_ids =  $data['office_ids'];
    }

    $id = $data['id'];
    $status_id = $data['status_id'];
    $tel = $data['tel'];
    $limit = $data['limit'];
    $page = (int) $data['page'];
    $filterLang = $data['filterLang'];
    $providers = $date = $users_ids = [];
    $where_date = '';
    $duplicate_tel = [];
    if (isset($data['sortBy'])) {
      $sortBy = ["tel" => 'tel', "name" => 'name', "email" => 'email', "provider" => 'provider_id', "user" => 'user_id', "date_created" => 'created_at', "date_updated" => 'updated_at', 'afilyator' => 'afilyator', 'text' => 'text', 'qtytel' => 'qtytel', 'ontime' => 'ontime', 'status' => 'status_id', 'depozit' => 'depozit'][$data['sortBy']];
      $sortDesc = $data['sortDesc'] ? 'DESC' : 'ASC';
    } else {
      $sortBy = 'created_at';
      $sortDesc = 'DESC';
    }

    if (isset($data['duplicate_tel'])) {
      $duplicate_tel = Lid::select('tel')->where('user_id', $id)->groupBy('tel')->having(DB::raw('count(tel)'), '>', 1);
    }
    if (isset($data['group_ids'])) {
      $res = User::select('id')->whereIn('group_id', $data['group_ids'])->get()->toArray();
      foreach ($res as $item) {
        $users_ids[] = $item['id'];
      }
    }
    if (isset($data['datefrom'])) {
      $date = [date('Y-m-d', strtotime($data['datefrom'])) . ' ' . date('H:i:s', mktime(0, 0, 0)), date('Y-m-d', strtotime($data['dateto'])) . ' ' . date('H:i:s', mktime(23, 59, 59))];
      $where_date = " AND created_at >= '" . $date[0] . "' AND created_at <= '" . $date[1] . "'";
    }

    if (count($data['provider_id']) > 0) {
      $providers = $data['provider_id'];
    } else {
      if (!in_array(0, $office_ids)) {
        $res = Provider::select('id')->whereIn('office_id',  $office_ids)->get()->toArray();
        foreach ($res as $item) {
          $providers[] = $item['id'];
        }
      }
    }


    $response = [];
    $q_leads = Lid::select('lids.*', DB::Raw('(SELECT SUM(`depozit`) FROM `depozits` WHERE `lids`.`id` = `depozits`.`lid_id`' . $where_date . ') depozit'))
      ->when(!is_array($id) && $id > 0 && count($users_ids) === 0, function ($query) use ($id) {
        return $query->where('lids.user_id', $id);
      })
      ->when(count($users_ids) > 0, function ($query) use ($users_ids) {
        return $query->whereIn('lids.user_id', $users_ids);
      })
      ->when(!in_array(0, $office_ids), function ($query) use ($office_ids) {
        return $query->whereIn('lids.office_id', $office_ids);
      })
      ->when(count($providers) > 0, function ($query) use ($providers) {
        return $query->whereIn('lids.provider_id', $providers);
      })
      ->when(count($status_id) > 0, function ($query) use ($status_id) {
        return $query->whereIn('lids.status_id', $status_id);
      })
      ->when($tel != '', function ($query) use ($tel) {
        return $query->where('lids.tel', 'like', $tel . '%');
      })
      ->when(isset($data['duplicate_tel']), function ($query) use ($duplicate_tel) {
        return $query->whereIn('lids.tel', $duplicate_tel);
      })
      ->when(count($date) > 0, function ($query) use ($date) {
        return $query->whereBetween('lids.created_at', $date);
      })
      ->when($filterLang != '', function ($query) use ($filterLang) {
        return $query->where('lids.client_lang', $filterLang);
      })
      ->when(isset($data['callback']) && $data['callback'] == 1, function ($query) {
        return $query->where(DB::Raw('(SELECT count(*) cnt FROM `logs` WHERE `lids`.`id` = `logs`.`lid_id` AND `logs`.`status_id` = 9)'), '>', 0);
      });
    // ->when(isset($data['sortBy']) && $data['sortBy'][0] == 'afilyator', function ($query) use ($data) {
    //   return $query->orderBy('lids.afilyator', $data['sortBy'][1]);
    // })
    // ->when(isset($data['sortBy']) && $data['sortBy'][0] == 'provider', function ($query) use ($data) {
    //   return $query->leftJoin('providers', 'lids.provider_id', '=', 'providers.id')->orderBy('providers.name', $data['sortBy'][1]);
    // })
    // ->when(isset($data['sortBy']) && $data['sortBy'][0] == 'date_created', function ($query) use ($data) {
    //   return $query->orderBy('lids.created_at', $data['sortBy'][1]);
    // })
    // ->when(isset($data['sortBy']) && $data['sortBy'][0] == 'date_updated', function ($query) use ($data) {
    //   return $query->orderBy('lids.updated_at', $data['sortBy'][1]);
    // })

    $response['hm'] = $q_leads->count();

    $response['lids'] = $q_leads
      ->when($limit != 'all' && $page * $limit > $limit, function ($query) use ($limit, $page) {
        return $query->offset($limit * ($page - 1));
      })
      ->when($limit != 'all', function ($query) use ($limit) {
        return $query->limit($limit);
      })
      ->when($sortBy && !in_array($sortBy, ['provider_id', 'user_id', 'depozit']), function ($query) use ($sortBy, $sortDesc) {
        return $query->orderBy('lids.' . $sortBy, $sortDesc);
      })
      ->when($sortBy && $sortBy == 'provider_id', function ($query) use ($sortDesc) {
        return $query->leftJoin('providers', 'providers.id', '=', 'lids.provider_id')->orderBy('providers.name', $sortDesc);
      })
      ->when($sortBy && $sortBy == 'user_id', function ($query) use ($sortDesc) {
        return $query->leftJoin('users', 'users.id', '=', 'lids.user_id')->orderBy('users.name', $sortDesc);
      })
      ->when($sortBy && $sortBy == 'depozit', function ($query) use ($sortBy, $sortDesc) {
        return $query->orderBy($sortBy, $sortDesc);
      })
      ->get();

    if ($page == 0) {
      $response['statuses'] = $q_leads->select(DB::Raw('count(statuses.id) hm'), DB::Raw('(SELECT SUM(`depozit`) FROM `depozits` WHERE `lids`.`id` = `depozits`.`lid_id`' . $where_date . ') depozit'), 'statuses.id', 'statuses.name', 'statuses.color')
        ->leftJoin('statuses', 'statuses.id', '=', 'status_id')
        ->when(count($date) > 0, function ($query) use ($date) {
          return $query->whereBetween('lids.created_at', $date);
        })
        ->when(!is_array($id) && $id > 0 && count($users_ids) === 0, function ($query) use ($id) {
          return $query->where('lids.user_id', $id);
        })
        ->when(count($users_ids) > 0, function ($query) use ($users_ids) {
          return $query->whereIn('lids.user_id', $users_ids);
        })
        ->when(!in_array(0, $office_ids), function ($query) use ($office_ids) {
          return $query->whereIn('lids.office_id', $office_ids);
        })
        ->groupBy('id')
        //->orderBy('lids.created_at', 'DESC')
        ->orderBy('statuses.order', 'ASC')
        ->get();
      $response['languges'] = Lid::select('client_lang as name')
        ->where('client_lang', '!=', null)
        ->when(count($date) > 0, function ($query) use ($date) {
          return $query->whereBetween('lids.created_at', $date);
        })
        ->when(!is_array($id) && $id > 0 && count($users_ids) === 0, function ($query) use ($id) {
          return $query->where('lids.user_id', $id);
        })
        ->when(count($users_ids) > 0, function ($query) use ($users_ids) {
          return $query->whereIn('lids.user_id', $users_ids);
        })
        ->when(!in_array(0, $office_ids), function ($query) use ($office_ids) {
          return $query->whereIn('lids.office_id', $office_ids);
        })
        ->groupBy('client_lang')
        //->orderBy('lids.created_at', 'DESC')
        ->orderBy('client_lang', 'ASC')
        ->get();
    }

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


    $date = [$getparams['from_date'], $getparams['to_date']];
    // if ($getlid['api_key'] != env('API_KEY')) return response(['status'=>'Key incorect'], 403);
    $f_key =  DB::table('apikeys')->where('api_key', $getparams['api_key'])->first();

    if (!$f_key) return response(['status' => 'Key incorect'], 403);
    $sql = "SELECT
    d.`lid_id` AS `order_lead_id`
    , d.`created_at` AS `ftd_date`
    , l.`created_at` AS 'dateAdd'
    , IF(l.status_id = '10','1','0') AS FTD
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
    if (isset($req['provider_id']) && isset($req['start']) && isset($req['end'])) {
      $lids =  Lid::where('provider_id', (int) $req['provider_id'])->whereBetween('created_at', [$req['start'], $req['end']])->get();
      if ($lids->count()) {
        return $lids;
      }
      //  else {
      //   // $date_end = substr($req['end'],0,10);
      //   // $date_start = date('Y-m-d H:i:s', strtotime('0', strtotime($req['end'])));
      //   $date_start = $req['start'];
      //   $date_end = date('Y-m-d H:i:s', strtotime('+2 hour +1 minutes', strtotime($req['end'])));
      //   return  Lid::where('provider_id', (int) $req['provider_id'])->whereBetween('created_at', [$date_start, $date_end])->get();
      // }
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

    Lid::whereIn('id', $lids)->delete();
    Log::whereIn('lid_id', $lids)->delete();
  }

  public function clearLiads(Request $request)
  {
    $lids = $request->all();
    Lid::whereIn('id', $lids)->update(['status_id' => 8, 'text' => '', 'qtytel' => 0]);
    Log::whereIn('lid_id', $lids)->delete();
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

    if (isset($req['umcfields']['phone']) && strlen($req['umcfields']['phone']) > 1) {
      $n_lid->tel =  preg_replace('/[^0-9]/', '', $req['umcfields']['phone']);
      $added_date =  Lid::where('tel', '=', '' . $n_lid->tel)->orderBy('created_at', 'desc')->value('created_at');
      if ($added_date != '') {
        $date = Carbon::now();
        $added_date = Carbon::parse($added_date);
        if ($date->diffInDays($added_date) < 14) {
          return response('you have to wait ');
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
    if (isset($req['client_geo'])) {
      $n_lid->client_geo = $req['client_geo'];
    } else {
      $n_lid->client_geo = '';
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

    $n_lid->afilyator = $req['umcfields']['affiliate_user'];
    $n_lid->provider_id = $f_key->id;
    $n_lid->user_id = (int) $req['user_id'];

    $n_lid->created_at = Now();

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
    }

    if ($phonestr) {
      $n_lid->tel = preg_replace('/[^0-9]/', '', $phonestr);
      $added_date =  Lid::where('tel', '=', '' . $n_lid->tel)->orderBy('created_at', 'desc')->value('created_at');
      if ($added_date != '') {
        $date = Carbon::now();
        $added_date = Carbon::parse($added_date);
        if ($date->diffInDays($added_date) < 14) {
          return response('you have to wait ');
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
    if (isset($req['client_geo'])) {
      $n_lid->client_geo = $req['client_geo'];
    } else {
      $n_lid->client_geo = '';
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

    if (isset($req['umcfields[name]']) && strlen($req['umcfields[name]']) > 1) {
      $n_lid->name =  $req['umcfields[name]'];
    } else {
      $n_lid->name = time();
    }

    if (isset($req['umcfields[phone]']) && strlen($req['umcfields[phone]']) > 1) {
      $n_lid->tel =  preg_replace('/[^0-9]/', '', $req['umcfields[phone]']);
      $added_date =  Lid::where('tel', '=', '' . $n_lid->tel)->orderBy('created_at', 'desc')->value('created_at');
      if ($added_date != '') {
        $date = Carbon::now();
        $added_date = Carbon::parse($added_date);
        if ($date->diffInDays($added_date) < 14) {
          return response('you have to wait ');
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
    if (isset($req['client_geo'])) {
      $n_lid->client_geo = $req['client_geo'];
    } else {
      $n_lid->client_geo = '';
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
    $n_lid->created_at = Now();

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
    $n_lid = new Lid;

    if (isset($req['umcfields']['name']) && strlen($req['umcfields']['name']) > 1) {
      $n_lid->name =  $req['umcfields']['name'];
    } else {
      $n_lid->name = time();
    }

    if (isset($req['umcfields']['phone']) && strlen($req['umcfields']['phone']) > 1) {
      $n_lid->tel =  $req['umcfields']['phone'];
      $added_date =  Lid::where('tel', '=', '' . $n_lid->tel)->orderBy('created_at', 'desc')->value('created_at');
      if ($added_date != '') {
        $date = Carbon::now();
        $added_date = Carbon::parse($added_date);
        if ($date->diffInDays($added_date) < 14) {
          return response('you have to wait ');
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
    if (isset($req['client_geo'])) {
      $n_lid->client_geo = $req['client_geo'];
    } else {
      $n_lid->client_geo = '';
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