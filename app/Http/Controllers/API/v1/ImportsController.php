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
    return Import::select(['imports.*', DB::raw('(SELECT `name` FROM `providers` WHERE `id` = `provider_id`) AS provider'), DB::raw('(SELECT `name` FROM `users` WHERE `id` = `user_id`) AS user'), DB::raw('(SELECT `group_id` FROM `users` WHERE `id` = `user_id`) AS group_id')])->orderByDesc('end')->get();
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

    $sql = "SELECT bl.`id`, bl.`address`, bl.`summ`, bl.`office_id`, bl.`other`, bl.`trx_count`, l.`id` lid_id, l.`name`, l.`created_at`, l.`tel`, l.`email`, l.`provider_id`, l.`status_id`, s.`name` s_name, s.`color` s_color, p.`name` p_name, (SELECT IF (SUM(d.`depozit`), SUM(d.`depozit`), '') FROM `depozits` d WHERE l.`id` = d.`lid_id` AND d.`created_at` > '" . $dateFrom . " 00:00:00' AND d.`created_at` < '" . $dateTo . " 23:59:59') depozit FROM `btc_list` bl INNER JOIN `lids` l ON (bl.`lid_id` = l.`id`) INNER JOIN `providers` p ON (l.`provider_id` = p.`id`) INNER JOIN `statuses` s ON (l.`status_id` = s.`id`) WHERE " . $where . "`other` REGEXP '[^|].*' ORDER BY l.`id`";
    $rows = DB::select(DB::raw($sql));
    //array dates (from to)
    $a_list_date = $this->date_range($dateFrom, $dateTo);
    $res['data'] = $data =  [];
    $res['providers'] = [];
    $res['statuses'] = [];
    $res['result'] = "success";

    $compareLidId = $sum_lid = $ia =  0;
    if ($rows) {
      //foreach row
      foreach ($rows as $lid) {
        $a_date_sum = $a_intersect = [];
        $sum_dat = 0;
        $other = $lid->other;
        $a_date_sum[0] = explode('|', $other);
        $max = count($a_date_sum[0]);
        for (
          $i = 1;
          $i < $max;
          $i += 2
        ) {
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
          // group same lids
          if ($compareLidId == $lid->lid_id) {
            $data[$ia - 1]['summ']  += $lid->summ;
            $data[$ia - 1]['sum_dat']  += $sum_dat;
            continue;
          } else {
            $compareLidId = $lid->lid_id;
            $data[$ia++] = [
              'id' => $lid->id,
              'name' => $lid->name,
              'email' => $lid->email,
              'tel' => $lid->tel,
              'created_at' => $lid->created_at,
              'address' => $lid->address,
              'lid_id' => $lid->lid_id,
              'status_id' =>  $lid->status_id,
              's_name' =>  $lid->s_name,
              'summ' => $lid->summ,
              'office_id' => $lid->office_id,
              'provider_id' => $lid->provider_id,
              'p_name' => $lid->p_name,
              'sum_dat' => $sum_dat,
              'depozit' => (int) $lid->depozit
            ];
          }
        }
        //next row
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
    $where = $office_id > 0 ? "  `office_id` = " . $office_id . " AND " : "";

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

  private function parseIni($filename)
  {
    // $file = file_get_contents($filename);
    $file = Storage::disk('public')->get($filename);
    // $file = Storage::get($filename);

    $file = mb_convert_encoding($file, "UTF-8", "UTF-16LE");
    $lines = explode("\n", $file);
    $rows = [];
    $read = false;
    if (!empty($lines)) {
      // If lines exists
      foreach ($lines as $line) {
        // Skipping the empty line and Comment line
        if ((empty(trim($line))) || (preg_match('/^#/', $line) > 0))
          continue;

        // Output Line Content
        $line = trim($line);
        if (strpos($line, "Calls") === 1) {
          $read = true;
          continue;
        }
        if (strpos($line, "[") === 0) {
          $read = false;
          continue;
        }
        if ($read === true) {
          $a1 = explode('=', $line);
          $a2 = explode(';', $a1[1]);
          // print_r($a2);
          $da = date('Y-m-d H:i:s', $a2[3]);
          // echo "Тел:{$a2[0]} time:{$da} sek:{$a2[4]} status:{$a2[5]}<br>\n";
          $rows[] = $a2;
        }
      }
    }
    return $rows;
  }
  private function getLeadOnTel($tel)
  {

    $lid = Lid::select('id', 'tel', 'user_id', 'updated_at')->where('tel', $tel)->get()->toArray();
    if ($lid) {
      return $lid[0]['tel'] . ' ' . date('Y-m-d H:i:s', strtotime($lid[0]['updated_at'])) . ' ' . $lid[0]['user_id'];
    }
    return $tel;
  }

  public function importCalls()
  {
    $directory = 'copy';
    // $files = Storage::disk('public')->allFiles($directory);
    $files = Storage::disk('public')->files($directory);

    foreach ($files as  $file) {
      $a_row = $this->parseIni($file);
      foreach ($a_row as $row) {
        // $row[0] - tel
        // $row[3] - date
        // $row[4] - sec
        // $row[5] - status
        print ($this->getLeadOnTel($row[0])) . ' ' . $row[4] . 'c ' . $row[5] . '<br>';
      }
      //return $a_row;
    }
  }
}
