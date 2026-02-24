<?php

namespace App\Console\Commands;

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

           $mqtt->subscribe('aquapark', function ($topic, $message) {

            $parts = explode(' ', $message);
            $deviceId = $parts[1] ?? null;

            MqttMessage::create([
                'topic' => $topic,
                'device_id' => $deviceId,
                'payload' => $message,
            ]);

            $this->info("Saved device {$deviceId} from topic {$topic}");

        }, 0);

            $this->info('Слухаємо... (Ctrl+C щоб зупинити)');
            $mqtt->loop(true);

        } catch (\Exception $e) {
            $this->error('Помилка: ' . $e->getMessage());
        }
    }
}