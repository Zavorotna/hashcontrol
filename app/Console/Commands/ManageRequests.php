<?php

namespace App\Console\Commands;

use App\Models\MqttMessage;
use Illuminate\Console\Command;

class ManageRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'requests {action} {device_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage MQTT requests: list, delete';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                $this->listRequests();
                break;
            case 'delete':
                $this->deleteRequest();
                break;
            default:
                $this->error('Invalid action. Use list or delete.');
        }
    }

    private function listRequests()
    {
        $requests = MqttMessage::all();
        if ($requests->isEmpty()) {
            $this->info('No requests.');
            return;
        }

        $this->table(['ID', 'Reader ID', 'Action', 'Data', 'Created At'], $requests->map(function ($request) {
            return [
                $request->id,
                $request->device_id,
                $request->action,
                $request->data,
                $request->created_at,
            ];
        }));
    }

    private function deleteRequest()
    {
        $readerId = $this->argument('device_id');
        if (!$readerId) {
            $this->error('Reader ID is required for delete action.');
            return;
        }

        $request = MqttMessage::where('device_id', $readerId)->first();
        if (!$request) {
            $this->error("Request for device {$readerId} not found.");
            return;
        }

        $request->delete();
        $this->info("Request for device {$readerId} deleted. Device can re-register.");
    }
}
