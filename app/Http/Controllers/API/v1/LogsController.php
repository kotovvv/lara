<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Log;
use DB;
use Debugbar;
use Carbon\Carbon;

class LogsController extends Controller
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

  public function onCdr(Request $request)
  {
    $office_id = session()->get('office_id');
    $user_id = (int) session()->get('user_id');
    $req = $request->all();
    $caller = $req['q'];
    $called = $req['w'];
    $duration = round($req['z'] / 1000);
    $reason = $req['x'];
    $connecttime = 0;
    if (isset($req['t'])) {
      $connecttime = round($req['t'] / 1000);
    }
    $date = Carbon::now();

    //get time last call
    $sql = "SELECT `timecall` FROM `calls` WHERE `user_id` = $user_id  AND date(`timecall`) = CURDATE() AND `timecall` < '$date' ORDER BY id DESC LIMIT 1";
    $lastcall = DB::select(DB::raw($sql));
    if ($lastcall) {
      $lastcall = Carbon::parse($lastcall[0]->timecall);
    } else {
      $lastcall = Carbon::now()->hour('09')->minute('00')->second('00');
    }
    //different between calls
    $cur = $date->subSeconds($duration + $connecttime);
    $diff = $cur->diffInSeconds($lastcall);

    $sql = "INSERT INTO `calls` (`user_id`,`user_tel`,`tel`,`duration`,`timecall`,`status`,`office_id`,`pausa`,`connecttime`) VALUES ($user_id,$caller,$called,$duration,'$date','$reason',$office_id,$diff,$connecttime)";
    DB::select(DB::raw($sql));
    // return response($sql, 200);
  }

  public function getCalls(Request $request)
  {
    $req = $request->all();
    $office_id = session()->get('office_id');

    if (isset($req['office_id'])) {
      $office_id = (int) $req['office_id'];
    }
    $office = "u.office_id = $office_id ";
    if ($office_id == 0) {
      $office = "1 ";
    }
    $where_user_group = '';
    if (isset($req['user_id_group'])) {
      $where_user_group = ' AND u.group_id = ' . $req['user_id_group'];
    }

    $dateFrom = $req['dateFrom'] . ' 00:00:00';
    $dateTo = $req['dateTo'] . ' 23:59:59';

    $sql = "
SELECT
    u.id,
    u.name,
    COUNT(c.id) as cnt,
    COUNT(DISTINCT c.tel) as utel,
    SEC_TO_TIME(SUM(c.duration) + SUM(c.connecttime)) as work,
    SEC_TO_TIME(SUM(c.duration)) as dur,
    SUM(c.duration > 120) as mr2,
    SEC_TO_TIME(ROUND(AVG(c.duration),0)) as avg,
    SEC_TO_TIME(SUM(c.pausa)) as pausa,
    SEC_TO_TIME(ROUND(AVG(c.pausa),0)) as spausa,
    SUM(c.duration > 0) as good,
    SUM(c.duration = 0) as bad,
    (SELECT u2.fio FROM users u2 WHERE u.group_id = u2.id) as grp
FROM users u
LEFT JOIN calls c ON c.user_id = u.id AND (c.timecall BETWEEN '$dateFrom' AND '$dateTo')
WHERE $office $where_user_group AND INSTR(u.servers,';')
GROUP BY u.id, u.name, u.group_id
ORDER BY grp ASC, u.name ASC
";
    return DB::select(DB::raw($sql));
  }

  public function getlogonid(Request $request, $lid_id)
  {
    $getlog = $request->all();
    //  Debugbar::info($insertItem);

    if ($getlog['api_key'] != env('API_KEY')) return response('Key incorect', 403);
    return Log::where('lid_id', $lid_id)->get();
  }


  public function add(Request $request)
  {

    $log = new Log;

    $log->tel = $request->tel;
    $log->lid_id = $request->lid_id;
    $log->user_id = $request->user_id;
    if (isset($request->status_id)) {
      $log->status_id = $request->status_id;
    }
    if (isset($request->text)) {
      $log->text = $request->text;
      $a_lid = [
        'text' => $request->text,
        'updated_at' => Now()
      ];
      DB::table('lids')->where('id', $log->lid_id)->update($a_lid);
    }

    $log->save();
  }

  public function tellog(Request $request)
  {
    $user_id = session()->get('user_id');
    $role_id = User::where('id', $user_id)->value('role_id');
    $logs =  DB::table('logs')
      ->select('users.fio', 'statuses.name', 'statuses.color', 'logs.text', 'logs.created_at') //,'logs.tel'
      ->leftJoin('statuses', 'logs.status_id', '=', 'statuses.id')
      ->leftJoin('users', 'logs.user_id', '=', 'users.id')
      ->where('logs.lid_id', $request->lid_id)
      ->when($role_id == 3, function ($query) {
        $query->where('logs.last_log', 0);
      })
      ->reorder('logs.created_at', 'desc')
      ->get();
    return $logs;
  }

  public function userlog(Request $request)
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
  public function StasusesOfId($id)
  {
    $statuses = DB::select(DB::raw("SELECT CAST(l.created_at AS DATE) cdate, s.`color`,s.`name`,u.`name` uname FROM `logs` l LEFT JOIN `users` u ON (u.`id` = l.`user_id`)  LEFT JOIN `statuses` s ON (s.`id` = l.`status_id` ) WHERE l.`lid_id` = '$id' AND l.`status_id` > 0 ORDER BY l.`created_at` DESC"));
    return  $statuses;
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
