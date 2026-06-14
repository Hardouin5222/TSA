<?php

namespace Pro\Support;

use Modules\Core\Abstracts\BaseSettingsClass;

class SettingClass extends BaseSettingsClass
{

    public static function getSettingPages()
    {
        return [
            'support' => [
                'id'       => 'support',
                'title'    => __("Support Settings"),
                'position' => 100,
                'view'     => "Support::admin.settings.support",
                "keys"     => [
                    'support_ticket_assign_role',
                    'support_ticket_view_type',
                ]
            ]
        ];
    }
}
