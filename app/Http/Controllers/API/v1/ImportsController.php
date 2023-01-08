<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Import;
use DB;
use Debugbar;

class ImportsController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
    return Import::select(['imports.*', DB::raw('(SELECT `name` FROM `providers` WHERE `id` = `provider_id`) AS provider'), DB::raw('(SELECT `name` FROM `users` WHERE `id` = `user_id`) AS user')])->orderByDesc('end')->get();
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
    $where = $office_id > 0 ? "  bl.`office_id` = " . $office_id . " AND " : "";
    $dateFrom = $req['datefrom'];
    $dateTo = $req['dateto'];
    $onlynew = $req['onlynew'];

    $sql = "SELECT bl.`id`, bl.`address`, bl.`summ`,bl.`office_id`, bl.`other`, bl.`trx_count`, l.`id` lid_id ,l.`name`,l.`tel`,l.`email`, l.`provider_id`, p.`name` p_name, IF(d.`depozit`,d.`depozit`,0) depozit FROM `btc_list` bl INNER JOIN `lids` l ON (bl.`lid_id` = l.`id` ) INNER JOIN `providers` p ON (l.`provider_id` = p.`id` ) LEFT JOIN `depozits` d ON (l.`id` = d.`lid_id` ) WHERE " . $where . " `other` REGEXP '20??-'";
    $rows = DB::select(DB::raw($sql));
    //array dates (from to)
    $a_list_date = $this->date_range($dateFrom, $dateTo);
    $res['data'] = [];
    $res['providers'] = [];
    $res['result'] = "success";

    if ($rows) {
      //foreach row
      foreach ($rows as $lid) {
        $a_date_sum = $a_intersect = [];
        $sum_dat = 0;
        $other = $lid->other;
        preg_match_all('/(\d{4}-\d{2}-\d{2}).[^-]*Z|(\d[^|]*)/', $other, $a_date_sum);

        $a_intersect = array_intersect($a_date_sum[1], $a_list_date);
        if ($onlynew && count($a_date_sum[0]) != count($a_intersect)*2) {
          continue;
        }
        if ($a_intersect) {
          foreach ($a_intersect as $key => $date) {
            if ($this->between_dates($date, $dateFrom, $dateTo)) {
              $sum_dat += $a_date_sum[0][$key + 1];
            }
            $res['providers'][] = ['id' => $lid->provider_id, 'name' => $lid->p_name];
          }
          //add sum_dat to row
          $res['data'][] = [
            'id' => $lid->id,
            'name' => $lid->name,
            'email' => $lid->email,
            'tel' => $lid->tel,
            'address' => $lid->address,
            'lid_id' => $lid->lid_id,
            'summ' => $lid->summ,
            'office_id' => $lid->office_id,
            'provider_id' => $lid->provider_id,
            'p_name' => $lid->p_name,
            'sum_dat' => $sum_dat,
            'depozit' => $lid->depozit
          ];
        }
        //next row
      }
    }
    return $res;
  }

  public function getBTCsOnDate(Request $request)
  {
    $req = $request->all();
    $office_id = session()->get('office_id');
    $where = $office_id > 0 ? "  `office_id` = " . $office_id . " AND " : "";

    $date = [date('Y-m-d', strtotime($req['datefrom'])) . ' ' . date("H:i:s", mktime(0, 0, 0)), date('Y-m-d', strtotime($req['dateto'])) . ' ' . date("H:i:s", mktime(23, 59, 59))];

    $sql = "SELECT bl.`address`, bl.`summ`, bl.`trx_count`, bl.`date_time`, l.`id` lid_id, u.`fio` FROM `btc_list` bl INNER JOIN `lids` l ON (bl.`lid_id` = l.`id` ) INNER JOIN `users` u ON (bl.`user_id` = u.`id` ) WHERE " . $where . " bl.`date_time` >= '" . $date[0] . "' AND bl.`date_time` <= '" . $date[1] . "'";

    return DB::select(DB::raw($sql));
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
    //  Debugbar::info($request->all());
    $a_import = $request->all();
    $date = date("Y-m-d H:i:s");
    // array_merge($a_import,['created_at'=>$date,'updated_at'=>$date ]);
    DB::table('imports')->insert($a_import);
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
