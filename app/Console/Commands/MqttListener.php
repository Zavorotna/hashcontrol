<?php

namespace App\Console\Commands;

use App\Models\MqttMessage;
use App\Models\Topic;
use App\Services\MqttMessageProcessor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class MqttListener extends Command
{
    protected $signature   = 'mqtt:listen';
    protected $description = 'Прослуховує всі MQTT топіки і обробляє повідомлення';

    public function handle(MqttMessageProcessor $processor): void
    {
        $this->info('Завантажую активні топіки...');

        // Отримуємо всі активні топіки з БД
        $topics = Topic::where('is_active', true)->get();

        if ($topics->isEmpty()) {
            $this->error('Немає активних топіків у БД. Додайте топіки через адмін-панель.');
            return;
        }

        $host = config('mqtt-client.connections.default.host');
        $this->info("Підключаємось до {$host}...");

        try {
            $mqtt = MQTT::connection();
            $this->info('Підключено!');

            // Підписуємось на кожен активний топік
            foreach ($topics as $topic) {
                $mqtt->subscribe($topic->slug, function (string $mqttTopic, string $payload) use ($processor) {
                    $this->handleMessage($mqttTopic, $payload, $processor);
                }, 0);

                $this->info("  ✓ Підписано на: {$topic->slug} ({$topic->name})");
            }

            $this->info('');
            $this->info('Слухаємо повідомлення... (Ctrl+C щоб зупинити)');
            $this->info('─────────────────────────────────────────────────');

            $mqtt->loop(true);

        } catch (\Exception $e) {
            $this->error('Помилка MQTT: ' . $e->getMessage());
        }
    }

    private function handleMessage(string $topic, string $payload, MqttMessageProcessor $processor): void
    {
        $now = Carbon::now();

        // Парсимо payload: очікуємо "EVENT DEVICE_ID"
        // Наприклад: "OPEN STORE_01_R1"
        $parts    = explode(' ', trim($payload));
        $deviceId = $parts[1] ?? $parts[0] ?? null; // device_id може бути одразу без префіксу

        $this->line("[{$now->format('H:i:s')}] [{$topic}] >>> {$payload}");

        // Зберігаємо сире повідомлення
        $message = MqttMessage::create([
            'topic'       => $topic,
            'device_id'   => $deviceId,
            'payload'     => $payload,
            'processed'   => false,
            'received_at' => $now,
        ]);

        // Опрацьовуємо
        $processor->process($message);

        if ($message->fresh()->error) {
            $this->warn("  ⚠ {$message->fresh()->error}");
        } else {
            $this->info("  ✓ Опрацьовано (device: {$deviceId})");
        }
    }
}
