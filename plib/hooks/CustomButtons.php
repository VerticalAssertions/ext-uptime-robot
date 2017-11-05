<?php
// Copyright 1999-2017. Plesk International GmbH.
class Modules_UptimeRobot_CustomButtons extends pm_Hook_CustomButtons
{
    public function getButtons()
    {
        $buttons = [[
            'place' => self::PLACE_COMMON,
            'order' => 3,
            'title' => pm_Locale::lmsg('UptimeRobotButtonTitle'),
            'description' => pm_Locale::lmsg('UptimeRobotButtonDescription'),
            'icon' => pm_Context::getBaseUrl() . 'images/16x16_mono.png',
            'link' => pm_Context::getActionUrl('index'),
        ], [
            'place' => self::PLACE_ADMIN_TOOLS_AND_SETTINGS,
            'title' => pm_Locale::lmsg('UptimeRobotButtonTitle'),
            'section' => 'statisticsPanel-tools-list',
            'order' => 5,
            'description' => pm_Locale::lmsg('UptimeRobotButtonDescription'),
            'link' => pm_Context::getActionUrl('index'),
        ]];
        return $buttons;
    }
}