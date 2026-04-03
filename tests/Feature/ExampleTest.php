<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\BlacklistedDevice;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_can_attach_action_to_device(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $device = Device::create(['device_id' => 'D100', 'name' => 'Device 100', 'user_id' => $admin->id, 'company_id' => null]);
        $action = Action::create(['name' => 'test_action', 'title' => 'Test action', 'description' => 'Test']);

        $response = $this->actingAs($admin)->post(route('admin.devices.actions.store', ['device' => $device->id]), [
            'action_id' => $action->id,
            'payload' => 'abc',
        ]);

        $response->assertRedirect(route('admin.devices'));
        $this->assertDatabaseHas('device_actions', ['device_id' => $device->id, 'action_id' => $action->id, 'payload' => 'abc']);
    }

    public function test_can_restore_deleted_device(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $device = Device::create(['device_id' => 'D200', 'name' => 'Device 200', 'user_id' => $admin->id, 'company_id' => null]);
        $device->delete();

        $response = $this->actingAs($admin)->post(route('admin.devices.restore', ['device' => $device->id]));

        $response->assertRedirect(route('admin.devices'));
        $this->assertDatabaseHas('devices', ['id' => $device->id, 'deleted_at' => null]);
    }

    public function test_can_restore_blacklisted_device(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $blacklisted = BlacklistedDevice::create(['device_id' => 'D300', 'reason' => 'test']);
        $blacklisted->delete();

        $response = $this->actingAs($admin)->post(route('admin.blacklisted_devices.restore', ['id' => $blacklisted->id]));

        $response->assertRedirect(route('admin.blacklisted_devices'));
        $this->assertDatabaseHas('blacklisted_devices', ['id' => $blacklisted->id, 'deleted_at' => null]);
    }
}
