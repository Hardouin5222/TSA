<?php

namespace App\Pro;

final class Pro
{
    public static function isPro()
    {
        $theme = env('BC_ACTIVE_THEME', defined('BC_INIT_THEME') ? BC_INIT_THEME : 'BC');

        // Always enable for BC now
        if (strtolower($theme) == 'bc') return true;
        return defined('BC_IS_PRO') and BC_IS_PRO;
    }

    public static function isEnable()
    {
        return config('pro.enable') && strtolower(config('bc.active_theme')) == 'bc';
    }

}
