<?php

namespace App\Console\Commands;

use App\Models\DeviceLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveLogs extends Command
{
    protected $signature   = 'logs:archive {--days=30 : Keep this many days in the main table}';
    protected $description = 'Move device logs older than N days to the archive table';

    public function handle(): void
    {
        $days     = (int) $this->option('days');
        $cutoff   = now()->subDays($days);
        $archived = 0;

        DeviceLog::where('logged_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(500, function ($chunk) use (&$archived) {
                DB::table('device_logs_archive')->insertOrIgnore(
                    $chunk->map(fn($r) => [
                        'id'         => $r->id,
                        'device_id'  => $r->device_id,
                        'action_id'  => $r->action_id,
                        'data'       => $r->data,
                        'logged_at'  => $r->logged_at,
                        'created_at' => $r->created_at,
                        'updated_at' => $r->updated_at,
                    ])->toArray()
                );
                DeviceLog::whereIn('id', $chunk->pluck('id'))->delete();
                $archived += $chunk->count();
            });

        $this->info("Archived {$archived} log rows (cutoff: {$cutoff}).");
    }
}
