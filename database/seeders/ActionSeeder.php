<?php

namespace Database\Seeders;

use App\Models\Action;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ActionSeeder extends Seeder
{
    /**
     * Seed base actions used across all demo scenarios.
     * All actions are created with updateOrCreate so the seeder is idempotent.
     */
    public function run(): void
    {
        $actions = [
            ['name' => '1',  'title' => 'Entry scan',        'description' => 'NFC/badge card scanned at entry reader'],
            ['name' => '2',  'title' => 'Exit scan',         'description' => 'NFC/badge card scanned at exit reader'],
            ['name' => '3',  'title' => 'Arrival',           'description' => 'Worker arrival scan'],
            ['name' => '4',  'title' => 'Departure',         'description' => 'Worker departure scan'],
            ['name' => '5',  'title' => 'Generator state',   'description' => 'Generator ON/OFF event (data = "on" or "off")'],
            ['name' => '7',  'title' => 'Temperature',       'description' => 'Temperature sensor reading (data = numeric value)'],
            ['name' => '8',  'title' => 'Ventilation state', 'description' => 'Ventilation / exhaust unit ON/OFF event'],
            ['name' => '9',  'title' => 'Compressor state',  'description' => 'Air compressor ON/OFF event'],
            ['name' => '10', 'title' => 'Fridge state',      'description' => 'Fridge / cold-storage controller ON/OFF event'],
            ['name' => '11', 'title' => 'Section access',    'description' => 'Badge scan at warehouse section entrance'],
            ['name' => '14', 'title' => 'Counter',           'description' => 'Pulse counter reading (data = cumulative value)'],
        ];

        foreach ($actions as $data) {
            Action::updateOrCreate(['name' => $data['name']], $data);
        }
    }
}
