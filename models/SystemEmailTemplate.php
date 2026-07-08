<?php
/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @author D Cube Consulting <info@dcubeconsulting.co.uk>
 */



namespace humhub\modules\systemEmailCustomizer\models;

use humhub\components\ActiveRecord;
use humhub\modules\systemEmailCustomizer\components\EmailDefinitionRegistry;
use Yii;

/**
 * @property int $id
 * @property string $template_key
 * @property string $subject
 * @property string|null $header
 * @property string $body
 * @property string|null $footer
 * @property string|null $header_bg_color
 * @property string|null $footer_bg_color
 * @property string|null $header_font_color
 * @property string|null $footer_font_color
 * @property int $is_active
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class SystemEmailTemplate extends ActiveRecord
{
    public static function tableName()
    {
        return 'system_email_template';
    }

    public function rules()
    {
        return [
            [['template_key', 'subject', 'body'], 'required'],
            [['header', 'body', 'footer'], 'string'],
            [['is_active'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['template_key'], 'string', 'max' => 100],
            [['subject'], 'string', 'max' => 255],
            [['header_bg_color', 'footer_bg_color', 'header_font_color', 'footer_font_color'], 'string', 'max' => 7],
            [['template_key'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'template_key' => Yii::t('SystemEmailCustomizerModule.base', 'Email'),
            'subject' => Yii::t('SystemEmailCustomizerModule.base', 'Subject'),
            'header' => Yii::t('SystemEmailCustomizerModule.base', 'Header / Banner'),
            'body' => Yii::t('SystemEmailCustomizerModule.base', 'Body'),
            'footer' => Yii::t('SystemEmailCustomizerModule.base', 'Footer'),
            'header_bg_color' => Yii::t('SystemEmailCustomizerModule.base', 'Header background'),
            'footer_bg_color' => Yii::t('SystemEmailCustomizerModule.base', 'Footer background'),
            'header_font_color' => Yii::t('SystemEmailCustomizerModule.base', 'Header text color'),
            'footer_font_color' => Yii::t('SystemEmailCustomizerModule.base', 'Footer text color'),
            'is_active' => Yii::t('SystemEmailCustomizerModule.base', 'Use custom template'),
        ];
    }

    public static function findByKey(string $key): ?self
    {
        return static::findOne(['template_key' => $key]);
    }

    public function getDefinition(): ?array
    {
        return EmailDefinitionRegistry::getDefinition($this->template_key);
    }

    public function getTitle(): string
    {
        return $this->getDefinition()['title'] ?? $this->template_key;
    }

    public function getDescription(): string
    {
        return $this->getDefinition()['description'] ?? '';
    }

    public function getTriggerDescription(): string
    {
        return $this->getDefinition()['trigger'] ?? '';
    }

    /**
     * @return array<string, string>
     */
    public function getAvailableVariables(): array
    {
        return $this->getDefinition()['variables'] ?? [];
    }

    public static function createFromDefinition(string $key): self
    {
        $definition = EmailDefinitionRegistry::getDefinition($key);
        if ($definition === null) {
            throw new \InvalidArgumentException('Unknown email definition: ' . $key);
        }

        $defaults = $definition['defaults'];

        $template = new self();
        $template->template_key = $key;
        $template->subject = $defaults['subject'] ?? '';
        $template->header = $defaults['header'] ?? '';
        $template->body = $defaults['body'] ?? '';
        $template->footer = $defaults['footer'] ?? '';
        $template->header_bg_color = $defaults['header_bg_color'] ?? '#f0f4f8';
        $template->footer_bg_color = $defaults['footer_bg_color'] ?? '#f8f9fa';
        $template->header_font_color = $defaults['header_font_color'] ?? '#1f2937';
        $template->footer_font_color = $defaults['footer_font_color'] ?? '#6b7280';
        $template->is_active = 0;

        return $template;
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        if ($insert && empty($this->created_at)) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;

        return true;
    }
}
