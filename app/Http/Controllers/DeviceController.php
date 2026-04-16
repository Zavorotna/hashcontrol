<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLog;
use App\Models\TrackedObject;
use Illuminate\Http\Request;
use PhpMqtt\Client\Facades\MQTT;

class DeviceController extends Controller
{
    public function show(Device $device, \Illuminate\Http\Request $request)
    {
        $this->authorize($device);

        $backPeriod     = $request->query('period', 'week');
        $backDeviceView = $request->query('device_view', 'my');

        $device->load('deviceActions.action');

        // Last known state for ON/OFF devices
        $currentState   = null;
        $lastStateLog   = null;
        if ($device->is_on_off) {
            $lastStateLog = DeviceLog::where('device_id', $device->id)
                ->whereIn('data', ['on', 'off'])
                ->latest('logged_at')
                ->first();
            $currentState = $lastStateLog?->data ?? 'unknown';
        }

        // Last measurement for single-value devices
        $lastMeasurement = null;
        if (!$device->is_on_off && is_null($device->getRawOriginal('is_range_start'))) {
            $lastMeasurement = DeviceLog::where('device_id', $device->id)
                ->latest('logged_at')
                ->first();
        }

        // Resolve data values → tracked object names for reader devices
        $objectMap = collect();
        if ($device->company_id) {
            $objectMap = TrackedObject::where('company_id', $device->company_id)
                ->pluck('name', 'external_id');
        }

        // Recent logs
        $recentLogs = DeviceLog::where('device_id', $device->id)
            ->with('action')
            ->latest('logged_at')
            ->take(30)
            ->get();

        return view('user.devices.show', compact(
            'device', 'currentState', 'lastStateLog', 'lastMeasurement',
            'objectMap', 'recentLogs', 'backPeriod', 'backDeviceView'
        ));
    }

    public function sendCommand(Request $request, Device $device)
    {
        $this->authorize($device);

        $request->validate([
            'action_name' => 'nullable|string|max:50',
            'data'        => 'required|string|max:500',
        ]);

        $payload = json_encode([
            'id'   => $device->device_id,
            'act'  => $request->action_name ?: null,
            'data' => $request->data,
        ]);

        try {
            $mqtt = MQTT::connection();
            $mqtt->publish(config('mqtt.publish_topic', 'hashcontrol'), $payload, 0);
            $mqtt->disconnect();

            return back()->with('command_sent', $payload);
        } catch (\Exception $e) {
            return back()->with('command_error', $e->getMessage());
        }
    }

    private function authorize(Device $device): void
    {
        if (auth()->user()->role === 'admin') {
            return;
        }
        abort_unless($device->user_id === auth()->id(), 403);
    }
}
