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
    $req = $request->all();
    $office_id = session()->get('office_id');
    $user_id = session()->get('user_id');
    $caller = $req['q'];
    $called = $req['w'];
    $duration = round($req['z'] / 1000);
    $reason = $req['x'];
    $date = Carbon::now();

    //get time last call
    $sql = "SELECT `timecall` FROM `calls` WHERE `user_id` = $user_id  AND date(`timecall`) = CURDATE() AND `timecall` < $date ORDER BY id DESC LIMIT 1";
    $lastcall = DB::select(DB::raw($sql));
    if ($lastcall) {
      $lastcall = Carbon::parse($lastcall[0]->timecall);
    } else {
      $lastcall = Carbon::now()->hour('09')->minute('00')->second('00');
    }
    //different between calls
    $cur = $date->subSeconds($duration);
    $diff = $cur->diffInSeconds($lastcall);

    $sql = "INSERT INTO `calls` (`user_id`,`user_tel`,`tel`,`duration`,`timecall`,`status`,`office_id`,`pausa`) VALUES ($user_id,$caller,$called,$duration,NOW(),'$reason',$office_id,$diff)";
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
    $office = "office_id = $office_id AND";
    if ($office_id == 0) {
      $office = "1 AND";
    }

    $dateFrom = $req['dateFrom'] . ' 00:00:00';
    $dateTo = $req['dateTo'] . ' 23:59:59';
    //====================


    // $sql = "SELECT `id`,`user_id`,`timecall`,`duration` FROM `calls` WHERE DATE(`timecall`) = '2023-06-28'";
    // $list = DB::select(DB::raw($sql));
    // foreach ($list as $user) {
    //   $sql = "SELECT `timecall` FROM `calls` WHERE `user_id` = $user->user_id  AND `timecall` < '$user->timecall' AND DATE(`timecall`) = '2023-06-28' ORDER BY id DESC LIMIT 1";
    //   $lastcall = DB::select(DB::raw($sql));
    //   if ($lastcall) {
    //     $lastcall = Carbon::parse($lastcall[0]->timecall);
    //   } else {
    //     $lastcall = Carbon::create('2023-06-28')->hour('09')->minute('00');
    //   }
    //   //different berween calls
    //   $cur = Carbon::parse($user->timecall)->subSeconds($user->duration);
    //   $diff = $cur->diffInSeconds($lastcall);
    //   $sql = "UPDATE `calls` SET `pausa` = $diff WHERE id = $user->id";
    //   DB::select(DB::raw($sql));
    // }

    //===================================
    $sql = "SELECT
    u.id
    ,u.name
,(SELECT COUNT(*) FROM `calls` c WHERE c.user_id = u.id AND (c.timecall BETWEEN '$dateFrom' AND '$dateTo')) cnt
,(SELECT SEC_TO_TIME(SUM(`duration`)) FROM `calls` c WHERE c.user_id = u.id AND (c.timecall BETWEEN '$dateFrom' AND '$dateTo')) dur
,(SELECT COUNT(*) FROM `calls` c WHERE `duration`> 120 AND c.user_id = u.id AND (c.timecall BETWEEN '$dateFrom' AND '$dateTo')) mr2
,(SELECT SEC_TO_TIME(round(AVG(`duration`))) FROM `calls` c WHERE  c.user_id = u.id AND (c.timecall BETWEEN '$dateFrom' AND '$dateTo')) avg
,(SELECT SEC_TO_TIME(SUM(`pausa`)) FROM `calls` c WHERE c.user_id = u.id AND (c.timecall BETWEEN '$dateFrom' AND '$dateTo')) pausa
   FROM
   `users` u
WHERE u.id IN (SELECT `user_id` FROM `calls` WHERE $office `timecall` BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `user_id`)";
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

    $logs =  DB::table('logs')
      ->select('users.fio', 'statuses.name', 'statuses.color', 'logs.text', 'logs.created_at') //,'logs.tel'
      ->leftJoin('statuses', 'logs.status_id', '=', 'statuses.id')
      ->join('users', 'logs.user_id', '=', 'users.id')
      ->where('logs.lid_id', $request->lid_id)
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
