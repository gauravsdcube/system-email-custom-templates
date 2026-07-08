<?php
/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @author D Cube Consulting <info@dcubeconsulting.co.uk>
 * @package humhub\modules\systemEmailCustomizer
 */



namespace humhub\modules\systemEmailCustomizer;

use humhub\components\Module as BaseModule;
use humhub\modules\systemEmailCustomizer\components\MailInterceptor;
use humhub\modules\systemEmailCustomizer\models\SystemEmailTemplate;
use humhub\modules\systemEmailCustomizer\permissions\ManageSystemEmails;
use humhub\modules\user\models\Group;
use Yii;
use yii\helpers\Url;

class Module extends BaseModule
{
    public const VERSION = '1.0.0';

    public const SETTING_AUTHORIZED_GROUP = 'authorizedGroupName';

    public $icon = 'envelope-o';

    public $resourcesPath = 'resources';

  /**
   * @inheritdoc
   */
    public function init()
    {
        parent::init();
        Yii::setAlias('@system-email-customizer', $this->getBasePath());
        $this->registerMailInterceptor();
    }

  /**
   * @inheritdoc
   */
    public function getName()
    {
        return Yii::t('SystemEmailCustomizerModule.base', 'System Email Customizer');
    }

  /**
   * @inheritdoc
   */
    public function getDescription()
    {
        return Yii::t('SystemEmailCustomizerModule.base', 'Customize system-generated emails with rich text, banners, and footers.');
    }

  /**
   * @inheritdoc
   */
    public function getConfigUrl()
    {
        return Url::to(['/system-email-customizer/admin/index']);
    }

  /**
   * @inheritdoc
   */
    public function getPermissions($contentContainer = null)
    {
        if ($contentContainer !== null) {
            return [];
        }

        return [
            new ManageSystemEmails(),
        ];
    }

  /**
   * Check whether the current user may manage system emails.
   */
    public function canManageEmails(): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        if (Yii::$app->user->isAdmin()) {
            return true;
        }

        if (Yii::$app->user->can(ManageSystemEmails::class)) {
            return true;
        }

        return $this->isAuthorizedGroupMember();
    }

  /**
   * Whether the current user belongs to the configured authorized group.
   */
    public function isAuthorizedGroupMember(): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        $groupName = trim((string)$this->settings->get(self::SETTING_AUTHORIZED_GROUP, ''));
        if ($groupName === '') {
            return false;
        }

        $group = Group::findOne(['name' => $groupName]);
        if ($group === null) {
            return false;
        }

        return $group->isMember(Yii::$app->user->identity);
    }

  /**
   * Replace the application mailer with the interceptor.
   */
    protected function registerMailInterceptor(): void
    {
        if (!Yii::$app->has('mailer')) {
            return;
        }

        $mailer = Yii::$app->get('mailer', false);
        if ($mailer instanceof MailInterceptor) {
            return;
        }

        $config = Yii::$app->components['mailer'] ?? ['class' => MailInterceptor::class];
        $config['class'] = MailInterceptor::class;
        Yii::$app->set('mailer', $config);
    }

  /**
   * @inheritdoc
   */
    public function disable()
    {
        foreach (SystemEmailTemplate::find()->each() as $template) {
            $template->delete();
        }

        parent::disable();
    }
}
