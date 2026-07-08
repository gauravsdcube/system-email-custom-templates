<?php
/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @author D Cube Consulting <info@dcubeconsulting.co.uk>
 */



namespace humhub\modules\systemEmailCustomizer;

use humhub\helpers\ControllerHelper;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\ui\menu\MenuLink;
use Yii;

class Events
{
    /**
     * Bootstrap the module early so the mail interceptor is active for all outgoing mail.
     */
    public static function onBeforeRequest($event = null): void
    {
        if (!Yii::$app->hasModule('system-email-customizer')) {
            return;
        }

        Yii::$app->getModule('system-email-customizer');
    }

    public static function onAdminMenuInit($event): void
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('system-email-customizer');
        if (!$module->canManageEmails()) {
            return;
        }

        /** @var AdminMenu $menu */
        $menu = $event->sender;
        $menu->addEntry(new MenuLink([
            'label' => Yii::t('SystemEmailCustomizerModule.base', 'System Emails'),
            'url' => ['/system-email-customizer/admin/index'],
            'icon' => 'envelope-o',
            'sortOrder' => 545,
            'isActive' => ControllerHelper::isActivePath('system-email-customizer'),
        ]));
    }
}
