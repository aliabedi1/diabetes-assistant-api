<?php

namespace Database\Seeders;

use App\Models\Medicine;
use Illuminate\Database\Seeder;

class MedicineSeeder extends Seeder
{
    public function run(): void
    {
        $globals = [
            ['name_en' => 'Metformin', 'name_fa' => 'متفورمین'],
            ['name_en' => 'Insulin',   'name_fa' => 'انسولین'],
            ['name_en' => 'Aspirin',   'name_fa' => 'آسپرین'],
        ];

        foreach ($globals as $data) {
            Medicine::firstOrCreate(
                ['user_id' => null, 'name_en' => $data['name_en']],
                ['name_fa' => $data['name_fa'], 'is_global' => true]
            );
        }
    }
}
