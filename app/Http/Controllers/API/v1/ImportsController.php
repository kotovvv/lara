<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

use App\Models\Import;
use App\Models\Lid;
use App\Models\Log;
use App\Models\User;
use Storage;
use Illuminate\Support\Facades\DB;
use Debugbar;


class ImportsController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index($from = 0, $to = 0)
  {
    $office_id = session()->get('office_id');

    $imports = Import::with(['provider', 'user'])
      ->when($from != 0 && $to != 0, function ($query) use ($from, $to) {
        return $query->whereBetween('start', [$from . ' 00:00:00', $to . ' 23:59:59']);
      })
      ->when($office_id > 0, function ($query) use ($office_id) {
        return $query->whereNotNull(DB::raw("JSON_SEARCH(office_ids, 'one', $office_id)"));
      })
      ->orderByDesc('end')
      ->get();

    foreach ($imports as $import) {
      $import->provider_name = $import->provider->name;
      $import->user_name = $import->user->fio;
    }

    Import::whereNull('hm_json')
      ->whereDate('start', date('Y-m-d'))
      ->update(['callc' => 1]);

    return $imports;
  }

  public function putBTC(Request $request)
  {
    $data = $request->all();
    foreach ($data['data'] as $key => $row) {
      $data['data'][$key]['office_id'] = $data['office_id'];
    }
    $response = DB::table('btc_list')->insertOrIgnore($data['data']);
    return $response;
  }

  private function  date_range($first, $last, $step = '+1 day', $output_format = 'Y-m-d')
  {
    $dates = array();
    $current = strtotime($first);
    $last = strtotime($last);
    while ($current <= $last) {
      $dates[] = date($output_format, $current);
      $current = strtotime($step, $current);
    }
    return $dates;
  }

  private function between_dates($date, $datefrom, $dateto)
  {
    $dateFrom = strtotime($datefrom);
    $dateTo = strtotime($dateto);
    $u_date = strtotime($date);
    if ($u_date >= $dateFrom && $u_date <= $dateTo) {
      return true;
    }
    return false;
  }

  public function getBTCotherOnDate(Request $request)
  {
    $req = $request->all();
    $office_id = session()->get('office_id');
    $dateFrom = $req['datefrom'];
    $dateTo = $req['dateto'];
    $onlynew = $req['onlynew'];

    $query = DB::table('btc_list as bl')
      ->leftJoin('lids as l', 'bl.lid_id', '=', 'l.id')
      ->leftJoin('providers as p', 'l.provider_id', '=', 'p.id')
      ->leftJoin('statuses as s', 'l.status_id', '=', 's.id')
      ->leftJoin('depozits as d', function ($join) use ($dateFrom, $dateTo) {
        $join->on('l.id', '=', 'd.lid_id')
          ->where('d.created_at', '>=', $dateFrom . ' 00:00:00')
          ->where('d.created_at', '<=', $dateTo . ' 23:59:59');
      })
      ->select(
        'bl.id',
        // 'bl.address',
        'bl.summ',
        'bl.office_id',
        'bl.other',
        'bl.trx_count',
        'l.id as lid_id',
        'l.name',
        'l.created_at',
        'l.tel',
        'l.email',
        'l.provider_id',
        'l.status_id',
        's.name as s_name',
        's.color as s_color',
        'p.name as p_name',
        DB::raw('COALESCE(SUM(d.depozit), 0) as depozit')
      )
      ->whereBetween('bl.date_time', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
      ->whereRaw($office_id > 0 ? "bl.office_id = $office_id AND `other` REGEXP '[^|].*'" : "`other` REGEXP '[^|].*'")
      ->groupBy(
        'bl.id',
        // 'bl.address',
        'bl.summ',
        'bl.office_id',
        'bl.other',
        'bl.trx_count',
        'l.id',
        'l.name',
        'l.created_at',
        'l.tel',
        'l.email',
        'l.provider_id',
        'l.status_id',
        's.name',
        's.color',
        'p.name'
      )
      ->orderBy('l.id')
      ->get();

    $a_list_date = $this->date_range($dateFrom, $dateTo);
    $res['data'] = $data = [];
    $res['providers'] = [];
    $res['statuses'] = [];
    $res['result'] = "success";

    $compareLidId = $sum_lid = $ia = 0;
    if ($query) {
      foreach ($query as $lid) {
        $a_date_sum = $a_intersect = [];
        $sum_dat = 0;
        $other = $lid->other;
        $a_date_sum[0] = explode('|', $other);
        $max = count($a_date_sum[0]);
        for ($i = 1; $i < $max; $i += 2) {
          $a_date_sum[1][] = date('Y-m-d', $a_date_sum[0][$i]);
        }

        $a_intersect = array_intersect($a_date_sum[1], $a_list_date);
        if ($onlynew && count($a_date_sum[1]) != count($a_intersect)) {
          continue;
        }
        if ($a_intersect) {
          foreach ($a_intersect as $key => $date) {
            if ($this->between_dates($date, $dateFrom, $dateTo)) {
              $sum_dat += $a_date_sum[0][($key + 1) * 2];
            }
            $res['providers'][] = ['id' => $lid->provider_id, 'name' => $lid->p_name];
            $res['statuses'][] = ['id' => $lid->status_id, 'name' => $lid->s_name, 'color' => $lid->s_color];
          }
          if ($compareLidId == $lid->lid_id) {
            $data[$ia - 1]['summ'] += $lid->summ;
            $data[$ia - 1]['sum_dat'] += $sum_dat;
            continue;
          } else {
            $compareLidId = $lid->lid_id;
            $data[$ia++] = [
              'id' => $lid->id,
              'name' => $lid->name,
              'email' => $lid->email,
              'tel' => $lid->tel,
              'created_at' => $lid->created_at,
              // 'address' => $lid->address,
              'lid_id' => $lid->lid_id,
              'status_id' => $lid->status_id,
              's_name' => $lid->s_name,
              'summ' => $lid->summ,
              'office_id' => $lid->office_id,
              'provider_id' => $lid->provider_id,
              'p_name' => $lid->p_name,
              'sum_dat' => $sum_dat,
              'depozit' => (int) $lid->depozit
            ];
          }
        }
      }
    }

    $res['data'] = array_values($data);
    return $res;
  }

  public function getBTCsOnDate(Request $request)
  {
    $res = [];
    $req = $request->all();
    if (isset($req['office_id'])) {
      $office_id = $req['office_id'];
    } else {
      $office_id = session()->get('office_id');
    }
    $where = $office_id > 0 ? "  l.`office_id` = " . $office_id . " AND " : "";

    $date = [date('Y-m-d', strtotime($req['datefrom'])) . ' ' . date("H:i:s", mktime(0, 0, 0)), date('Y-m-d', strtotime($req['dateto'])) . ' ' . date("H:i:s", mktime(23, 59, 59))];

    $res['list'] = DB::select("SELECT bl.`address`, bl.`summ`, bl.`trx_count`, bl.`date_time`, l.`id` lid_id, u.`fio`,u.`office_id` FROM `btc_list` bl INNER JOIN `lids` l ON (bl.`lid_id` = l.`id` ) INNER JOIN `users` u ON (bl.`user_id` = u.`id` ) WHERE " . $where . " bl.`date_time` >= '" . $date[0] . "' AND bl.`date_time` <= '" . $date[1] . "'");

    $res['free'] = DB::select('SELECT COUNT(*) count,office_id FROM `btc_list` WHERE used = 0 GROUP BY office_id');


    return response($res, 200);
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
  public function store(Request $request, $from = 0, $to = 0)
  {
    $a_import = $request->all();
    $office_ids = Lid::where('load_mess', $a_import['message'])->groupBy('office_id')->pluck('office_id')->toArray();
    if (count($office_ids) > 0 && !in_array(0, $office_ids)) {
      $a_import['office_ids'] = json_encode($office_ids);
    }
    $a_import['callc'] = 1;
    DB::table('imports')->insert($a_import);
  }

  public function deleteLoad(Request $request, $load_key)
  {
    DB::table('lids')->where('load_key', $load_key)->delete();
    DB::table('imports')->where('load_key', $load_key)->delete();
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


  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request

   * @return \Illuminate\Http\Response
   */
  public function importUpdate(Request $request)
  {
    $data = $request->all();
    if (isset($data['message'])) {
      $import = DB::table('imports')->where('id', $data['id'])->first();
      $date = date('Y-m-d', strtotime($import->start));
      DB::table('lids')->where('load_mess', $import->message)->whereDate('created_at', $date)->update(['load_mess' => $data['message']]);
      if ($import->provider_id != $data['provider_id']) {
        Lid::where('load_mess', $import->message)->update(['provider_id' => $data['provider_id']]);
      }
      DB::table('imports')->where('id', $data['id'])->update(['message' => $data['message'], 'sum' => $data['sum'], 'cp' => $data['cp'], 'provider_id' => $data['provider_id'], 'baer' => $data['baer'] == 'null' ? '' : $data['baer']]);
    } else {
      DB::table('imports_provider')->where('id', $data['id'])->update(['sum' => $data['sum'], 'cp' => $data['cp']]);
    }
  }

  public function getHistory(Request $request)
  {
    $data = $request->all();
    $loads_id = $data['id'];
    $message = isset($data['message']) ? $data['message'] : null;
    $response = [];
    $response['statuses'] = [];
    $lidids = DB::table('historyimport')->where('imports_id', $loads_id)->when($message != null, function ($query) use ($message) {
      return $query->where('load_mess', $message);
    })->orderBy('created_at', 'DESC')->get()->pluck('lids')->toArray();
    // $sql= "SELECT lids FROM `historyimport` WHERE imports_id = ". $loads_id." ORDER BY created_at DESC LIMIT 1";
    if (count($lidids)) {
      $getLiads = Lid::whereIn('lids.id', explode(',', $lidids[0]));
      $response['statuses'] = $getLiads->select(DB::Raw('count(statuses.id) hm'), 'statuses.id', 'statuses.name', 'statuses.color')
        ->leftJoin('statuses', 'statuses.id', '=', 'status_id')
        ->groupBy('statuses.id')
        ->orderBy('statuses.order', 'ASC')
        ->get();
    }

    $response['history'] = DB::table('historyimport')->where('imports_id', $loads_id)->orderBy('created_at', 'DESC')->get();
    return $response;
  }
  public function redistributeLids(Request $request)
  {
    $data = $request->all();
    $lid_ids = $data['lid_ids'];
    $usersIds = $data['usersIds'];
    $resetStatus = $data['resetStatus'];
    $id = $data['id'];
    $provider_id = $data['provider_id'];
    $message = isset($data['message']) ? $data['message'] : '';
    $start = $data['start'];
    //$end = isset($data['end']) ? $data['end'] : '';
    $geo = isset($data['geo']) ? $data['geo'] : '';

    $historyimp = [];
    if ($message) {

      //?- get row from imports on id - $id']
      //- get id from lids on load_mess and date
      $getLiads = Lid::where('load_mess', $message)
        ->whereDate('lids.created_at', date('Y-m-d', strtotime($start)));
      //- get statuses for this leads
      $historyimp['statuses'] = $getLiads->select(DB::Raw('count(statuses.id) hm'), 'statuses.id', 'statuses.name', 'statuses.color')
        ->leftJoin('statuses', 'statuses.id', '=', 'status_id')
        ->groupBy('statuses.id')
        ->orderBy('statuses.order', 'ASC')
        ->get();


      $historyimp['lids'] = implode(',', $lid_ids);

      $historyimp['imports_id'] = $id;
      $historyimp['load_mess'] = $message;
      $historyimp['created_at'] = Now();
      //- insert to history
      DB::table('historyimport')->insert($historyimp);
    } else {
      //- get id lids from imported_lids on provider_id and date
      $lidsId = DB::table('imported_leads')->where('api_key_id', $provider_id)->whereDate('upload_time', $start)->where('geo', $geo)->pluck('lead_id')->toArray();


      $getLiads = Lid::whereIn('lids.id', $lidsId);

      //- get statuses for this liads
      $historyimp['statuses'] = $getLiads->select(DB::Raw('count(statuses.id) hm'), 'statuses.id', 'statuses.name', 'statuses.color')
        ->leftJoin('statuses', 'statuses.id', '=', 'status_id')
        ->groupBy('statuses.id')
        ->orderBy('statuses.order', 'ASC')
        ->get();

      $historyimp['lids'] = implode(',', $lid_ids);
      $historyimp['imports_id'] = $id;
      $historyimp['created_at'] = Now();
      //- insert to history
      DB::table('historyimport')->insert($historyimp);
    }
    $hm = ceil(count($lid_ids) / count($usersIds));

    foreach (array_chunk($lid_ids, $hm) as $n_user => $lid_ids) {
      $office_id = User::where('id', (int) $usersIds[$n_user])->value('office_id');

      Lid::whereIn('id', $lid_ids)->update(['user_id' => $usersIds[$n_user], 'updated_at' => Now(), 'office_id' => $office_id, 'status_id' => 8, 'text' => '', 'qtytel' => 0]);
    }
    if ($message) {
      // $a_group_ids = json_encode(
      //   Lid::where('load_mess', $message)->leftJoin('users', 'users.id', '=', 'lids.user_id')->whereDate('lids.created_at', date('Y-m-d', strtotime($start)))->groupBy('users.group_id')->pluck('users.group_id')->toArray()
      // );

      // get offices users
      $a_office_ids = json_encode(Lid::where('load_mess', $message)
        ->whereDate('lids.created_at', date('Y-m-d', strtotime($start)))->where('office_id', '!=', 0)->groupBy('office_id')->orderBy('id', 'ASC')->pluck('office_id')->toArray());
      DB::table('imports')->where('id', $id)->update(['office_ids' => $a_office_ids]);
    } else {
      // get offices users
      $a_office_ids = json_encode(Lid::whereIn('lids.id', $lidsId)->where('office_id', '!=', 0)->groupBy('office_id')->orderBy('id', 'ASC')->pluck('office_id')->toArray());
      DB::table('imports_provider')->where('id', $id)->update(['office_ids' => $a_office_ids]);
    }
    Log::whereIn('lid_id', $lid_ids)->delete();
    return response('All done', 200);
  }

  public function redistribute(Request $request)
  {
    $data = $request->all();
    $imoprtsIdsm = $data['importsIdsm'];
    $usersIds = $data['usersIds'];
    $resetStatus = $data['resetStatus'];
    $alliads = [];
    $setLiads = [];

    foreach ($imoprtsIdsm as $import_) {

      $historyimp = [];
      if (isset($import_['message'])) {

        //?- get row from imports on id - $import_['id']
        //- get id from lids on load_mess and date
        $getLiads = Lid::where('load_mess', $import_['message'])
          ->whereDate('lids.created_at', date('Y-m-d', strtotime($import_['start'])));

        //- get statuses for this leads
        $historyimp['statuses'] = $getLiads->select(DB::Raw('count(statuses.id) hm'), 'statuses.id', 'statuses.name', 'statuses.color')
          ->leftJoin('statuses', 'statuses.id', '=', 'status_id')
          ->groupBy('statuses.id')
          ->orderBy('statuses.order', 'ASC')
          ->get();
        //- select lids which must changed
        $setLiads = Lid::where('load_mess', $import_['message'])->when(count($resetStatus) > 0, function ($query) use ($resetStatus) {
          return $query->whereIn('status_id', $resetStatus);
        })->whereDate('lids.created_at', date('Y-m-d', strtotime($import_['start'])))->get()->pluck('id')->toArray();

        $historyimp['lids'] = implode(',', $setLiads);

        $historyimp['imports_id'] = $import_['id'];
        $historyimp['load_mess'] = $import_['message'];
        $historyimp['created_at'] = Now();
        //- insert to history
        DB::table('historyimport')->insert($historyimp);
        //- collect $alliads
        $alliads = array_merge($alliads, $setLiads);

        // $a_group_ids = json_encode(
        //   Lid::where('load_mess',  $import_['message'])->leftJoin('users', 'users.id', '=', 'lids.user_id')->whereDate('lids.created_at', date('Y-m-d', strtotime($import_['start'])))->groupBy('users.group_id')->pluck('users.group_id')->toArray()
        // );

        // get offices users
        $a_office_ids = json_encode(Lid::where('load_mess',  $import_['message'])
          ->whereDate('lids.created_at', date('Y-m-d', strtotime($import_['start'])))->where('office_id', '!=', 0)->groupBy('office_id')->orderBy('id', 'ASC')->pluck('office_id')->toArray());
        DB::table('imports')->where('id', $import_['id'])->update(['office_ids' => $a_office_ids]);
      } else {
        //- get id lids from imported_lids on provider_id and date
        $lidsId = DB::table('imported_leads')->where('api_key_id', $import_['provider_id'])->whereDate('upload_time', $import_['start'])->where('geo', $import_['geo'])->pluck('lead_id')->toArray();

        $getLiads = Lid::whereIn('lids.id', $lidsId);


        //- get statuses for this liads
        $historyimp['statuses'] = $getLiads->select(DB::Raw('count(statuses.id) hm'), 'statuses.id', 'statuses.name', 'statuses.color')
          ->leftJoin('statuses', 'statuses.id', '=', 'status_id')
          ->groupBy('statuses.id')
          ->orderBy('statuses.order', 'ASC')
          ->get();
        //- select lids which must changed
        $setLiads = Lid::whereIn('lids.id', $lidsId)->when(count($resetStatus) > 0, function ($query) use ($resetStatus) {
          return $query->whereIn('status_id', $resetStatus);
        })->get()->pluck('id')->toArray();
        $historyimp['lids'] = implode(',', $setLiads);
        $historyimp['imports_id'] = $import_['id'];
        $historyimp['created_at'] = Now();
        //- collect $alliads
        $alliads = array_merge($alliads, $setLiads);
        if (!$alliads) {
          return response('No alliads', 403);
        }
        //- insert to history
        DB::table('historyimport')->insert($historyimp);
      }
    }

    $hm = ceil(count($alliads) / count($usersIds));

    foreach (array_chunk($alliads, $hm) as $n_user => $lid_ids) {
      $office_id = User::where('id', (int) $usersIds[$n_user])->value('office_id');

      Lid::whereIn('id', $lid_ids)->update(['user_id' => $usersIds[$n_user], 'updated_at' => Now(), 'office_id' => $office_id, 'status_id' => 8, 'text' => '', 'qtytel' => 0]);
    }
    foreach ($imoprtsIdsm as $import_) {

      if (isset($import_['message'])) {
        // get offices users
        $a_office_ids = json_encode(Lid::where('load_mess', $import_['message'])
          ->whereDate('lids.created_at', date('Y-m-d', strtotime($import_['start'])))->where('office_id', '!=', 0)->groupBy('office_id')->orderBy('id', 'ASC')->pluck('office_id')->toArray());
        DB::table('imports')->where('id', $import_['id'])->update(['office_ids' => $a_office_ids]);
      } else {
        // get offices users
        $a_office_ids = json_encode(Lid::whereIn('lids.id', $lidsId)->where('office_id', '!=', 0)->groupBy('office_id')->orderBy('id', 'ASC')->pluck('office_id')->toArray());
        DB::table('imports_provider')->where('id', $import_['id'])->update(['office_ids' => $a_office_ids]);
      }
    }
    Log::whereIn('lid_id', $alliads)->delete();
    return response('All done', 200);
  }

  public function importCallc()
  {
    Artisan::call('import:callc');
    return response('Await', 200);
  }

  public  function checkLoadMess(Request $request)
  {
    $id = $request->get('id') !== null ? (int) $request->get('id') : 0;
    $load_mess = $request->get('load_mess');
    $exists = DB::table('imports')->where('message', $load_mess)->when($id, function ($query) use ($id) {
      return $query->where('id', '!=', $id);
    })->exists();

    return response()->json(['exists' => $exists]);
  }

  public function executeCommand(Request $request)
  {
    $command = $request->input('command');

    if ($command) {
      Artisan::call($command);
      return response()->json(['message' => 'Command executed successfully'], 200);
    }

    return response()->json(['error' => 'No command provided'], 400);
  }
}
