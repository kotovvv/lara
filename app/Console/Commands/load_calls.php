<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lid;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

//$ php artisan loadcalls:two
//$ php artisan schedule:list
//* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1

class Load_calls extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  // Added optional --debug flag for verbose output
  protected $signature = 'loadcalls:two {--debug : Verbose per-file output}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Parse files with calls and write in table calls on phone namber';

  /**
   * Create a new command instance.
   */
  public function __construct()
  {
    parent::__construct();
  }

  public function handle()
  {
    $start = microtime(true);
    $directory = 'copy';
    $files = Storage::disk('public')->allFiles($directory);
    $curdate = date('Y-m-d');

    $this->info('[' . now() . "] Start loadcalls:two. Files found: " . count($files));
    //Log::info('loadcalls:two start', ['files' => count($files)]);

    if (empty($files)) {
      $this->info('No files in ' . $directory);
      //Log::info('loadcalls:two no files');
      return 0; // nothing to do
    }

    $processedFiles = 0; // number of files iterated
    $affectedRows = 0;   // number of DB upserts
    $skippedRows = 0;    // filtered out rows

    foreach ($files as $file) {
      $processedFiles++;
      if (!Storage::disk('public')->exists($file)) {
        $this->warn("Skip missing: $file");
        Log::warning('loadcalls:two missing file', ['file' => $file]);
        continue;
      }

      // Skip partially written files
      if (Str::endsWith($file, '.filepart')) {
        $this->warn("Skip partial file: $file");
        //Log::info('loadcalls:two skip filepart', ['file' => $file]);
        continue;
      }

      $parts = explode('/', $file);
      $serv = $parts[1] ?? null;
      $user_serv = $parts[2] ?? null;
      if (!$serv || !$user_serv) {
        $this->warn("Bad path structure: $file");
        Log::warning('loadcalls:two bad path', ['file' => $file]);
        continue;
      }

      if ($this->option('debug')) {
        $this->line("Processing file: $file (serv=$serv user_serv=$user_serv)");
      }

      $rows = $this->parseIni($file);
      $fileAffected = 0;

      foreach ($rows as $row) {
        if (!is_array($row)) {
          $skippedRows++;
          continue;
        }
        if (!preg_match('/^[0-9]+$/', $row[0] ?? '')) {
          $skippedRows++;
          continue;
        }
        if (!is_numeric($row[3] ?? null) || $curdate != date('Y-m-d', $row[3])) {
          $skippedRows++;
          continue;
        }

        $user = User::where([
          'serv' => $serv,
          'user_serv' => $user_serv,
          'active' => 1
        ])->first();
        if (!$user) {
          $skippedRows++;
          continue;
        }

        $data = [
          'user_id' => $user->id,
          'office_id' => $user->office_id,
          'tel' => $row[0],
          'timecall' => date('Y-m-d H:i:s', $row[3]),
          'duration' => $row[4] ?? 0,
          'status' => $row[5] ?? null,
        ];
        try {
          DB::table('calls')->updateOrInsert($data);
          $affectedRows++;
          $fileAffected++;
        } catch (\Throwable $th) {
          Log::error('loadcalls:two DB error: ' . $th->getMessage(), ['data' => $data]);
          $this->error('DB error: ' . $th->getMessage());
        }
      }

      $this->info("File processed: $file rows: $fileAffected");
      //Log::info('loadcalls:two file processed', ['file' => $file, 'rows' => $fileAffected]);

      // Remove processed file (with fallback and logging)
      $deleted = Storage::disk('public')->delete($file);
      $realPath = storage_path('app/public/' . $file);
      if (!$deleted && file_exists($realPath)) {
        // Try to adjust permissions then unlink directly
        @chmod($realPath, 0666);
        try {
          @unlink($realPath);
          $deleted = !file_exists($realPath);
        } catch (\Throwable $e) {
          Log::error('loadcalls:two unlink failed', ['file' => $file, 'real' => $realPath, 'err' => $e->getMessage()]);
        }
      }
      if ($deleted) {
        //Log::info('loadcalls:two deleted', ['file' => $file]);
      } else {
        $this->warn("Delete failed: $file");
        $dir = dirname($realPath);
        $perms = file_exists($realPath) ? substr(sprintf('%o', fileperms($realPath)), -4) : null;
        $dperms = is_dir($dir) ? substr(sprintf('%o', fileperms($dir)), -4) : null;
        $owner = function_exists('fileowner') && file_exists($realPath) ? @fileowner($realPath) : null;
        $group = function_exists('filegroup') && file_exists($realPath) ? @filegroup($realPath) : null;
        $ownerName = function_exists('posix_getpwuid') && $owner ? (@posix_getpwuid($owner)['name'] ?? $owner) : $owner;
        $groupName = function_exists('posix_getgrgid') && $group ? (@posix_getgrgid($group)['name'] ?? $group) : $group;
        Log::warning('loadcalls:two delete failed', [
          'file' => $file,
          'real' => $realPath,
          'file_exists' => file_exists($realPath),
          'is_writable_file' => file_exists($realPath) ? @is_writable($realPath) : null,
          'is_writable_dir' => is_dir($dir) ? @is_writable($dir) : null,
          'file_perms' => $perms,
          'dir_perms' => $dperms,
          'owner' => $ownerName,
          'group' => $groupName,
        ]);
      }
    }

    $duration = round(microtime(true) - $start, 3);
    $this->info('[' . now() . "] Finished loadcalls:two. Files: $processedFiles Rows: $affectedRows Skipped: $skippedRows Time: {$duration}s");
    //Log::info('loadcalls:two finished', [
    //   'files' => $processedFiles,
    //   'rows' => $affectedRows,
    //   'skipped' => $skippedRows,
    //   'time' => $duration
    // ]);
    return 0;
  }

  protected function parseIni($filename)
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
          try {
            $a2[3];
          } catch (\Throwable $th) {
            continue;
          }
          // $da = date('Y-m-d H:i:s', $a2[3]);
          // echo "Тел:{$a2[0]} time:{$da} sek:{$a2[4]} status:{$a2[5]}<br>\n";
          $rows[] = $a2;
        }
      }
    }
    return $rows;
  }
  protected function getLeadOnTel($tel, $datecall)
  {

    $lid = Lid::select('id', 'tel', 'user_id', 'updated_at', 'office_id')->where('tel', $tel)->where('created_at', '<', date('Y-m-d H:i:s', $datecall))->orderBy('created_at', 'DESC')->get()->toArray();
    // if ($lid) {
    //   print ($lid[0]['tel'] . ' ' . date('Y-m-d H:i:s', strtotime($lid[0]['updated_at'])) . ' ' . $lid[0]['user_id']) . '<br>';
    // }
    return $lid;
  }
}
