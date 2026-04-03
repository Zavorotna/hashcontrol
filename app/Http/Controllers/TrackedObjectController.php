<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLog;
use App\Models\TrackedObject;
use Illuminate\Http\Request;
use PhpMqtt\Client\Facades\MQTT;

class TrackedObjectController extends Controller
{
    public function index()
    {
        $user       = auth()->user();
        $companyIds = $user->companies()->pluck('id');

        $objects = TrackedObject::whereIn('company_id', $companyIds)
            ->with('company')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('user.tracked-objects.index', compact('objects'));
    }

    public function create()
    {
        $companies = auth()->user()->companies;
        return view('user.tracked-objects.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $user       = auth()->user();
        $companyIds = $user->companies()->pluck('id')->toArray();

        $request->validate([
            'external_id' => 'required|string|max:100',
            'company_id'  => 'required|in:' . implode(',', $companyIds),
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:shop,generator,fridge,counter,worker,thermometer,other',
            'tenant_name' => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:500',
            'notes'       => 'nullable|string',
        ]);

        $exists = TrackedObject::where('external_id', $request->external_id)
            ->where('company_id', $request->company_id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['external_id' => 'Цей ID вже зареєстровано для даної компанії.'])->withInput();
        }

        TrackedObject::create($request->only(
            'external_id', 'company_id', 'name', 'type',
            'tenant_name', 'email', 'phone', 'address', 'notes'
        ));

        return redirect()->route('user.tracked-objects.index')->with('success', 'Об\'єкт зареєстровано.');
    }

    public function show(TrackedObject $trackedObject)
    {
        $this->authorizeObject($trackedObject);

        // Визначаємо пристрої що надсилали дані для цього об'єкта
        $deviceIds = DeviceLog::where('data', $trackedObject->external_id)
            ->pluck('device_id')
            ->unique();

        $associatedDevices = Device::whereIn('id', $deviceIds)
            ->with('deviceActions.action')
            ->get();

        $recentLogs = DeviceLog::where('data', $trackedObject->external_id)
            ->with(['device', 'action'])
            ->latest('logged_at')
            ->take(50)
            ->get();

        $stats = [
            'day'   => DeviceLog::where('data', $trackedObject->external_id)->where('logged_at', '>=', now()->subDay())->count(),
            'week'  => DeviceLog::where('data', $trackedObject->external_id)->where('logged_at', '>=', now()->subWeek())->count(),
            'month' => DeviceLog::where('data', $trackedObject->external_id)->where('logged_at', '>=', now()->subMonth())->count(),
        ];

        return view('user.tracked-objects.show', compact(
            'trackedObject', 'recentLogs', 'stats', 'associatedDevices'
        ));
    }

    public function edit(TrackedObject $trackedObject)
    {
        $this->authorizeObject($trackedObject);
        $companies = auth()->user()->companies;
        return view('user.tracked-objects.create', compact('trackedObject', 'companies'));
    }

    public function update(Request $request, TrackedObject $trackedObject)
    {
        $this->authorizeObject($trackedObject);

        $companyIds = auth()->user()->companies()->pluck('id')->toArray();

        $request->validate([
            'external_id' => 'required|string|max:100',
            'company_id'  => 'required|in:' . implode(',', $companyIds),
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:shop,generator,fridge,counter,worker,thermometer,other',
            'tenant_name' => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:500',
            'notes'       => 'nullable|string',
        ]);

        $trackedObject->update($request->only(
            'external_id', 'company_id', 'name', 'type',
            'tenant_name', 'email', 'phone', 'address', 'notes'
        ));

        return redirect()->route('user.tracked-objects.show', $trackedObject)->with('success', 'Збережено.');
    }

    public function destroy(TrackedObject $trackedObject)
    {
        $this->authorizeObject($trackedObject);
        $trackedObject->delete();
        return redirect()->route('user.tracked-objects.index')->with('success', 'Об\'єкт видалено.');
    }

    /**
     * Відправити команду на пристрій через MQTT.
     * POST /user/tracked-objects/{trackedObject}/send-command
     */
    public function sendCommand(Request $request, TrackedObject $trackedObject)
    {
        $this->authorizeObject($trackedObject);

        $request->validate([
            'device_id'   => 'required|exists:devices,id',
            'action_name' => 'required|string|max:100',
            'data'        => 'required|string|max:500',
        ]);

        $device = Device::findOrFail($request->device_id);

        // Перевіряємо що пристрій належить компанії юзера
        $companyIds = auth()->user()->companies()->pluck('id');
        abort_unless($companyIds->contains($device->company_id), 403);

        $payload = json_encode([
            'id'   => $device->device_id,
            'act'  => $request->action_name,
            'data' => $request->data,
        ]);

        try {
            $mqtt = MQTT::connection();
            $mqtt->publish(config('mqtt.publish_topic', 'hashcontrol'), $payload, 0);
            $mqtt->disconnect();

            return back()->with('command_sent', "Команду відправлено: {$payload}");
        } catch (\Exception $e) {
            return back()->with('command_error', 'Помилка відправки MQTT: ' . $e->getMessage());
        }
    }

    private function authorizeObject(TrackedObject $obj): void
    {
        $companyIds = auth()->user()->companies()->pluck('id');
        abort_unless($companyIds->contains($obj->company_id), 403);
    }
}
