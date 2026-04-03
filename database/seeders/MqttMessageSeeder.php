<?php

namespace Database\Seeders;

use App\Models\MqttMessage;
use Illuminate\Database\Seeder;

class MqttMessageSeeder extends Seeder
{
    /**
     * Приклади MQTT-повідомлень з числовими id/act/data.
     * Ці записи симулюють незареєстровані пристрої, що чекають на реєстрацію.
     */
    public function run(): void
    {
        $rawMessages = [
            // Новий рідер (device_id=201), ще не зареєстрований, надсилає NFC-картку 501
            ['topic' => 'hashcontrol', 'payload' => '{"id":"201","act":1,"data":"501"}'],
            // Той же рідер, інша картка
            ['topic' => 'hashcontrol', 'payload' => '{"id":"201","act":1,"data":"502"}'],
            // Другий невідомий рідер (202), дія закриття
            ['topic' => 'hashcontrol', 'payload' => '{"id":"202","act":2,"data":"501"}'],
            // Пристрій без дії (лише id та data)
            ['topic' => 'hashcontrol', 'payload' => '{"id":"203","data":"601"}'],
            // Ще один невідомий пристрій — наприклад лічильник
            ['topic' => 'hashcontrol', 'payload' => '{"id":"204","act":8,"data":"701"}'],
        ];

        foreach ($rawMessages as $raw) {
            $data = json_decode($raw['payload'], true);
            if ($data) {
                MqttMessage::create([
                    'topic'     => $raw['topic'],
                    'payload'   => $raw['payload'],
                    'device_id' => $data['id'] ?? null,
                    'action'    => $data['act'] ?? null,
                    'data'      => $data['data'] ?? null,
                ]);
            }
        }
    }
}
