<?php

namespace Database\Seeders;

use App\Models\MqttMessage;
use Illuminate\Database\Seeder;

class MqttMessageSeeder extends Seeder
{
    /**
     * Simulate unregistered devices sending MQTT messages that await admin registration.
     * Device IDs 501–510 are intentionally not registered in the devices table.
     * Covers all major device types: NFC reader, ON/OFF (generator/exhaust/fridge),
     * thermometer, counter, and unknown-format device.
     */
    public function run(): void
    {
        $rawMessages = [
            // ── NFC entry reader (new TRC, device 501) ───────────────────────
            // Scans three different NFC cards — each card is a potential shop object
            ['topic' => 'hashcontrol', 'payload' => '{"id":"501","act":"1","data":"801"}'],
            ['topic' => 'hashcontrol', 'payload' => '{"id":"501","act":"1","data":"802"}'],
            ['topic' => 'hashcontrol', 'payload' => '{"id":"501","act":"1","data":"803"}'],

            // ── NFC exit reader (paired with 501, device 502) ─────────────────
            ['topic' => 'hashcontrol', 'payload' => '{"id":"502","act":"2","data":"801"}'],

            // ── Generator / relay (ON/OFF device, device 503) ─────────────────
            // data field contains "on" or "off" — marks it as is_on_off type
            ['topic' => 'hashcontrol', 'payload' => '{"id":"503","data":"on"}'],
            ['topic' => 'hashcontrol', 'payload' => '{"id":"503","data":"off"}'],

            // ── Thermometer (device 504) ──────────────────────────────────────
            // data = numeric temperature value; no act field
            ['topic' => 'hashcontrol', 'payload' => '{"id":"504","data":"23"}'],
            ['topic' => 'hashcontrol', 'payload' => '{"id":"504","data":"22"}'],

            // ── Ventilation / exhaust unit (ON/OFF, device 505) ───────────────
            ['topic' => 'hashcontrol', 'payload' => '{"id":"505","act":"8","data":"on"}'],
            ['topic' => 'hashcontrol', 'payload' => '{"id":"505","act":"8","data":"off"}'],

            // ── Fridge / cold-storage controller (ON/OFF, device 506) ─────────
            ['topic' => 'hashcontrol', 'payload' => '{"id":"506","act":"10","data":"on"}'],
            ['topic' => 'hashcontrol', 'payload' => '{"id":"506","act":"10","data":"off"}'],

            // ── Pulse counter (device 507) ────────────────────────────────────
            // data = cumulative counter value
            ['topic' => 'hashcontrol', 'payload' => '{"id":"507","act":"14","data":"1052"}'],
            ['topic' => 'hashcontrol', 'payload' => '{"id":"507","act":"14","data":"1078"}'],

            // ── Worker badge reader (device 508) ──────────────────────────────
            ['topic' => 'hashcontrol', 'payload' => '{"id":"508","act":"3","data":"901"}'],
            ['topic' => 'hashcontrol', 'payload' => '{"id":"508","act":"4","data":"901"}'],

            // ── Unknown / non-standard device (device 509) ────────────────────
            // No act field; data looks like an arbitrary identifier
            ['topic' => 'hashcontrol', 'payload' => '{"id":"509","data":"ABX-77"}'],
        ];

        foreach ($rawMessages as $raw) {
            $data = json_decode($raw['payload'], true);
            if ($data) {
                MqttMessage::create([
                    'topic'     => $raw['topic'],
                    'payload'   => $raw['payload'],
                    'device_id' => $data['id']  ?? null,
                    'action'    => $data['act'] ?? null,
                    'data'      => $data['data'] ?? null,
                ]);
            }
        }
    }
}
