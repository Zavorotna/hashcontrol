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
        // ══════════════════════════════════════════════════════════════════════
        // ДІЇ (Actions) — ідентифікатори числові (відповідають полю act у MQTT)
        // ══════════════════════════════════════════════════════════════════════

        $actOpen    = Action::firstOrCreate(['name' => '1'], ['title' => 'Відкриття',          'description' => 'Відкриття замку / вхід у приміщення']);
        $actClose   = Action::firstOrCreate(['name' => '2'], ['title' => 'Закриття',           'description' => 'Закриття замку / вихід із приміщення']);
        $actArrival = Action::firstOrCreate(['name' => '3'], ['title' => 'Прихід',             'description' => 'Прихід працівника / прохід на вхід']);
        $actLeave   = Action::firstOrCreate(['name' => '4'], ['title' => 'Відхід',             'description' => 'Відхід працівника / прохід на вихід']);
        $actGenOn   = Action::firstOrCreate(['name' => '5'], ['title' => 'Запуск генератора',  'description' => 'Генератор увімкнено']);
        $actGenOff  = Action::firstOrCreate(['name' => '6'], ['title' => 'Зупинка генератора', 'description' => 'Генератор вимкнено']);
        $actTemp    = Action::firstOrCreate(['name' => '7'], ['title' => 'Температура',        'description' => 'Показник термометра (значення у полі data)']);

        // ══════════════════════════════════════════════════════════════════════
        // СЦЕНАРІЙ 1 — «7 Океан» (ТРЦ, 2 рідери, 5 магазинів)
        // MQTT формат: {"id":"101","act":1,"data":"203"}
        // ══════════════════════════════════════════════════════════════════════

        $userOzean = User::firstOrCreate(
            ['email' => 'ozean@example.com'],
            ['name' => 'Власник 7 Океан', 'password' => Hash::make('password'), 'role' => 'user']
        );

        $compOzean = Company::firstOrCreate(
            ['name' => '7 Океан'],
            ['user_id' => $userOzean->id]
        );

        // Рідер A — відкриття (вхід). device_id "101", початок діапазону
        $readerOpenDev = Device::firstOrCreate(
            ['device_id' => '101'],
            ['name' => 'Рідер 7 Океан', 'is_range_start' => true, 'user_id' => $userOzean->id, 'company_id' => $compOzean->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $readerOpenDev->id, 'action_id' => $actOpen->id]);

        // Рідер B — закриття (вихід). device_id "102", кінець діапазону
        $readerCloseDev = Device::firstOrCreate(
            ['device_id' => '102'],
            ['name' => 'Рідер 7 Океан', 'is_range_start' => false, 'user_id' => $userOzean->id, 'company_id' => $compOzean->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $readerCloseDev->id, 'action_id' => $actClose->id]);

        // Магазини — NFC-ключі з числовими ID (data = 203…207)
        $shops = [
            ['external_id' => '203', 'name' => 'Магазин Puma',       'tenant_name' => 'ТОВ Пума Україна',  'email' => 'puma@7ocean.ua',   'phone' => '+380661234001', 'address' => 'ТРЦ 7 Океан, 1 пов., секція A3'],
            ['external_id' => '204', 'name' => 'Магазин Nike',        'tenant_name' => 'ФОП Нікітін О.В.', 'email' => 'nike@7ocean.ua',   'phone' => '+380661234002', 'address' => 'ТРЦ 7 Океан, 1 пов., секція A4'],
            ['external_id' => '205', 'name' => "Кав'ярня CupUp",      'tenant_name' => 'ТОВ КавАром',       'email' => 'cupup@7ocean.ua',  'phone' => '+380661234003', 'address' => 'ТРЦ 7 Океан, 2 пов., фудкорт'],
            ['external_id' => '206', 'name' => 'Ресторан Sunny',      'tenant_name' => 'ФОП Сонячна М.О.', 'email' => 'sunny@7ocean.ua',  'phone' => '+380661234004', 'address' => 'ТРЦ 7 Океан, 2 пов., фудкорт'],
            ['external_id' => '207', 'name' => 'Фото Studio Flash',   'tenant_name' => 'ТОВ Фото Флеш',   'email' => 'flash@7ocean.ua',  'phone' => '+380661234005', 'address' => 'ТРЦ 7 Океан, 3 пов., секція C7'],
        ];

        $shopObjects = [];
        foreach ($shops as $s) {
            $shopObjects[] = TrackedObject::firstOrCreate(
                ['external_id' => $s['external_id'], 'company_id' => $compOzean->id],
                array_merge($s, ['type' => 'shop', 'company_id' => $compOzean->id])
            );
        }

        $this->generateShopLogs($shopObjects, $readerOpenDev, $actOpen, $readerCloseDev, $actClose, 30);

        // ══════════════════════════════════════════════════════════════════════
        // СЦЕНАРІЙ 2 — «Альфа Моторс» (1 рідер, 3 працівники)
        // MQTT формат: {"id":"103","act":3,"data":"301"}  — прихід
        //              {"id":"103","act":4,"data":"301"}  — відхід
        // ══════════════════════════════════════════════════════════════════════

        $userAlfa = User::firstOrCreate(
            ['email' => 'alfa@example.com'],
            ['name' => 'Менеджер Альфа', 'password' => Hash::make('password'), 'role' => 'user']
        );

        $compAlfa = Company::firstOrCreate(
            ['name' => 'Альфа Моторс'],
            ['user_id' => $userAlfa->id]
        );

        // Рідер прохідної вхід. device_id "103", початок діапазону
        $readerAlfaDev = Device::firstOrCreate(
            ['device_id' => '103'],
            ['name' => 'Рідер прохідної', 'is_range_start' => true, 'user_id' => $userAlfa->id, 'company_id' => $compAlfa->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $readerAlfaDev->id, 'action_id' => $actArrival->id]);

        // Рідер прохідної вихід. device_id "106", кінець діапазону
        $readerAlfaOutDev = Device::firstOrCreate(
            ['device_id' => '106'],
            ['name' => 'Рідер прохідної', 'is_range_start' => false, 'user_id' => $userAlfa->id, 'company_id' => $compAlfa->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $readerAlfaOutDev->id, 'action_id' => $actLeave->id]);

        // Картки працівників (числові ID)
        $workers = [
            ['external_id' => '301', 'name' => 'Іваненко Олег Михайлович', 'phone' => '+380501110001', 'email' => 'ivanenko@alfamoto.ua', 'notes' => 'Старший менеджер продажів. Табельний №001'],
            ['external_id' => '302', 'name' => 'Петренко Марія Сергіївна', 'phone' => '+380501110002', 'email' => 'petrenko@alfamoto.ua', 'notes' => 'Менеджер з обслуговування клієнтів. Табельний №002'],
            ['external_id' => '303', 'name' => 'Коваль Дмитро Іванович',   'phone' => '+380501110003', 'email' => 'koval@alfamoto.ua',    'notes' => 'Автомеханік. Відділ сервісу. Табельний №003'],
        ];

        $workerObjects = [];
        foreach ($workers as $w) {
            $workerObjects[] = TrackedObject::firstOrCreate(
                ['external_id' => $w['external_id'], 'company_id' => $compAlfa->id],
                array_merge($w, ['type' => 'worker', 'company_id' => $compAlfa->id])
            );
        }

        $this->generateWorkerLogs($workerObjects, $readerAlfaDev, $readerAlfaOutDev, $actArrival, $actLeave, 30);

        // ══════════════════════════════════════════════════════════════════════
        // СЦЕНАРІЙ 3 — «Тепло Груп» (генератор + термометр)
        // Генератор: {"id":"104","act":5,"data":"401"}  — запуск
        //            {"id":"104","act":6,"data":"401"}  — зупинка
        // Термометр: {"id":"105","act":7,"data":"18"}   — температура (значення)
        // ══════════════════════════════════════════════════════════════════════

        $userTeplo = User::firstOrCreate(
            ['email' => 'teplo@example.com'],
            ['name' => 'Диспетчер Тепло Груп', 'password' => Hash::make('password'), 'role' => 'user']
        );

        $compTeplo = Company::firstOrCreate(
            ['name' => 'Тепло Груп'],
            ['user_id' => $userTeplo->id]
        );

        // Генератор: два рідери з однаковою назвою, різні ролі
        $genDevOn = Device::firstOrCreate(
            ['device_id' => '104'],
            ['name' => 'Генератор головний', 'is_range_start' => true, 'user_id' => $userTeplo->id, 'company_id' => $compTeplo->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $genDevOn->id, 'action_id' => $actGenOn->id]);

        $genDevOff = Device::firstOrCreate(
            ['device_id' => '107'],
            ['name' => 'Генератор головний', 'is_range_start' => false, 'user_id' => $userTeplo->id, 'company_id' => $compTeplo->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $genDevOff->id, 'action_id' => $actGenOff->id]);

        // Термометр: одиничний (is_range_start = null)
        $thermoDev = Device::firstOrCreate(
            ['device_id' => '105'],
            ['name' => 'Термометр склад А', 'is_range_start' => null, 'user_id' => $userTeplo->id, 'company_id' => $compTeplo->id]
        );
        DeviceAction::firstOrCreate(['device_id' => $thermoDev->id, 'action_id' => $actTemp->id]);

        // Відстежуваний об'єкт — генераторна установка
        // data="401" ідентифікує цей конкретний агрегат
        $genObject = TrackedObject::firstOrCreate(
            ['external_id' => '401', 'company_id' => $compTeplo->id],
            [
                'name'    => 'Головний генератор',
                'type'    => 'generator',
                'address' => 'вул. Промислова 12, підстанція',
                'notes'   => 'Дизельний генератор 100 кВт. Обслуговування — раз на 3 місяці.',
            ]
        );

        // Термометр: data = числове значення температури (напр. "18", "19")
        // TrackedObject не реєструємо — ці data-значення з'являться
        // у розділі "Незареєстровані ID" і користувач побачить, що термометр активний.

        $this->generateGeneratorLogs($genObject, $genDevOn, $genDevOff, $actGenOn, $actGenOff, 30);
        $this->generateThermoLogs($thermoDev, $actTemp, 30);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Хелпери генерації логів
    // (всі Carbon у UTC — виключає помилки переходу на літній час)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Відкриття/закриття для магазинів. Закриття — не більше ніж +3 год,
     * щоб гарантовано залишитися в межах того ж дня.
     */
    private function generateShopLogs(
        array $shopObjects,
        Device $readerOpen,
        Action $actOpen,
        Device $readerClose,
        Action $actClose,
        int $days
    ): void {
        $rows = [];

        for ($d = $days; $d >= 0; $d--) {
            $date = Carbon::now('UTC')->subDays($d);

            foreach ($shopObjects as $shop) {
                $visits = rand(3, 15);

                for ($v = 0; $v < $visits; $v++) {
                    // Відкриття між 09:00 і 19:00 UTC
                    $openTime = $date->copy()
                        ->setHour(rand(9, 18))
                        ->setMinute(rand(0, 59))
                        ->setSecond(rand(0, 59));

                    $rows[] = [
                        'device_id'  => $readerOpen->id,
                        'action_id'  => $actOpen->id,
                        'data'       => $shop->external_id,
                        'logged_at'  => $openTime,
                        'created_at' => $openTime,
                        'updated_at' => $openTime,
                    ];

                    // Закриття через 30хв–3год (не перетинає північ)
                    $closeTime = $openTime->copy()->addMinutes(rand(30, 180));

                    $rows[] = [
                        'device_id'  => $readerClose->id,
                        'action_id'  => $actClose->id,
                        'data'       => $shop->external_id,
                        'logged_at'  => $closeTime,
                        'created_at' => $closeTime,
                        'updated_at' => $closeTime,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DeviceLog::insert($chunk);
        }
    }

    /**
     * Прихід/відхід для працівників (тільки пн–пт).
     */
    private function generateWorkerLogs(
        array $workerObjects,
        Device $readerIn,
        Device $readerOut,
        Action $actArrival,
        Action $actLeave,
        int $days
    ): void {
        $rows = [];

        for ($d = $days; $d >= 0; $d--) {
            $date = Carbon::now('UTC')->subDays($d);

            if ($date->isWeekend()) {
                continue;
            }

            foreach ($workerObjects as $worker) {
                // Прихід 07:00–09:00 UTC — рідер входу
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

                // Відхід 15:00–17:00 UTC — рідер виходу
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
     * Запуски/зупинки генератора. data = external_id генераторного об'єкта (числовий).
     */
    private function generateGeneratorLogs(
        TrackedObject $genObject,
        Device $genDevOn,
        Device $genDevOff,
        Action $actGenOn,
        Action $actGenOff,
        int $days
    ): void {
        $rows        = [];
        $activations = rand(4, 8);

        for ($i = 0; $i < $activations; $i++) {
            $startDay  = Carbon::now('UTC')->subDays(rand(1, $days));
            $startTime = $startDay->copy()->setHour(rand(6, 22))->setMinute(rand(0, 59))->setSecond(0);

            $rows[] = [
                'device_id'  => $genDevOn->id,
                'action_id'  => $actGenOn->id,
                'data'       => $genObject->external_id,
                'logged_at'  => $startTime,
                'created_at' => $startTime,
                'updated_at' => $startTime,
            ];

            // Зупинка через 1–6 год
            $stopTime = $startTime->copy()->addHours(rand(1, 6));

            $rows[] = [
                'device_id'  => $genDevOff->id,
                'action_id'  => $actGenOff->id,
                'data'       => $genObject->external_id,
                'logged_at'  => $stopTime,
                'created_at' => $stopTime,
                'updated_at' => $stopTime,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DeviceLog::insert($chunk);
        }
    }

    /**
     * Показники термометра кожні 2 год.
     * data = ціле числове значення температури (наприклад "18", "19").
     * Ці data-значення не будуть збігатися з жодним TrackedObject.external_id —
     * вони з'являться у розділі "Незареєстровані ID" на панелі користувача.
     */
    private function generateThermoLogs(
        Device $thermoDev,
        Action $actTemp,
        int $days
    ): void {
        $rows     = [];
        $tempBase = 18;

        for ($d = $days; $d >= 0; $d--) {
            $date = Carbon::now('UTC')->subDays($d);

            for ($h = 0; $h < 24; $h += 2) {
                $time = $date->copy()->setHour($h)->setMinute(0)->setSecond(0);

                // Цілочислове коливання ±1°C
                $tempBase += rand(-1, 1);
                $tempBase  = max(5, min(30, $tempBase));

                $rows[] = [
                    'device_id'  => $thermoDev->id,
                    'action_id'  => $actTemp->id,
                    'data'       => (string)$tempBase,
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
