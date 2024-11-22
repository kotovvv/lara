<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;


class Kernel extends ConsoleKernel
{
  /**
   * The Artisan commands provided by your application.
   *
   * @var array
   */
  protected $commands = [
    Commands\load_calls::class,
    Commands\ImportCallc::class,
  ];

  /**
   * Define the application's command schedule.
   *
   * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
   * @return void
   */
  protected function schedule(Schedule $schedule)
  {
    $schedule->command('loadcalls:two')->everyTenMinutes()->between('10:00', '20:00')->appendOutputTo(storage_path('logs/loadcalls.log'));
    //$schedule->command('import:callc')->everyThirtyMinutes()->between('10:00', '20:00')->appendOutputTo(storage_path('logs/importcallc.log'));
  }

  /**
   * Register the commands for the application.
   *
   * @return void
   */
  protected function commands()
  {
    $this->load(__DIR__ . '/Commands');

    require base_path('routes/console.php');
  }
}