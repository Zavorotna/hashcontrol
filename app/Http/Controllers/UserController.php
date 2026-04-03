<?php

namespace App\Http\Controllers;

use App\Models\DeviceLog;
use App\Models\TrackedObject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return view('user.index', $this->getDashboardData(auth()->user()));
    }

    public function settings()
    {
        return view('user.settings');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'Введіть поточний пароль.',
            'password.required'         => 'Введіть новий пароль.',
            'password.min'              => 'Пароль має бути не менше 6 символів.',
            'password.confirmed'        => 'Паролі не співпадають.',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Поточний пароль невірний.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Пароль успішно змінено.');
    }

    public function getDashboardData(User $user): array
    {
        $companies  = $user->companies()->with('offices')->get();
        $devices    = $user->devices()->with('deviceActions.action')->get();
        $companyIds = $companies->pluck('id');
        $deviceIds  = $devices->pluck('id');

        $allObjects = TrackedObject::whereIn('company_id', $companyIds)->get();

        $allDataIds = DeviceLog::whereIn('device_id', $deviceIds)
            ->select('data')->distinct()->orderBy('data')->pluck('data');

        $unregisteredDataIds = $allDataIds->diff($allObjects->pluck('external_id'))->values();

        $objectStats = [];
        foreach ($allObjects as $obj) {
            $lastLog = DeviceLog::where('data', $obj->external_id)
                ->with('action')
                ->latest('logged_at')
                ->first();

            $objectStats[$obj->id] = [
                'day'         => DeviceLog::where('data', $obj->external_id)->where('logged_at', '>=', now()->subDay())->count(),
                'week'        => DeviceLog::where('data', $obj->external_id)->where('logged_at', '>=', now()->subWeek())->count(),
                'month'       => DeviceLog::where('data', $obj->external_id)->where('logged_at', '>=', now()->subMonth())->count(),
                'last_data'   => $lastLog?->data,
                'last_action' => $lastLog?->action?->title ?? $lastLog?->action?->name,
                'last_at'     => $lastLog?->logged_at,
            ];
        }

        $objectsByCompany = $allObjects->groupBy('company_id');

        $logs = DeviceLog::whereIn('device_id', $deviceIds)
            ->with(['device', 'action'])
            ->latest('logged_at')
            ->take(20)
            ->get()
            ->map(function ($log) use ($allObjects) {
                $log->tracked_object = $allObjects->firstWhere('external_id', $log->data);
                return $log;
            });

        return compact(
            'companies', 'devices', 'logs',
            'objectsByCompany', 'allObjects',
            'objectStats', 'unregisteredDataIds', 'allDataIds',
        );
    }
}
