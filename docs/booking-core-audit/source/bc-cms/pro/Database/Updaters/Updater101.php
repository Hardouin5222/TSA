<?php

namespace Pro\Database\Updaters;

use Illuminate\Support\Facades\Artisan;

class Updater101
{

    public static function run()
    {

        $version = '1.1';
        if (version_compare(setting_item('bc_pro_update_to_101'), $version, '>=')) return;

        Artisan::call('migrate', [
            '--force' => true,
        ]);

        setting_update_item('bc_pro_update_to_101', $version);
    }
}
