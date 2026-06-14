<?php

namespace Pro;

use Pro\Database\Updaters\Updater101;

class Updater extends \Illuminate\Support\ServiceProvider
{

    public function boot()
    {
        if (!$this->app->runningInConsole()) {
            Updater101::run();
        }
    }
}
