<?php
/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @author D Cube Consulting <info@dcubeconsulting.co.uk>
 */



namespace humhub\modules\systemEmailCustomizer\permissions;

use humhub\modules\admin\components\BaseAdminPermission;
use Yii;

class ManageSystemEmails extends BaseAdminPermission
{
    /**
     * @inheritdoc
     */
    protected $moduleId = 'system-email-customizer';

    /**
     * @inheritdoc
     */
    protected $defaultAllowedGroups = [];

    /**
     * @inheritdoc
     */
    protected $fixedGroups = [];

    public function getTitle()
    {
        return Yii::t('SystemEmailCustomizerModule.base', 'Manage system emails');
    }

    public function getDescription()
    {
        return Yii::t('SystemEmailCustomizerModule.base', 'Edit, preview, and enable custom templates for system-generated emails.');
    }
}
