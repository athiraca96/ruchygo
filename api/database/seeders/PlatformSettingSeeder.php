<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'platform_fee_percentage' => '5',
            'shipping_fee' => '50',
            'free_shipping_threshold' => '500',
            'return_window_days' => '7',
            'replacement_window_days' => '7',
        ];

        foreach ($settings as $key => $value) {
            PlatformSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $this->command->info('Platform settings seeded successfully.');
    }
}
