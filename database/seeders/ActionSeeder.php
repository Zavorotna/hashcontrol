<?php

namespace Database\Seeders;

use App\Models\Action;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ActionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Action::create([
            'name' => '1',
            'description' => 'State change - start',
        ]);

        Action::create([
            'name' => '2',
            'description' => 'State change - end',
        ]);

        Action::create([
            'name' => '3',
            'description' => 'Generator action',
        ]);
    }
}
