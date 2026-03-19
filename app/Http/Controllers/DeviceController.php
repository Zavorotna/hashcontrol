<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Store;
use App\Models\Topic;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    // ─── Доступні типи подій ──────────────────────────────────────
    // Щоб додати новий тип — просто дописати сюди
    const EVENT_TYPES = [
        'reader_open'   => 'Рідер (відкриття)',
        'reader_close'  => 'Рідер (закриття)',
        'generator_on'  => 'Генератор (увімкнення)',
        'generator_off' => 'Генератор (вимкнення)',
        'generic_on'    => 'Пристрій (увімкнення)',
        'generic_off'   => 'Пристрій (вимкнення)',
    ];

    // ─── Доступні типи сесій ──────────────────────────────────────
    const SESSION_TYPES = [
        'store_open'   => 'Відкриття магазину',
        'generator'    => 'Генератор',
        'refrigerator' => 'Холодильник',
        'shift'        => 'Зміна',
        'security'     => 'Охорона',
        'generic'      => 'Загальне',
    ];

    // ─── Одиниці метрики ─────────────────────────────────────────
    const METRIC_UNITS = [
        'л'      => 'Літри (л)',
        'кВт·год' => 'Кіловат-години (кВт·год)',
        'год'    => 'Години (год)',
        '°C'     => 'Градуси (°C)',
        '%'      => 'Відсотки (%)',
    ];

    // ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $topics = Topic::all();
        $query  = Device::with(['topic', 'store'])->latest();

        if ($request->filled('topic_id')) {
            $query->where('topic_id', $request->topic_id);
        }

        if ($request->filled('session_type')) {
            $query->where('session_type', $request->session_type);
        }

        $devices      = $query->paginate(20);
        $sessionTypes = self::SESSION_TYPES;

        return view('devices.index', compact('devices', 'topics', 'sessionTypes'));
    }

    public function create(Request $request)
    {
        $topics       = Topic::where('is_active', true)->get();
        $stores       = Store::where('is_active', true)->with('topic')->get();
        $eventTypes   = self::EVENT_TYPES;
        $sessionTypes = self::SESSION_TYPES;
        $metricUnits  = self::METRIC_UNITS;

        return view('devices.create', compact(
            'topics', 'stores', 'eventTypes', 'sessionTypes', 'metricUnits'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'topic_id'             => 'required|exists:topics,id',
            'store_id'             => 'nullable|exists:stores,id',
            'mqtt_device_id'       => 'required|string|max:100|unique:devices',
            'name'                 => 'nullable|string|max:255',
            'is_active'            => 'boolean',

            // Нові поля
            'type'                 => 'required|string|in:' . implode(',', array_keys(self::EVENT_TYPES)),
            'session_type'         => 'required|string|in:' . implode(',', array_keys(self::SESSION_TYPES)),
            'metric_rate_per_hour' => 'nullable|numeric|min:0|max:9999',
            'metric_unit'          => 'nullable|string|in:' . implode(',', array_keys(self::METRIC_UNITS)),
            'metric_label'         => 'nullable|string|max:100',
        ]);

        // Пристрої що не є рідерами — не прив'язані до магазину
        if (!in_array($validated['type'], ['reader_open', 'reader_close'])) {
            $validated['store_id'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        Device::create($validated);

        return redirect()->route('devices.index')
            ->with('success', 'Пристрій додано успішно!');
    }

    public function show(Device $device)
    {
        $device->load(['topic', 'store', 'workSessions' => fn($q) => $q->latest()->limit(10)]);

        return view('devices.show', compact('device'));
    }

    public function edit(Device $device)
    {
        $topics       = Topic::where('is_active', true)->get();
        $stores       = Store::where('is_active', true)->with('topic')->get();
        $eventTypes   = self::EVENT_TYPES;
        $sessionTypes = self::SESSION_TYPES;
        $metricUnits  = self::METRIC_UNITS;

        return view('devices.edit', compact(
            'device', 'topics', 'stores', 'eventTypes', 'sessionTypes', 'metricUnits'
        ));
    }

    public function update(Request $request, Device $device)
    {
        $validated = $request->validate([
            'topic_id'             => 'required|exists:topics,id',
            'store_id'             => 'nullable|exists:stores,id',
            'mqtt_device_id'       => 'required|string|max:100|unique:devices,mqtt_device_id,' . $device->id,
            'name'                 => 'nullable|string|max:255',
            'is_active'            => 'boolean',

            'type'                 => 'required|string|in:' . implode(',', array_keys(self::EVENT_TYPES)),
            'session_type'         => 'required|string|in:' . implode(',', array_keys(self::SESSION_TYPES)),
            'metric_rate_per_hour' => 'nullable|numeric|min:0|max:9999',
            'metric_unit'          => 'nullable|string|in:' . implode(',', array_keys(self::METRIC_UNITS)),
            'metric_label'         => 'nullable|string|max:100',
        ]);

        if (!in_array($validated['type'], ['reader_open', 'reader_close'])) {
            $validated['store_id'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active');

        $device->update($validated);

        return redirect()->route('devices.index')
            ->with('success', 'Пристрій оновлено!');
    }

    public function destroy(Device $device)
    {
        $device->delete();

        return redirect()->route('devices.index')
            ->with('success', 'Пристрій видалено!');
    }
}