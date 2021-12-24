<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;

class load_calls extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'loadcalls:minute';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Parse file with calls and write in log on phone namber';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    $file = './calls/MicroSIP.ini';
    if (!file_exists($file)) exit;
    $a_calls = $this->parse_ini($file)['Calls'];

    // $a_calls = array_reverse($a_calls);
    $a_all = [];

    $last_writed = DB::table('logs')->selectRaw('CAST(DATE_SUB(`created_at`, INTERVAL 1 DAY) AS DATE) as lastdate')->whereNotNull('cols')->latest()->limit(1)->first();
    if($last_writed) $last_writed = $last_writed->lastdate;

    foreach ($a_calls as $param) {
      $date = date('Y-m-d', $param[3]);
      if($last_writed){
        //get only new
        if($date <= $last_writed) continue;
      }
      $tel = $param[0];
      $duration = $param[4];
      if (!isset($a_all[$tel][$date]['col'])) $a_all[$tel][$date]['col'] = 0;
      if (!isset($a_all[$tel][$date]['duration'])) $a_all[$tel][$date]['duration'] = 0;
      $a_all[$tel][$date]['col'] += 1;
      $a_all[$tel][$date]['duration'] += $duration;
    }

    $a_calls = [];
//  echo count($a_all).PHP_EOL;
$all= count($a_all);
$i = $all;
    foreach ($a_all as $tel => $params) {
      foreach ($params as $date => $value) {
DB::update(DB::Raw("UPDATE `logs` SET `cols` = '".$value['col']."', `duration`='".$value['duration']."' WHERE `tel` = '".$tel."' AND CAST( `created_at` AS DATE) = '".$date."' ORDER BY id DESC LIMIT 1"));
        //echo "tel:" . $tel . " date:" . $date . " count:" . $value['col'] . ":" . $value['duration'] . " <br>";
        $i--;
      }
    }
    if($i == 0) unlink($file);
    return 0;
  }


  protected function parse_ini($filepath)
  {
    $ini = file($filepath);
    if (count($ini) == 0) {
      return array();
    }
    $sections = array();
    $values = array();
    $globals = array();

    $i = 0;
    foreach ($ini as $line) {
      $line = trim($line);
      // Comments
      if ($line == '' || $line[0] == ';') {
        continue;
      }
      // Sections
      if ($line[0] == '[') {
        $sections[] = substr($line, 1, -1);
        $i++;
        continue;
      }
      // Key-value pair
      list($key, $value) = explode('=', $line, 2);
      $key = trim($key);
      $value = trim($value);

      if (strpos($value, ";") !== false)
        $value = explode(";", $value);

      if ($i == 0) {
        // Array values
        if (substr($line, -1, 2) == '[]') {
          $globals[$key][] = $value;
        } else {
          $globals[$key] = $value;
        }
      } else {
        // Array values
        if (substr($line, -1, 2) == '[]') {
          $values[$i - 1][$key][] = $value;
        } else {
          $values[$i - 1][$key] = $value;
        }
      }
    }
    for ($j = 0; $j < $i; $j++) {
      $result[$sections[$j]] = $values[$j];
    }
    return $result + $globals;
  }
}
