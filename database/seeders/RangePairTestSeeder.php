<?php

namespace Database\Seeders;

use App\Models\Action;
use App\Models\Company;
use App\Models\Device;
use App\Models\DeviceLog;
use App\Models\TrackedObject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder для тестування range-pair логіки (вхід/вихід).
 *
 * Сценарії що перевіряються:
 *  1. Нормальна пара вхід → вихід
 *  2. Сесія ще відкрита (зайшов, ще не вийшов)
 *  3. Подвійний вхід без виходу → другий вхід має ігноруватись
 *  4. Вихід без входу → має ігноруватись
 *  5. Кілька сесій за кілька днів
 */
class RangePairTestSeeder extends Seeder
{
    public function run(): void
    {
        // ── Actions ───────────────────────────────────────────────────────────
        $actEntry = Action::updateOrCreate(['name' => '1'], ['title' => 'Вхід',  'description' => 'Сканування картки на вході']);
        $actExit  = Action::updateOrCreate(['name' => '2'], ['title' => 'Вихід', 'description' => 'Сканування картки на виході']);

        // ── User + Company ────────────────────────────────────────────────────
        $user = User::updateOrCreate(
            ['email' => 'test.pair@demo.local'],
            ['name' => 'Тест Пари', 'password' => Hash::make('password'), 'role' => 'user']
        );

        $company = Company::updateOrCreate(
            ['name' => 'Тестова компанія (пари)'],
            ['user_id' => $user->id]
        );

        $company->users()->syncWithoutDetaching([$user->id => ['position' => 'owner']]);

        // ── Range-pair devices — ОДНАКОВА назва "Двері лабораторії" ──────────
        $entryDev = Device::updateOrCreate(
            ['device_id' => 'TEST-ENTRY-001'],
            [
                'user_id'        => $user->id,
                'company_id'     => $company->id,
                'name'           => 'Двері лабораторії',
                'is_range_start' => true,
                'is_on_off'      => false,
            ]
        );

        $exitDev = Device::updateOrCreate(
            ['device_id' => 'TEST-EXIT-001'],
            [
                'user_id'        => $user->id,
                'company_id'     => $company->id,
                'name'           => 'Двері лабораторії',
                'is_range_start' => false,
                'is_on_off'      => false,
            ]
        );

        // Другий одиночний пристрій (без пари) для порівняння у таблиці
        $singleDev = Device::updateOrCreate(
            ['device_id' => 'TEST-SINGLE-001'],
            [
                'user_id'    => $user->id,
                'company_id' => $company->id,
                'name'       => 'Зчитувач коридор',
                'is_range_start' => null,
                'is_on_off'      => false,
            ]
        );

        // ── Tracked objects ───────────────────────────────────────────────────
        $lab1 = TrackedObject::updateOrCreate(
            ['external_id' => 'CARD-LAB-001', 'company_id' => $company->id],
            ['name' => 'Лабораторія 1', 'type' => 'shop']
        );

        $lab2 = TrackedObject::updateOrCreate(
            ['external_id' => 'CARD-LAB-002', 'company_id' => $company->id],
            ['name' => 'Лабораторія 2', 'type' => 'shop']
        );

        $worker = TrackedObject::updateOrCreate(
            ['external_id' => 'CARD-WORKER-001', 'company_id' => $company->id],
            ['name' => 'Іван Тестовий', 'type' => 'worker']
        );

        // Прив'язуємо пристрої до об'єктів
        $lab1->devices()->syncWithoutDetaching([$entryDev->id, $exitDev->id]);
        $lab2->devices()->syncWithoutDetaching([$entryDev->id, $exitDev->id]);
        $worker->devices()->syncWithoutDetaching([$entryDev->id, $exitDev->id]);

        // ── Видаляємо старі логи щоб уникнути дублів ─────────────────────────
        DeviceLog::whereIn('device_id', [$entryDev->id, $exitDev->id, $singleDev->id])->delete();

        // ── Генеруємо логи ────────────────────────────────────────────────────
        $logs = [];

        // ─────────────────────────────────────────────
        // Сценарій 1: КІЛЬКА НОРМАЛЬНИХ СЕСІЙ (2 дні тому)
        // Лабораторія 1: зайшла о 9:00, вийшла 9:45 (45 хв)
        $logs[] = $this->log($entryDev, $actEntry, 'CARD-LAB-001', now()->subDays(2)->setTime(9, 0));
        $logs[] = $this->log($exitDev,  $actExit,  'CARD-LAB-001', now()->subDays(2)->setTime(9, 45));

        // Лабораторія 1: зайшла о 14:00, вийшла 15:30 (90 хв)
        $logs[] = $this->log($entryDev, $actEntry, 'CARD-LAB-001', now()->subDays(2)->setTime(14, 0));
        $logs[] = $this->log($exitDev,  $actExit,  'CARD-LAB-001', now()->subDays(2)->setTime(15, 30));

        // Лабораторія 2: зайшла о 10:00, вийшла 11:20 (80 хв)
        $logs[] = $this->log($entryDev, $actEntry, 'CARD-LAB-002', now()->subDays(2)->setTime(10, 0));
        $logs[] = $this->log($exitDev,  $actExit,  'CARD-LAB-002', now()->subDays(2)->setTime(11, 20));

        // ─────────────────────────────────────────────
        // Сценарій 2: ПОДВІЙНИЙ ВХІД без виходу (вчора)
        // Перший вхід о 10:00 → другий вхід о 10:05 (має ігноруватись)
        // → вихід о 11:00 → зараховується тільки перший вхід 10:00
        $logs[] = $this->log($entryDev, $actEntry, 'CARD-LAB-001', now()->subDay()->setTime(10, 0));
        $logs[] = $this->log($entryDev, $actEntry, 'CARD-LAB-001', now()->subDay()->setTime(10, 5));  // ← ігнорується
        $logs[] = $this->log($exitDev,  $actExit,  'CARD-LAB-001', now()->subDay()->setTime(11, 0));

        // ─────────────────────────────────────────────
        // Сценарій 3: ВИХІД БЕЗ ВХОДУ (вчора)
        // → має ігноруватись, жодної сесії не створюється
        $logs[] = $this->log($exitDev, $actExit, 'CARD-LAB-002', now()->subDay()->setTime(9, 0));  // ← ігнорується

        // Потім нормальна пара для lab2 того ж дня
        $logs[] = $this->log($entryDev, $actEntry, 'CARD-LAB-002', now()->subDay()->setTime(13, 0));
        $logs[] = $this->log($exitDev,  $actExit,  'CARD-LAB-002', now()->subDay()->setTime(13, 30));

        // ─────────────────────────────────────────────
        // Сценарій 4: ВІДКРИТА СЕСІЯ (сьогодні, ще всередині)
        // Лабораторія 1: зайшла годину тому, ще не вийшла
        $logs[] = $this->log($entryDev, $actEntry, 'CARD-LAB-001', now()->subHour());

        // Іван: зайшов 30 хв тому, ще всередині
        $logs[] = $this->log($entryDev, $actEntry, 'CARD-WORKER-001', now()->subMinutes(30));

        // ─────────────────────────────────────────────
        // Кілька записів на одиночному пристрої (для порівняння)
        $logs[] = $this->log($singleDev, $actEntry, 'CARD-LAB-001',    now()->subHours(3));
        $logs[] = $this->log($singleDev, $actEntry, 'CARD-LAB-002',    now()->subHours(2));
        $logs[] = $this->log($singleDev, $actEntry, 'CARD-WORKER-001', now()->subHour());

        DeviceLog::insert($logs);

        $this->command->info('RangePairTestSeeder виконано:');
        $this->command->info('  Пристрій входу:  TEST-ENTRY-001 (Двері лабораторії)');
        $this->command->info('  Пристрій виходу: TEST-EXIT-001  (Двері лабораторії)');
        $this->command->info('  Об\'єкти: Лабораторія 1, Лабораторія 2, Іван Тестовий');
        $this->command->info('  Логін: test.pair@demo.local / password');
    }

    private function log(Device $device, Action $action, string $data, Carbon $time): array
    {
        return [
            'device_id' => $device->id,
            'action_id' => $action->id,
            'data'      => $data,
            'logged_at' => $time->format('Y-m-d H:i:s'),
            'created_at' => $time->format('Y-m-d H:i:s'),
            'updated_at' => $time->format('Y-m-d H:i:s'),
        ];
    }
}
