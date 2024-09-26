<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Lid;

class ImportCallc extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'import:callc';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Command callc NEW, CallBack, Deposit, howmany for imports';

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
    $imports = DB::table('imports')->where('callc', 1)
      ->get();

    // dd(vsprintf(str_replace(array('?'), array('\'%s\''), $imports->toSql()), $imports->getBindings()));

    foreach ($imports as $import) {
      $a_hm = (object) [];
      if ($import->message != '') {
        $a_hm = DB::selectOne('SELECT SUM(status_id = 8) hmnew,SUM(status_id = 9) hmcb,SUM(status_id = 10) hmdp, SUM(status_id = 20) hmpnd, SUM(status_id = 32) hmpot, COUNT(*) hm FROM lids l WHERE l.`load_mess` = "' . $import->message . '"');

        $a_hm_json = json_encode(DB::select('SELECT if(isnull(office_id),0,office_id) office_id, SUM(status_id = 8) hmnew,SUM(status_id = 9) hmcb,SUM(status_id = 10) hmdp, SUM(status_id = 20) hmpnd, SUM(status_id = 32) hmpot, COUNT(*) hm FROM lids l WHERE l.`load_mess` = "' . $import->message . '" GROUP BY office_id WITH ROLLUP'));
        $a_hm->hm_json = $a_hm_json;

        // get offices users
        $a_office_ids = json_encode(Lid::where('load_mess',  $import->message)
          ->whereDate('lids.created_at', date('Y-m-d', strtotime($import->start)))->where('office_id', '!=', 0)->groupBy('office_id')->orderBy('id', 'ASC')->pluck('office_id')->toArray());
        DB::table('imports')->where('id', $import->id)->update(['office_ids' => $a_office_ids]);
      }

      $a_hm->callc = 0;
      $a_hm->updated_at = date('Y-m-d H:m:s');
      DB::table('imports')->where('id', $import->id)->update((array)$a_hm);
    }

    $imports_provider = DB::table('imports_provider')->where('callc', 1)->get();
    foreach ($imports_provider as $import) {
      $a_hm = (object) [];
      $lid_ids = DB::table('imported_leads')->where('api_key_id', $import->provider_id)->whereDate('upload_time', $import->date)->where('geo', $import->geo)->pluck('lead_id')->toArray();
      if (count($lid_ids) > 0) {
        $a_hm = DB::selectOne('SELECT SUM(status_id = 8)hmnew,SUM(status_id = 9)hmcb,SUM(status_id = 10)hmdp,SUM(status_id = 20) hmpnd, SUM(status_id = 32) hmpot, COUNT(*)hm FROM lids l WHERE l.`id` IN (' . implode(',', $lid_ids) . ')');

        if ($a_hm) {
          $a_hm_json = json_encode(DB::select('SELECT if(isnull(office_id),0,office_id) office_id, SUM(status_id = 8) hmnew,SUM(status_id = 9) hmcb,SUM(status_id = 10) hmdp, SUM(status_id = 20) hmpnd, SUM(status_id = 32) hmpot, COUNT(*) hm FROM lids l WHERE l.`id` IN (' . implode(',', $lid_ids) . ') GROUP BY office_id WITH ROLLUP'));
          $a_hm->hm_json = $a_hm_json;
          $a_hm->callc = 0;
          $a_hm->updated_at = date('Y-m-d H:m:s');

          DB::table('imports_provider')->where('id', $import->id)->update((array)$a_hm);
        }
      }
    }

    return 0;
  }
}