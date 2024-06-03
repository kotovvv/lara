<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
    $imports = DB::table('imports ')->where('callc', 1)->get();
    foreach ($imports as $import) {
    }
    return 0;
  }
}