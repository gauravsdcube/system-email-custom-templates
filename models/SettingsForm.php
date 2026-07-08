<?php
/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @author D Cube Consulting <info@dcubeconsulting.co.uk>
 */



namespace humhub\modules\systemEmailCustomizer\models;

use humhub\modules\systemEmailCustomizer\Module;
use humhub\modules\user\models\Group;
use Yii;
use yii\base\Model;

class SettingsForm extends Model
{
    public $authorizedGroupName = '';

    public function rules()
    {
        return [
            [['authorizedGroupName'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'authorizedGroupName' => Yii::t('SystemEmailCustomizerModule.base', 'Authorized group'),
        ];
    }

    public function init()
    {
        parent::init();
        /** @var Module $module */
        $module = Yii::$app->getModule('system-email-customizer');
        $this->authorizedGroupName = (string)$module->settings->get(Module::SETTING_AUTHORIZED_GROUP, '');
    }

  /**
   * @return array<int, string>
   */
    public function getGroupOptions(): array
    {
        $options = ['' => Yii::t('SystemEmailCustomizerModule.base', 'None (permission only)')];
        foreach (Group::find()->orderBy(['name' => SORT_ASC])->all() as $group) {
            $options[$group->name] = $group->name;
        }

        return $options;
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        /** @var Module $module */
        $module = Yii::$app->getModule('system-email-customizer');
        $module->settings->set(Module::SETTING_AUTHORIZED_GROUP, trim($this->authorizedGroupName));

        return true;
    }
}
