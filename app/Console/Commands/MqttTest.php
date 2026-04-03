<?php

namespace App\Console\Commands;

use App\Models\Action;
use App\Models\BlacklistedDevice;
use App\Models\Device;
use App\Models\DeviceLog;
use App\Models\MqttMessage;
use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class MqttTest extends Command
{
    protected $signature   = 'mqtt:test';
    protected $description = 'Тест підключення до MQTT';

    public function handle(): void
    {
        $this->info('Підключаємось до ' . config('mqtt-client.connections.default.host') . '...');

        try {
            $mqtt = MQTT::connection();
            $this->info('Підключення успішне!');

            // $mqtt->subscribe('aquapark', function (string $topic, string $message) {
            //     $this->info("[{$topic}] >>> {$message}");
            // }, 0);


            // $mqtt->subscribe('#', function (string $topic, string $message) {
            //     $this->info("[{$topic}] >>> {$message}");
            // }, 0);

            // $mqtt->subscribe('aquapark/arduino', function ($topic, $message) {
            //     $this->info("Arduino >>> {$message}");
            // }, 0);

           $mqtt->subscribe('hashcontrol', function ($topic, $message) {

            $data = json_decode($message, true);
            if (!$data || !isset($data['id'], $data['data'])) {
                $this->error("Invalid JSON or missing keys: {$message}");
                return;
            }

            // Check if device is blacklisted
            if (BlacklistedDevice::where('device_id', $data['id'])->exists()) {
                $this->warn("Device {$data['id']} is blacklisted, skipping");
                return;
            }

            // Register action only if present in payload
            $action = null;
            if (isset($data['act'])) {
                $action = Action::firstOrCreate(
                    ['name' => (string)$data['act']],
                    ['description' => 'Auto registered action']
                );
            }

            // Save or update message (only one per device)
            MqttMessage::updateOrCreate(
                ['device_id' => $data['id']],
                [
                    'topic'   => $topic,
                    'payload' => $message,
                    'action'  => $data['act'] ?? null,
                    'data'    => $data['data'],
                ]
            );

            // If device is registered, log the event
            $device = Device::where('device_id', $data['id'])->first();
            if ($device) {
                DeviceLog::create([
                    'device_id' => $device->id,
                    'action_id' => $action?->id,
                    'data'      => $data['data'],
                    'logged_at' => now(),
                ]);
            }

            $actionInfo = $action ? " action {$data['act']}" : '';
            $this->info("Processed device {$data['id']}{$actionInfo} from topic {$topic}");

        }, 0);

            $this->info('Слухаємо... (Ctrl+C щоб зупинити)');
            $mqtt->loop(true);

        } catch (\Exception $e) {
            $this->error('Помилка: ' . $e->getMessage());
        }
    }
}