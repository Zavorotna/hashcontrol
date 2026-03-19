<?php

namespace App\Services;

use App\Models\Device;
use App\Models\MqttMessage;
use App\Models\StoreSession;
use App\Models\GeneratorSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Обробляє вхідні MQTT-повідомлення та створює/закриває сесії.
 *
 * Формат повідомлення (payload): "EVENT DEVICE_ID"
 * Наприклад: "OPEN STORE_01_R1" або "START GEN_AQ_01"
 *
 * Device ID вже знаємо з конфігурації в БД через таблицю devices.
 */
class MqttMessageProcessor
{
    public function process(MqttMessage $message): void
    {
        try {
            $deviceId = $message->device_id;

            if (!$deviceId) {
                $message->update(['processed' => true, 'error' => 'device_id відсутній у payload']);
                return;
            }

            /** @var Device|null $device */
            $device = Device::where('mqtt_device_id', $deviceId)
                ->where('is_active', true)
                ->with(['store', 'topic'])
                ->first();

            if (!$device) {
                $message->update([
                    'processed' => true,
                    'error'     => "Пристрій '{$deviceId}' не знайдено в БД",
                ]);
                return;
            }

            $eventTime = Carbon::parse($message->received_at);

            match($device->type) {
                Device::TYPE_READER_OPEN   => $this->handleStoreOpen($device, $eventTime),
                Device::TYPE_READER_CLOSE  => $this->handleStoreClose($device, $eventTime),
                Device::TYPE_GENERATOR_ON  => $this->handleGeneratorOn($device, $eventTime),
                Device::TYPE_GENERATOR_OFF => $this->handleGeneratorOff($device, $eventTime),
            };

            $message->update(['processed' => true]);

        } catch (\Throwable $e) {
            Log::error('MQTT processing error', [
                'message_id' => $message->id,
                'error'      => $e->getMessage(),
            ]);

            $message->update(['processed' => true, 'error' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────

    private function handleStoreOpen(Device $device, Carbon $eventTime): void
    {
        $store = $device->store;

        // Якщо магазин вже відкритий — закриємо попередню сесію (захист від дублікатів)
        $existing = StoreSession::where('store_id', $store->id)
            ->whereNull('closed_at')
            ->first();

        if ($existing) {
            $existing->close($eventTime);
        }

        StoreSession::create([
            'store_id'  => $store->id,
            'opened_at' => $eventTime,
        ]);

        Log::info("Магазин відкрився: {$store->name}", ['time' => $eventTime]);
    }

    private function handleStoreClose(Device $device, Carbon $eventTime): void
    {
        $store = $device->store;

        $session = StoreSession::where('store_id', $store->id)
            ->whereNull('closed_at')
            ->latest('opened_at')
            ->first();

        if (!$session) {
            Log::warning("Закриття без відкриття для магазину: {$store->name}");
            return;
        }

        $session->close($eventTime);

        Log::info("Магазин закрився: {$store->name}", [
            'duration' => $session->fresh()->duration_human,
        ]);
    }

    private function handleGeneratorOn(Device $device, Carbon $eventTime): void
    {
        $topic = $device->topic;

        // Зупиняємо попередню якщо не була зупинена
        $existing = GeneratorSession::where('topic_id', $topic->id)
            ->whereNull('stopped_at')
            ->first();

        if ($existing) {
            $existing->stop($eventTime);
        }

        GeneratorSession::create([
            'topic_id'   => $topic->id,
            'started_at' => $eventTime,
        ]);

        Log::info("Генератор увімкнено: {$topic->name}");
    }

    private function handleGeneratorOff(Device $device, Carbon $eventTime): void
    {
        $topic = $device->topic;

        $session = GeneratorSession::where('topic_id', $topic->id)
            ->whereNull('stopped_at')
            ->latest('started_at')
            ->first();

        if (!$session) {
            Log::warning("Генератор вимкнено але активної сесії немає: {$topic->name}");
            return;
        }

        $session->stop($eventTime);

        Log::info("Генератор вимкнено: {$topic->name}", [
            'fuel' => $session->fresh()->fuel_consumed_total . ' л',
        ]);
    }
}
