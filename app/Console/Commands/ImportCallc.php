<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

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
    $imports = DB::table('imports ')->where('callc', 1)->where('updated_at', '>', now()->subHours(3))->get();
    foreach ($imports as $import) {
      $hmnew = $hmcb = $hmdp = $hm = $callc = 0;

      DB::table('imports ')->where('id', $import->id)->update([
        'hmnew'  => $hmnew,
        'hmcb' => $hmcb,
        'hmdp' => $hmdp,
        'hm' => $hm,
        'callc' => $callc,
        'updated_at' => NOW()
      ]);
    }

    $imports_provider = DB::table('imports_provider ')->where('callc', 1)->where('updated_at', '>', now()->subHours(3))->get();
    foreach ($imports_provider as $import) {
      $hmnew = $hmcb = $hmdp = $hm = $callc = 0;

      DB::table('imports_provider ')->where('id', $import->id)->update([
        'hmnew'  => $hmnew,
        'hmcb' => $hmcb,
        'hmdp' => $hmdp,
        'hm' => $hm,
        'callc' => $callc,
        'updated_at' => NOW()
      ]);
    }

    return 0;
  }
}