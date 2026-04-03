<?php

namespace App\Http\Controllers;

use App\Http\Controllers\UserController;
use App\Models\Action;
use App\Models\Company;
use App\Models\Device;
use App\Models\DeviceAction;
use App\Models\BlacklistedDevice;
use App\Models\MqttMessage;
use App\Models\Office;
use App\Models\TrackedObject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    // Devices CRUD
    public function devices()
    {
        $devices = Device::with(['user', 'company', 'deviceActions.action'])->withTrashed()->get();
        return view('admin.devices.index', compact('devices'));
    }

    public function createDevice()
    {
        $users       = User::where('role', 'user')->get();
        $companies   = Company::all();
        $deviceNames = Device::withTrashed()->pluck('name')->unique()->sort()->values();
        return view('admin.devices.create', compact('users', 'companies', 'deviceNames'));
    }

    public function storeDevice(Request $request)
    {
        $request->validate([
            'device_id'  => 'required|unique:devices',
            'name'       => 'required',
            'user_id'    => 'nullable|exists:users,id',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $rangeRaw     = $request->input('is_range_start');
        $isRangeStart = ($rangeRaw === '' || is_null($rangeRaw)) ? null : (bool)$rangeRaw;

        Device::create(array_merge(
            $request->only('device_id', 'name', 'user_id', 'company_id'),
            ['is_range_start' => $isRangeStart]
        ));
        return redirect()->route('admin.devices')->with('success', 'Device created');
    }

    public function editDevice(Device $device)
    {
        $users       = User::where('role', 'user')->get();
        $companies   = Company::all();
        $deviceNames = Device::withTrashed()->pluck('name')->unique()->sort()->values();
        return view('admin.devices.edit', compact('device', 'users', 'companies', 'deviceNames'));
    }

    public function updateDevice(Request $request, Device $device)
    {
        $request->validate([
            'device_id'  => 'required|unique:devices,device_id,' . $device->id,
            'name'       => 'required',
            'user_id'    => 'nullable|exists:users,id',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $rangeRaw     = $request->input('is_range_start');
        $isRangeStart = ($rangeRaw === '' || is_null($rangeRaw)) ? null : (bool)$rangeRaw;

        $device->update(array_merge(
            $request->only('device_id', 'name', 'user_id', 'company_id'),
            ['is_range_start' => $isRangeStart]
        ));
        return redirect()->route('admin.devices')->with('success', 'Device updated');
    }

    public function destroyDevice(Device $device)
    {
        $device->delete();
        return redirect()->route('admin.devices')->with('success', 'Device deleted');
    }

    // Actions CRUD
    public function actions()
    {
        $actions = Action::all();
        return view('admin.actions.index', compact('actions'));
    }

    public function createAction()
    {
        return view('admin.actions.create');
    }

    public function storeAction(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:actions',
            'title' => 'required|string',
            'description' => 'nullable',
        ]);

        Action::create($request->only('name', 'title', 'description'));
        return redirect()->route('admin.actions')->with('success', 'Action created');
    }

    public function editAction(Action $action)
    {
        return view('admin.actions.edit', compact('action'));
    }

    public function updateAction(Request $request, Action $action)
    {
        $request->validate([
            'name' => 'required|unique:actions,name,' . $action->id,
            'title' => 'required|string',
            'description' => 'nullable',
        ]);

        $action->update($request->only('name', 'title', 'description'));
        return redirect()->route('admin.actions')->with('success', 'Action updated');
    }

    public function destroyAction(Action $action)
    {
        $action->delete();
        return redirect()->route('admin.actions')->with('success', 'Action deleted');
    }

    // Companies CRUD
    public function companies()
    {
        $companies = Company::with('user')->get();
        return view('admin.companies.index', compact('companies'));
    }

    public function createCompany()
    {
        $users = User::where('role', 'user')->get();
        return view('admin.companies.create', compact('users'));
    }

    public function storeCompany(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'user_id' => 'required|exists:users,id',
        ]);

        Company::create($request->all());
        return redirect()->route('admin.companies')->with('success', 'Company created');
    }

    public function editCompany(Company $company)
    {
        $users = User::where('role', 'user')->get();
        return view('admin.companies.edit', compact('company', 'users'));
    }

    public function updateCompany(Request $request, Company $company)
    {
        $request->validate([
            'name' => 'required',
            'user_id' => 'required|exists:users,id',
        ]);

        $company->update($request->all());
        return redirect()->route('admin.companies')->with('success', 'Company updated');
    }

    public function destroyCompany(Company $company)
    {
        $company->delete();
        return redirect()->route('admin.companies')->with('success', 'Company deleted');
    }

    // Offices CRUD
    public function offices()
    {
        $offices = Office::with('company')->get();
        return view('admin.offices.index', compact('offices'));
    }

    public function createOffice()
    {
        $companies = Company::all();
        return view('admin.offices.create', compact('companies'));
    }

    public function storeOffice(Request $request)
    {
        $request->validate([
            'office_number' => 'required|unique:offices',
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'nullable',
        ]);

        Office::create($request->all());
        return redirect()->route('admin.offices')->with('success', 'Office created');
    }

    public function editOffice(Office $office)
    {
        $companies = Company::all();
        return view('admin.offices.edit', compact('office', 'companies'));
    }

    public function updateOffice(Request $request, Office $office)
    {
        $request->validate([
            'office_number' => 'required|unique:offices,office_number,' . $office->id,
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'nullable',
        ]);

        $office->update($request->all());
        return redirect()->route('admin.offices')->with('success', 'Office updated');
    }

    public function destroyOffice(Office $office)
    {
        $office->delete();
        return redirect()->route('admin.offices')->with('success', 'Office deleted');
    }

    // Blacklist CRUD
    public function blacklistedDevices()
    {
        $devices = BlacklistedDevice::withTrashed()->get();
        return view('admin.blacklisted_devices.index', compact('devices'));
    }

    public function createBlacklistedDevice()
    {
        return view('admin.blacklisted_devices.create');
    }

    public function storeBlacklistedDevice(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string|unique:blacklisted_devices,device_id',
            'reason' => 'nullable|string',
        ]);

        BlacklistedDevice::create($request->all());

        return redirect()->route('admin.blacklisted_devices')->with('success', 'Device added to blacklist');
    }

    public function editBlacklistedDevice(BlacklistedDevice $blacklistedDevice)
    {
        return view('admin.blacklisted_devices.edit', compact('blacklistedDevice'));
    }

    public function updateBlacklistedDevice(Request $request, BlacklistedDevice $blacklistedDevice)
    {
        $request->validate([
            'device_id' => 'required|string|unique:blacklisted_devices,device_id,' . $blacklistedDevice->id,
            'reason' => 'nullable|string',
        ]);

        $blacklistedDevice->update($request->all());

        return redirect()->route('admin.blacklisted_devices')->with('success', 'Blacklist item updated');
    }

    public function destroyBlacklistedDevice(BlacklistedDevice $blacklistedDevice)
    {
        $blacklistedDevice->delete();
        return redirect()->route('admin.blacklisted_devices')->with('success', 'Blacklisted device removed');
    }

    public function restoreBlacklistedDevice($id)
    {
        $device = BlacklistedDevice::withTrashed()->findOrFail($id);
        $device->restore();
        return redirect()->route('admin.blacklisted_devices')->with('success', 'Blacklisted device restored');
    }

    public function restoreDevice($id)
    {
        $device = Device::withTrashed()->findOrFail($id);
        $device->restore();
        return redirect()->route('admin.devices')->with('success', 'Device restored');
    }

    public function addActionToDevice(Request $request, Device $device)
    {
        $request->validate([
            'action_id' => 'required|exists:actions,id',
            'payload' => 'nullable|string',
        ]);

        DeviceAction::create([
            'device_id' => $device->id,
            'action_id' => $request->action_id,
            'payload' => $request->payload,
        ]);

        return redirect()->route('admin.devices')->with('success', 'Action assigned to device');
    }

    public function removeActionFromDevice(Device $device, DeviceAction $deviceAction)
    {
        $deviceAction->delete();
        return redirect()->route('admin.devices')->with('success', 'Device action removed');
    }

    // MQTT requests management
    public function destroyMqttMessage($id)
    {
        $message = MqttMessage::findOrFail($id);
        $message->delete();
        return redirect()->route('admin.index')->with('success', 'Запит видалено');
    }

    public function restoreMqttMessage($id)
    {
        $message = MqttMessage::withTrashed()->findOrFail($id);
        $message->restore();
        return redirect()->route('admin.index')->with('success', 'Запит відновлено');
    }

    // Users
    public function users()
    {
        $users = User::where('role', 'user')
            ->withCount('devices')
            ->with('companies')
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function userDashboard(User $user)
    {
        abort_if($user->role === 'admin', 403);

        $data = app(UserController::class)->getDashboardData($user);
        return view('user.index', array_merge($data, ['viewingAs' => $user]));
    }

    public function userDashboard(User $user)
    {
        $data = app(UserController::class)->getDashboardData($user);
        return view('user.index', array_merge($data, ['adminViewingAs' => $user]));
    }

    public function destroyUser(User $user)
    {
        abort_if($user->role === 'admin', 403);

        // Відв'язуємо пристрої від власника (не видаляємо самі пристрої)
        $user->devices()->update(['user_id' => null]);
        $user->companies()->update(['user_id' => null]);
        $user->delete();

        return redirect()->route('admin.users')->with('success', "Користувача {$user->name} видалено.");
    }

    // Existing methods
    public function index()
    {
        $pendingRequests = MqttMessage::withTrashed()
            ->whereRaw('NOT EXISTS (SELECT 1 FROM devices WHERE devices.device_id = mqtt_messages.device_id)')
            ->get();
        $devices = Device::with('user', 'company')->get();
        $actions = Action::all();

        return view('admin.index', compact('pendingRequests', 'devices', 'actions'));
    }

    public function showRegisterDeviceForm($id)
    {
        $message           = MqttMessage::findOrFail($id);
        $users             = User::where('role', 'user')->orderBy('name')->get();
        $companies         = Company::orderBy('name')->get();
        $actions           = Action::orderBy('title')->orderBy('name')->get();
        $deviceNames       = Device::withTrashed()->pluck('name')->unique()->sort()->values();
        $generatedPassword = Str::random(10);

        return view('admin.register-device', compact('message', 'users', 'companies', 'actions', 'deviceNames', 'generatedPassword'));
    }
 
    public function registerDevice(Request $request, $id)
    {
        $message = MqttMessage::findOrFail($id);
 
        $request->validate([
            'owner_email'          => 'required|email',
            'owner_name'           => 'nullable|string',
            'owner_new_password'   => 'nullable|string|min:6',
            'company_name'         => 'required|string',
            'device_name'          => 'nullable|string',
            'actions'              => 'nullable|array',
            'actions.*.action_id'  => 'required|exists:actions,id',
            'object_name'          => 'nullable|string|max:255',
        ]);

        // ── Визначаємо або створюємо власника ────────────────────────────────
        $newUserPassword = null;
        $user = User::where('email', $request->owner_email)->first();
        if (!$user) {
            $newUserPassword = $request->owner_new_password ?: Str::random(10);
            $user = User::create([
                'email'    => $request->owner_email,
                'name'     => $request->owner_name ?: explode('@', $request->owner_email)[0],
                'password' => Hash::make($newUserPassword),
                'role'     => 'user',
            ]);
        }

        // ── Визначаємо або створюємо компанію (через datalist — просто за назвою) ─
        $company = Company::firstOrCreate(
            ['name' => $request->company_name],
            ['user_id' => $user->id]
        );

        if ($request->filled('blacklist_device')) {
            BlacklistedDevice::create([
                'device_id' => $message->device_id,
                'reason'    => $request->blacklist_reason ?: 'Чорний список із форми реєстрації',
            ]);

            $message->delete();

            return redirect()->route('admin.index')
                ->with('success', "Пристрій {$message->device_id} додано до чорного списку");
        }

        // ── Створюємо пристрій ────────────────────────────────────────────────
        $rangeRaw     = $request->input('is_range_start');
        $isRangeStart = ($rangeRaw === '' || is_null($rangeRaw)) ? null : (bool)$rangeRaw;

        $device = Device::create([
            'device_id'      => $message->device_id,
            'name'           => $request->device_name ?? ('Device ' . $message->device_id),
            'user_id'        => $user->id,
            'company_id'     => $company->id,
            'is_range_start' => $isRangeStart,
        ]);

        // ── Призначаємо дії пристрою ──────────────────────────────────────────
        if ($request->filled('actions') && is_array($request->actions)) {
            foreach ($request->actions as $actionData) {
                if (!empty($actionData['action_id'])) {
                    DeviceAction::create([
                        'device_id' => $device->id,
                        'action_id' => $actionData['action_id'],
                        'payload'   => $actionData['payload'] ?? null,
                    ]);
                }
            }
        }

        // ── Реєструємо об'єкт за значенням data (якщо потрібно) ──────────────
        if ($request->boolean('register_object') && $message->data) {
            TrackedObject::firstOrCreate(
                ['external_id' => $message->data, 'company_id' => $company->id],
                [
                    'name'       => $request->object_name ?: $message->data,
                    'type'       => 'other',
                    'company_id' => $company->id,
                ]
            );
        }

        $message->delete();

        $successMsg = "Пристрій зареєстровано → {$user->name} / {$company->name}";
        if ($newUserPassword) {
            $successMsg .= " | Новий користувач створено. Пароль: {$newUserPassword}";
        }

        return redirect()->route('admin.index')->with('success', $successMsg);
    }
}
