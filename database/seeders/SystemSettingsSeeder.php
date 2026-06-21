<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemSetting::setValue('office_start_time', '09:00');
        SystemSetting::setValue('office_end_time', '18:00');
        SystemSetting::setValue('late_threshold_minutes', '15');
    }
}
