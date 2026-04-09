<?php

namespace Database\Seeders;

use App\Models\Action;
use App\Models\Company;
use App\Models\Device;
use App\Models\DeviceAction;
use App\Models\DeviceLog;
use App\Models\TrackedObject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── Actions (updateOrCreate keeps titles in sync with ActionSeeder) ──
        $actOpen    = Action::updateOrCreate(['name' => '1'],  ['title' => 'Entry scan',        'description' => 'NFC card scanned at entry reader']);
        $actClose   = Action::updateOrCreate(['name' => '2'],  ['title' => 'Exit scan',         'description' => 'NFC card scanned at exit reader']);
        $actArrival = Action::updateOrCreate(['name' => '3'],  ['title' => 'Arrival',           'description' => 'Worker arrival scan']);
        $actLeave   = Action::updateOrCreate(['name' => '4'],  ['title' => 'Departure',         'description' => 'Worker departure scan']);
        $actGen     = Action::updateOrCreate(['name' => '5'],  ['title' => 'Generator state',   'description' => 'Generator ON/OFF event']);
        $actTemp    = Action::updateOrCreate(['name' => '7'],  ['title' => 'Temperature',       'description' => 'Temperature sensor reading']);
        $actVent    = Action::updateOrCreate(['name' => '8'],  ['title' => 'Ventilation state', 'description' => 'Ventilation unit ON/OFF event']);
        $actComp    = Action::updateOrCreate(['name' => '9'],  ['title' => 'Compressor state',  'description' => 'Air compressor ON/OFF event']);
        $actFridge  = Action::updateOrCreate(['name' => '10'], ['title' => 'Fridge state',      'description' => 'Fridge controller ON/OFF event']);
        $actSection = Action::updateOrCreate(['name' => '11'], ['title' => 'Section access',    'description' => 'Badge scan at warehouse section entrance']);

        // ══════════════════════════════════════════════════════════════════════
        // "7 Океан" TRC
        // NFC readers log shop entry/exit; generator and ventilation are is_on_off.
        // Cross-stats: how many shop visits occurred while generator/ventilation was ON.
        // ══════════════════════════════════════════════════════════════════════

        $userOzean = User::updateOrCreate(
            ['email' => 'user1@gmail.com'],
            ['name' => 'Власник 7 Океан', 'password' => Hash::make('user1'), 'role' => 'user']
        );
        $compOzean = Company::firstOrCreate(
            ['name' => '7 Океан'],
            ['user_id' => $userOzean->id]
        );

        // Entry reader — range start (each visit logs data = shop external_id)
        $readerIn = Device::updateOrCreate(
            ['device_id' => '101'],
            ['name' => 'Рідер 7 Океан', 'is_range_start' => true, 'is_on_off' => false,
             'user_id' => $userOzean->id, 'company_id' => $compOzean->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $readerIn->id, 'action_id' => $actOpen->id]);

        // Exit reader — range end
        $readerOut = Device::updateOrCreate(
            ['device_id' => '102'],
            ['name' => 'Рідер 7 Океан', 'is_range_start' => false, 'is_on_off' => false,
             'user_id' => $userOzean->id, 'company_id' => $compOzean->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $readerOut->id, 'action_id' => $actClose->id]);

        // Generator — is_on_off, sends data="on"/"off", random activations
        $genOzean = Device::updateOrCreate(
            ['device_id' => '108'],
            ['name' => 'Генератор ТРЦ', 'is_on_off' => true, 'is_range_start' => null,
             'user_id' => $userOzean->id, 'company_id' => $compOzean->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $genOzean->id, 'action_id' => $actGen->id]);

        // Ventilation — is_on_off, runs on a fixed daily schedule 08:00–22:00
        $ventOzean = Device::updateOrCreate(
            ['device_id' => '109'],
            ['name' => 'Витяжка ТРЦ', 'is_on_off' => true, 'is_range_start' => null,
             'user_id' => $userOzean->id, 'company_id' => $compOzean->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $ventOzean->id, 'action_id' => $actVent->id]);

        // Thermometer — single-value device, no range or on/off
        $thermoOzean = Device::updateOrCreate(
            ['device_id' => '110'],
            ['name' => 'Термометр ТРЦ', 'is_on_off' => false, 'is_range_start' => null,
             'user_id' => $userOzean->id, 'company_id' => $compOzean->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $thermoOzean->id, 'action_id' => $actTemp->id]);

        // Shops — NFC card IDs sent as the data field by the readers
        $shops = [
            ['external_id' => '203', 'name' => 'Магазин Puma',     'type' => 'shop',
             'tenant_name' => 'ТОВ Пума Україна',  'email' => 'puma@7ocean.ua',  'phone' => '+380661234001', 'address' => 'ТРЦ 7 Океан, 1 пов., секція A3'],
            ['external_id' => '204', 'name' => 'Магазин Nike',     'type' => 'shop',
             'tenant_name' => 'ФОП Нікітін О.В.',  'email' => 'nike@7ocean.ua',  'phone' => '+380661234002', 'address' => 'ТРЦ 7 Океан, 1 пов., секція A4'],
            ['external_id' => '205', 'name' => "Кав'ярня CupUp",  'type' => 'shop',
             'tenant_name' => 'ТОВ КавАром',       'email' => 'cupup@7ocean.ua', 'phone' => '+380661234003', 'address' => 'ТРЦ 7 Океан, 2 пов., фудкорт'],
            ['external_id' => '206', 'name' => 'Ресторан Sunny',  'type' => 'shop',
             'tenant_name' => 'ФОП Сонячна М.О.',  'email' => 'sunny@7ocean.ua', 'phone' => '+380661234004', 'address' => 'ТРЦ 7 Океан, 2 пов., фудкорт'],
            ['external_id' => '207', 'name' => 'Фото Studio Flash','type' => 'shop',
             'tenant_name' => 'ТОВ Фото Флеш',     'email' => 'flash@7ocean.ua', 'phone' => '+380661234005', 'address' => 'ТРЦ 7 Океан, 3 пов., секція C7'],
        ];

        $shopObjects = [];
        foreach ($shops as $s) {
            $shopObjects[] = TrackedObject::firstOrCreate(
                ['external_id' => $s['external_id'], 'company_id' => $compOzean->id],
                array_merge($s, ['company_id' => $compOzean->id])
            );
        }

        // Shop visit logs (entry+exit pairs, 30 days)
        $this->generateRangeObjectLogs($shopObjects, $readerIn, $actOpen, $readerOut, $actClose, 30, 3, 15, 9, 18, 30, 180);
        // Generator: 14 random activations over 30 days, each lasting 1–4 hours
        $this->generateRandomOnOffLogs($genOzean, $actGen, 30, 14, 60, 240);
        // Ventilation: every day 08:00–22:00
        $this->generateScheduledOnOffLogs($ventOzean, $actVent, 30, 8, 22);
        // Thermometer: reading every 2 hours
        $this->generateThermoLogs($thermoOzean, $actTemp, 30, 20);

        // ══════════════════════════════════════════════════════════════════════
        // Scenario 2 — "Альфа Моторс" garage
        // Workers are tracked by arrival/departure readers.
        // Workshop compressor (is_on_off) runs on weekdays 07:30–17:30.
        // Cross-stats: when was the compressor on vs. when did workers arrive/leave?
        // ══════════════════════════════════════════════════════════════════════

        $userAlfa = User::updateOrCreate(
            ['email' => 'user2@gmail.com'],
            ['name' => 'Менеджер Альфа', 'password' => Hash::make('user2'), 'role' => 'user']
        );
        $compAlfa = Company::firstOrCreate(
            ['name' => 'Альфа Моторс'],
            ['user_id' => $userAlfa->id]
        );

        // Arrival reader — range start
        $readerAlfaIn = Device::updateOrCreate(
            ['device_id' => '103'],
            ['name' => 'Рідер прохідної', 'is_range_start' => true, 'is_on_off' => false,
             'user_id' => $userAlfa->id, 'company_id' => $compAlfa->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $readerAlfaIn->id, 'action_id' => $actArrival->id]);

        // Departure reader — range end
        $readerAlfaOut = Device::updateOrCreate(
            ['device_id' => '106'],
            ['name' => 'Рідер прохідної', 'is_range_start' => false, 'is_on_off' => false,
             'user_id' => $userAlfa->id, 'company_id' => $compAlfa->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $readerAlfaOut->id, 'action_id' => $actLeave->id]);

        // Workshop compressor — is_on_off, weekdays 07:30–17:30
        $compressor = Device::updateOrCreate(
            ['device_id' => '111'],
            ['name' => 'Компресор цеху', 'is_on_off' => true, 'is_range_start' => null,
             'user_id' => $userAlfa->id, 'company_id' => $compAlfa->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $compressor->id, 'action_id' => $actComp->id]);

        // Workshop thermometer
        $thermoAlfa = Device::updateOrCreate(
            ['device_id' => '112'],
            ['name' => 'Термометр цеху', 'is_on_off' => false, 'is_range_start' => null,
             'user_id' => $userAlfa->id, 'company_id' => $compAlfa->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $thermoAlfa->id, 'action_id' => $actTemp->id]);

        // Workers — external_id is the badge/card number
        $workers = [
            ['external_id' => '301', 'name' => 'Іваненко Олег Михайлович', 'type' => 'worker',
             'phone' => '+380501110001', 'email' => 'ivanenko@alfamoto.ua', 'notes' => 'Старший менеджер продажів. Табельний №001'],
            ['external_id' => '302', 'name' => 'Петренко Марія Сергіївна', 'type' => 'worker',
             'phone' => '+380501110002', 'email' => 'petrenko@alfamoto.ua', 'notes' => 'Менеджер з обслуговування клієнтів. Табельний №002'],
            ['external_id' => '303', 'name' => 'Коваль Дмитро Іванович',   'type' => 'worker',
             'phone' => '+380501110003', 'email' => 'koval@alfamoto.ua',    'notes' => 'Автомеханік. Відділ сервісу. Табельний №003'],
        ];

        $workerObjects = [];
        foreach ($workers as $w) {
            $workerObjects[] = TrackedObject::firstOrCreate(
                ['external_id' => $w['external_id'], 'company_id' => $compAlfa->id],
                array_merge($w, ['company_id' => $compAlfa->id])
            );
        }

        // Worker arrival 07:00–09:00, departure 15:00–17:00, weekdays only
        $this->generateWorkerLogs($workerObjects, $readerAlfaIn, $readerAlfaOut, $actArrival, $actLeave, 30);
        // Compressor: weekdays only 07:00–17:00
        $this->generateScheduledOnOffLogs($compressor, $actComp, 30, 7, 17, true);
        // Workshop thermometer: base ~22°C (indoor)
        $this->generateThermoLogs($thermoAlfa, $actTemp, 30, 22);

        // ══════════════════════════════════════════════════════════════════════
        // Scenario 3 — "Тепло Груп" warehouse
        // A section-access reader logs which warehouse section was entered.
        // Generator (random), exhaust (daily), fridge (random) are all is_on_off.
        // Cross-stats: section accesses during generator/exhaust/fridge ON intervals.
        // ══════════════════════════════════════════════════════════════════════

        $userTeplo = User::updateOrCreate(
            ['email' => 'user3@gmail.com'],
            ['name' => 'Диспетчер Тепло Груп', 'password' => Hash::make('user3'), 'role' => 'user']
        );
        $compTeplo = Company::firstOrCreate(
            ['name' => 'Тепло Груп'],
            ['user_id' => $userTeplo->id]
        );

        // Section access reader — entry-only reader (is_range_start=true, no exit pair).
        // Must NOT be null so its data values appear in the unregistered objects list.
        $sectionReader = Device::updateOrCreate(
            ['device_id' => '107'],
            ['name' => 'Зчитувач секцій', 'is_on_off' => false, 'is_range_start' => true,
             'user_id' => $userTeplo->id, 'company_id' => $compTeplo->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $sectionReader->id, 'action_id' => $actSection->id]);

        // Generator — is_on_off, occasional random activations (power outage / testing)
        $genTeplo = Device::updateOrCreate(
            ['device_id' => '104'],
            ['name' => 'Генератор склад', 'is_on_off' => true, 'is_range_start' => null,
             'user_id' => $userTeplo->id, 'company_id' => $compTeplo->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $genTeplo->id, 'action_id' => $actGen->id]);

        // Exhaust / ventilation — is_on_off, daily 06:00–23:00
        $exhaust = Device::updateOrCreate(
            ['device_id' => '113'],
            ['name' => 'Витяжка складу', 'is_on_off' => true, 'is_range_start' => null,
             'user_id' => $userTeplo->id, 'company_id' => $compTeplo->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $exhaust->id, 'action_id' => $actVent->id]);

        // Fridge / cold-storage controller — is_on_off, multiple cycles per day
        $fridge = Device::updateOrCreate(
            ['device_id' => '114'],
            ['name' => 'Холодильна камера', 'is_on_off' => true, 'is_range_start' => null,
             'user_id' => $userTeplo->id, 'company_id' => $compTeplo->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $fridge->id, 'action_id' => $actFridge->id]);

        // Thermometer — cold storage, base ~5°C
        $thermoTeplo = Device::updateOrCreate(
            ['device_id' => '105'],
            ['name' => 'Термометр склад А', 'is_on_off' => false, 'is_range_start' => null,
             'user_id' => $userTeplo->id, 'company_id' => $compTeplo->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $thermoTeplo->id, 'action_id' => $actTemp->id]);

        // Warehouse sections — external_id is the badge/card on the section door
        $sections = [
            ['external_id' => '401', 'name' => 'Секція А', 'type' => 'other',
             'address' => 'Склад корпус 1, секція А', 'notes' => 'Загальний склад. Відкривається 06:00–23:00.'],
            ['external_id' => '402', 'name' => 'Секція Б', 'type' => 'other',
             'address' => 'Склад корпус 1, секція Б', 'notes' => 'Стелажна зона, паллетні вантажі.'],
            ['external_id' => '403', 'name' => 'Секція В', 'type' => 'other',
             'address' => 'Склад корпус 2, секція В', 'notes' => 'Холодильна зона. Доступ обмежений.'],
        ];

        $sectionObjects = [];
        foreach ($sections as $sec) {
            $sectionObjects[] = TrackedObject::firstOrCreate(
                ['external_id' => $sec['external_id'], 'company_id' => $compTeplo->id],
                array_merge($sec, ['company_id' => $compTeplo->id])
            );
        }

        // Section access logs: random times 06:00–23:00, 2–10 accesses/day per section
        $this->generateSingleReaderLogs($sectionObjects, $sectionReader, $actSection, 30, 2, 10, 6, 23);
        // Generator: 8 activations over 30 days, each 2–8 hours (power-outage simulation)
        $this->generateRandomOnOffLogs($genTeplo, $actGen, 30, 8, 120, 480);
        // Exhaust: every day 06:00–23:00
        $this->generateScheduledOnOffLogs($exhaust, $actVent, 30, 6, 23);
        // Fridge: 22 activations over 30 days, each 2–6 hours (cooling cycles)
        $this->generateRandomOnOffLogs($fridge, $actFridge, 30, 22, 120, 360);
        // Cold-storage thermometer: base ~5°C, stays in 2–10°C range
        $this->generateThermoLogs($thermoTeplo, $actTemp, 30, 5);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Log generation helpers
    // All timestamps are UTC to avoid DST edge cases.
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Entry + exit log pairs for objects tracked by two paired readers (range start/end).
     * Each day produces $minVisits–$maxVisits visit pairs per object.
     * Entry hour is random in [$minHour, $maxHour]; exit is +$minStayMin to +$maxStayMin.
     */
    private function generateRangeObjectLogs(
        array   $objects,
        Device  $readerIn,
        Action  $actIn,
        Device  $readerOut,
        Action  $actOut,
        int     $days,
        int     $minVisits,
        int     $maxVisits,
        int     $minHour,
        int     $maxHour,
        int     $minStayMin,
        int     $maxStayMin
    ): void {
        $rows = [];

        for ($d = $days; $d >= 0; $d--) {
            $date   = Carbon::now('UTC')->subDays($d);
            $visits = rand($minVisits, $maxVisits);

            foreach ($objects as $obj) {
                for ($v = 0; $v < $visits; $v++) {
                    $inTime = $date->copy()
                        ->setHour(rand($minHour, $maxHour))
                        ->setMinute(rand(0, 59))
                        ->setSecond(rand(0, 59));

                    $rows[] = [
                        'device_id'  => $readerIn->id,
                        'action_id'  => $actIn->id,
                        'data'       => $obj->external_id,
                        'logged_at'  => $inTime,
                        'created_at' => $inTime,
                        'updated_at' => $inTime,
                    ];

                    $outTime = $inTime->copy()->addMinutes(rand($minStayMin, $maxStayMin));

                    $rows[] = [
                        'device_id'  => $readerOut->id,
                        'action_id'  => $actOut->id,
                        'data'       => $obj->external_id,
                        'logged_at'  => $outTime,
                        'created_at' => $outTime,
                        'updated_at' => $outTime,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DeviceLog::insert($chunk);
        }
    }

    /**
     * Arrival + departure log pairs for workers (weekdays only).
     * Arrival: 07:00–09:00, departure: 15:00–17:00.
     */
    private function generateWorkerLogs(
        array  $workerObjects,
        Device $readerIn,
        Device $readerOut,
        Action $actArrival,
        Action $actLeave,
        int    $days
    ): void {
        $rows = [];

        for ($d = $days; $d >= 0; $d--) {
            $date = Carbon::now('UTC')->subDays($d);
            if ($date->isWeekend()) {
                continue;
            }

            foreach ($workerObjects as $worker) {
                $inTime = $date->copy()
                    ->setHour(rand(7, 8))
                    ->setMinute(rand(0, 59))
                    ->setSecond(rand(0, 59));

                $rows[] = [
                    'device_id'  => $readerIn->id,
                    'action_id'  => $actArrival->id,
                    'data'       => $worker->external_id,
                    'logged_at'  => $inTime,
                    'created_at' => $inTime,
                    'updated_at' => $inTime,
                ];

                $outTime = $date->copy()
                    ->setHour(rand(15, 16))
                    ->setMinute(rand(0, 59))
                    ->setSecond(rand(0, 59));

                $rows[] = [
                    'device_id'  => $readerOut->id,
                    'action_id'  => $actLeave->id,
                    'data'       => $worker->external_id,
                    'logged_at'  => $outTime,
                    'created_at' => $outTime,
                    'updated_at' => $outTime,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DeviceLog::insert($chunk);
        }
    }

    /**
     * Single-reader access logs: one log per access (no exit pair).
     * Used for warehouse section badges.
     */
    private function generateSingleReaderLogs(
        array  $objects,
        Device $reader,
        Action $act,
        int    $days,
        int    $minAccesses,
        int    $maxAccesses,
        int    $minHour,
        int    $maxHour
    ): void {
        $rows = [];

        for ($d = $days; $d >= 0; $d--) {
            $date = Carbon::now('UTC')->subDays($d);

            foreach ($objects as $obj) {
                $count = rand($minAccesses, $maxAccesses);

                for ($a = 0; $a < $count; $a++) {
                    $time = $date->copy()
                        ->setHour(rand($minHour, $maxHour))
                        ->setMinute(rand(0, 59))
                        ->setSecond(rand(0, 59));

                    $rows[] = [
                        'device_id'  => $reader->id,
                        'action_id'  => $act->id,
                        'data'       => $obj->external_id,
                        'logged_at'  => $time,
                        'created_at' => $time,
                        'updated_at' => $time,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DeviceLog::insert($chunk);
        }
    }

    /**
     * Random ON/OFF activations for an is_on_off device.
     * Produces $totalActivations on+off pairs spread randomly across the past $days days.
     * Each activation lasts between $minDurationMin and $maxDurationMin minutes.
     */
    private function generateRandomOnOffLogs(
        Device  $device,
        ?Action $actState,
        int     $days,
        int     $totalActivations,
        int     $minDurationMin,
        int     $maxDurationMin
    ): void {
        $rows = [];

        for ($i = 0; $i < $totalActivations; $i++) {
            $onTime = Carbon::now('UTC')
                ->subDays(rand(0, $days))
                ->setHour(rand(6, 22))
                ->setMinute(rand(0, 59))
                ->setSecond(0);

            $offTime = $onTime->copy()->addMinutes(rand($minDurationMin, $maxDurationMin));

            $rows[] = [
                'device_id'  => $device->id,
                'action_id'  => $actState?->id,
                'data'       => 'on',
                'logged_at'  => $onTime,
                'created_at' => $onTime,
                'updated_at' => $onTime,
            ];
            $rows[] = [
                'device_id'  => $device->id,
                'action_id'  => $actState?->id,
                'data'       => 'off',
                'logged_at'  => $offTime,
                'created_at' => $offTime,
                'updated_at' => $offTime,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DeviceLog::insert($chunk);
        }
    }

    /**
     * Scheduled daily ON/OFF for an is_on_off device (e.g. ventilation, compressor).
     * Sends an "on" event at $onHour ±30 min and an "off" event at $offHour ±30 min
     * every day (or only on weekdays when $weekdaysOnly = true).
     */
    private function generateScheduledOnOffLogs(
        Device  $device,
        ?Action $actState,
        int     $days,
        int     $onHour,
        int     $offHour,
        bool    $weekdaysOnly = false
    ): void {
        $rows = [];

        for ($d = $days; $d >= 0; $d--) {
            $date = Carbon::now('UTC')->subDays($d);

            if ($weekdaysOnly && $date->isWeekend()) {
                continue;
            }

            $onTime  = $date->copy()->setHour($onHour)->setMinute(rand(0, 29))->setSecond(0);
            $offTime = $date->copy()->setHour($offHour)->setMinute(rand(0, 29))->setSecond(0);

            $rows[] = [
                'device_id'  => $device->id,
                'action_id'  => $actState?->id,
                'data'       => 'on',
                'logged_at'  => $onTime,
                'created_at' => $onTime,
                'updated_at' => $onTime,
            ];
            $rows[] = [
                'device_id'  => $device->id,
                'action_id'  => $actState?->id,
                'data'       => 'off',
                'logged_at'  => $offTime,
                'created_at' => $offTime,
                'updated_at' => $offTime,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DeviceLog::insert($chunk);
        }
    }

    /**
     * Temperature sensor readings every 2 hours.
     * $baseTemp is the starting temperature; fluctuates ±1°C per reading, clamped to 2–35°C.
     */
    private function generateThermoLogs(
        Device  $thermoDev,
        Action  $actTemp,
        int     $days,
        int     $baseTemp = 18
    ): void {
        $rows = [];

        for ($d = $days; $d >= 0; $d--) {
            $date = Carbon::now('UTC')->subDays($d);

            for ($h = 0; $h < 24; $h += 2) {
                $time      = $date->copy()->setHour($h)->setMinute(0)->setSecond(0);
                $baseTemp += rand(-1, 1);
                $baseTemp  = max(2, min(35, $baseTemp));

                $rows[] = [
                    'device_id'  => $thermoDev->id,
                    'action_id'  => $actTemp->id,
                    'data'       => (string)$baseTemp,
                    'logged_at'  => $time,
                    'created_at' => $time,
                    'updated_at' => $time,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DeviceLog::insert($chunk);
        }
    }
}
