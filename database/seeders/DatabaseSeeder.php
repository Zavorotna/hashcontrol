<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Store;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Користувачі ───────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Адміністратор', 'password' => Hash::make('password'), 'role' => 'admin']
        );

        User::firstOrCreate(
            ['email' => 'user@example.com'],
            ['name' => 'Менеджер', 'password' => Hash::make('password'), 'role' => 'user']
        );

        // ── Беремо всі топіки з mqtt_messages ─────────────────────
        $topicsInMessages = DB::table('mqtt_messages')
            ->select('topic')
            ->distinct()
            ->get();

        foreach ($topicsInMessages as $t) {
            $topicSlug = $t->topic;

            $topic = Topic::updateOrCreate(
                ['slug' => $topicSlug],
                [
                    'name' => ucfirst($topicSlug),
                    'description' => "Топік {$topicSlug} з mqtt_messages",
                    'has_generator' => DB::table('mqtt_messages')->where('topic', $topicSlug)->where('device_id', 'like', 'GEN_%')->exists(),
                    'fuel_rate_per_hour' => 5.0, // можеш підставити свої значення
                    'is_active' => true,
                ]
            );

            // ── Беремо всі унікальні device_id цього топіка ─────────
            $devicesInTopic = DB::table('mqtt_messages')
                ->where('topic', $topicSlug)
                ->select('device_id')
                ->distinct()
                ->get();

            foreach ($devicesInTopic as $d) {
                $deviceId = $d->device_id;

                if (str_starts_with($deviceId, 'GEN_')) {
                    // Генератор
                    Device::updateOrCreate(
                        ['mqtt_device_id' => $deviceId],
                        [
                            'topic_id' => $topic->id,
                            'store_id' => null,
                            'type' => 'generator',
                            'name' => "Генератор {$deviceId}",
                            'is_active' => true,
                        ]
                    );
                } else {
                    // Магазин / рідери
                    // Визначимо mqtt_device_id магазину: прибираємо _R1/_R2
                    $storeMqttId = preg_replace('/_R[12]$/', '', $deviceId);

                    $store = Store::updateOrCreate(
                        ['mqtt_device_id' => $storeMqttId],
                        [
                            'topic_id' => $topic->id,
                            'name' => "Магазин {$storeMqttId}",
                            'employee_name' => null,
                            'location' => null,
                            'is_active' => true,
                        ]
                    );

                    // Тепер створюємо сам пристрій
                    Device::updateOrCreate(
                        ['mqtt_device_id' => $deviceId],
                        [
                            'topic_id' => $topic->id,
                            'store_id' => $store->id,
                            'type' => str_contains($deviceId, '_R1') ? 'reader_open' : 'reader_close',
                            'name' => "Рідер {$deviceId}",
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        $this->command->info('✅ Seeder завершено на основі mqtt_messages!');
    }
}