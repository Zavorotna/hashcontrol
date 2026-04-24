<?php

namespace App\Console\Commands;

use App\Models\Action;
use App\Models\BlacklistedDevice;
use App\Models\Device;
use App\Models\DeviceLog;
use App\Models\MqttMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Facades\MQTT;

class MqttTest extends Command
{
    protected $signature   = 'mqtt:test';
    protected $description = 'Subscribe to MQTT topics and process messages';

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

            // JSON validation
            $data = json_decode($message, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                $this->error("JSON decode error: " . json_last_error_msg() . " | raw: {$message}");
                return;
            }

            if (!isset($data['id'], $data['data'])) {
                $this->error("Missing required fields (id, data): {$message}");
                return;
            }

            $deviceId = (string) $data['id'];
            $dataVal  = (string) $data['data'];
            $actVal   = isset($data['act']) ? (string) $data['act'] : null;

            // Check if device is blacklisted
            if (BlacklistedDevice::where('device_id', $deviceId)->whereNull('deleted_at')->exists()) {
                $this->warn("Device {$deviceId} is blacklisted, skipping");
                return;
            }

            try {
                DB::transaction(function () use ($topic, $message, $deviceId, $dataVal, $actVal) {
                    // Register action only if present in payload
                    $action = null;
                    if ($actVal !== null) {
                        $action = Action::firstOrCreate(
                            ['name' => $actVal],
                            ['title' => $actVal, 'description' => 'Auto registered']
                        );
                    }

                    // Save or update message (one per device — restore if soft-deleted)
                    $mqttMsg = MqttMessage::withTrashed()->where('device_id', $deviceId)->first();
                    if ($mqttMsg) {
                        $mqttMsg->restore();
                        $mqttMsg->update([
                            'topic'   => $topic,
                            'payload' => $message,
                            'action'  => $actVal,
                            'data'    => $dataVal,
                        ]);
                    } else {
                        MqttMessage::create([
                            'device_id' => $deviceId,
                            'topic'     => $topic,
                            'payload'   => $message,
                            'action'    => $actVal,
                            'data'      => $dataVal,
                        ]);
                    }

                    // If device is registered — log the event
                    $device = Device::where('device_id', $deviceId)->first();
                    if ($device) {

                        DeviceLog::create([
                            'device_id' => $device->id,
                            'action_id' => $action?->id,
                            'data'      => $dataVal,
                            'logged_at' => now(),
                        ]);
                    }
                });
            } catch (\Exception $e) {
                $this->error("DB error for device {$deviceId}: " . $e->getMessage());
                Log::error('mqtt:handler', ['device' => $deviceId, 'error' => $e->getMessage()]);
                return;
            }

            $actionInfo = $actVal ? " act={$actVal}" : '';
            $this->info("OK device={$deviceId}{$actionInfo} data={$dataVal}");

        }, 0);

            $this->info('Слухаємо... (Ctrl+C щоб зупинити)');
            $mqtt->loop(true);

        } catch (\Exception $e) {
            $this->error('Помилка: ' . $e->getMessage());
        }
    }
}