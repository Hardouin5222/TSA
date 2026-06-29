<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TsaCheckoutSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'booking_guest_checkout' => '1',
            'booking_enable_guest_checkout' => '1',
            'enable_guest_checkout' => '1',
            'guest_checkout' => '1',
            'booking_enable_registration' => '1',
            'user_enable_registration' => '1',
            'enable_registration' => '1',
        ];

        foreach ($settings as $name => $val) {
            DB::table('core_settings')->updateOrInsert(
                ['name' => $name],
                [
                    'val' => $val,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
