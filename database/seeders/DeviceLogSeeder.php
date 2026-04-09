<?php

namespace Database\Seeders;

use App\Models\Action;
use App\Models\Device;
use App\Models\DeviceLog;
use Illuminate\Database\Seeder;

class DeviceLogSeeder extends Seeder
{
    public function run(): void
    {
        $device = Device::where('device_id', '123')->firstOrFail();

        $action = Action::firstOrCreate(
            ['name' => '1'],
            ['description' => 'Авто']
        );

        $logs = [
            ['data' => '101', 'hours_ago' => 1],
            ['data' => '101', 'hours_ago' => 9],
            ['data' => '102', 'hours_ago' => 2],
            ['data' => '102', 'hours_ago' => 26],
            ['data' => 'gen_backup', 'hours_ago' => 3],
            ['data' => 'gen_backup', 'hours_ago' => 51],
            ['data' => '201', 'hours_ago' => 4], 
        ];

        foreach ($logs as $log) {
            DeviceLog::create([
                'device_id' => $device->id,
                'action_id' => $action->id,
                'data'      => $log['data'],
                'logged_at' => now()->subHours($log['hours_ago']),
            ]);
        }
    }
}