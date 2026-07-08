<?php
/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @author D Cube Consulting <info@dcubeconsulting.co.uk>
 */



use humhub\components\Application;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\systemEmailCustomizer\Events;
use yii\console\Application as ConsoleApplication;

return [
    'id' => 'system-email-customizer',
    'class' => humhub\modules\systemEmailCustomizer\Module::class,
    'namespace' => 'humhub\modules\systemEmailCustomizer',
    'events' => [
        ['class' => Application::class, 'event' => Application::EVENT_BEFORE_REQUEST, 'callback' => [Events::class, 'onBeforeRequest']],
        ['class' => ConsoleApplication::class, 'event' => ConsoleApplication::EVENT_BEFORE_REQUEST, 'callback' => [Events::class, 'onBeforeRequest']],
        ['class' => AdminMenu::class, 'event' => AdminMenu::EVENT_INIT, 'callback' => [Events::class, 'onAdminMenuInit']],
    ],
];
