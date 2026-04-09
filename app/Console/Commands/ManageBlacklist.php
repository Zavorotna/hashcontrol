<?php

namespace App\Console\Commands;

use App\Models\BlacklistedDevice;
use Illuminate\Console\Command;

class ManageBlacklist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blacklist {action} {device_id?} {--reason=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage blacklisted devices: list, add, remove';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                $this->listBlacklisted();
                break;
            case 'add':
                $this->addToBlacklist();
                break;
            case 'remove':
                $this->removeFromBlacklist();
                break;
            default:
                $this->error('Invalid action. Use list, add, or remove.');
        }
    }

    private function listBlacklisted()
    {
        $devices = BlacklistedDevice::all();
        if ($devices->isEmpty()) {
            $this->info('No blacklisted devices.');
            return;
        }

        $this->table(['ID', 'Reader ID', 'Reason', 'Created At'], $devices->map(function ($device) {
            return [
                $device->id,
                $device->device_id,
                $device->reason,
                $device->created_at,
            ];
        }));
    }

    private function addToBlacklist()
    {
        $readerId = $this->argument('device_id');
        if (!$readerId) {
            $this->error('Reader ID is required for add action.');
            return;
        }

        $reason = $this->option('reason') ?? 'Manual blacklist';

        BlacklistedDevice::firstOrCreate(['device_id' => $readerId], ['reason' => $reason]);

        $this->info("Device {$readerId} added to blacklist.");
    }

    private function removeFromBlacklist()
    {
        $readerId = $this->argument('device_id');
        if (!$readerId) {
            $this->error('Reader ID is required for remove action.');
            return;
        }

        $device = BlacklistedDevice::where('device_id', $readerId)->first();
        if (!$device) {
            $this->error("Device {$readerId} not found in blacklist.");
            return;
        }

        $device->delete();
        $this->info("Device {$readerId} removed from blacklist.");
    }
}
