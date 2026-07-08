<?php
/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @author D Cube Consulting <info@dcubeconsulting.co.uk>
 */



namespace humhub\modules\systemEmailCustomizer\controllers;

use humhub\components\access\ControllerAccess;
use humhub\modules\admin\components\Controller;
use humhub\modules\systemEmailCustomizer\components\EmailDefinitionRegistry;
use humhub\modules\systemEmailCustomizer\components\TemplateProcessor;
use humhub\modules\systemEmailCustomizer\components\VariableExtractor;
use humhub\modules\systemEmailCustomizer\models\SettingsForm;
use humhub\modules\systemEmailCustomizer\models\SystemEmailTemplate;
use humhub\modules\systemEmailCustomizer\Module;
use humhub\modules\systemEmailCustomizer\permissions\ManageSystemEmails;
use Yii;
use yii\web\NotFoundHttpException;

class AdminController extends Controller
{
    /**
     * @inheritdoc
     */
    public $adminOnly = false;

    public function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            ['checkCanManageEmails'],
        ];
    }

    public function checkCanManageEmails($rule, $access): bool
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

        /** @var Module|null $module */
        $module = Yii::$app->getModule('system-email-customizer');
        if ($module instanceof Module && $module->isAuthorizedGroupMember()) {
            return true;
        }

        $access->code = 403;
        $access->reason = Yii::t('SystemEmailCustomizerModule.base', 'You do not have permission to manage system emails.');
        return false;
    }

    public function actionIndex()
    {
        return $this->render('index', [
            'groupedDefinitions' => EmailDefinitionRegistry::getDefinitionsByCategory(),
            'templates' => $this->getTemplateMap(),
            'categoryLabels' => $this->getCategoryLabels(),
        ]);
    }

    public function actionEdit($key = null)
    {
        if ($key === null) {
            $key = Yii::$app->request->get('key')
                ?? (Yii::$app->request->post('SystemEmailTemplate')['template_key'] ?? null);
        }

        if ($key === null || $key === '') {
            throw new NotFoundHttpException('Email definition not found.');
        }

        $definition = EmailDefinitionRegistry::getDefinition($key);
        if ($definition === null) {
            throw new NotFoundHttpException('Email definition not found.');
        }

        $template = SystemEmailTemplate::findByKey($key) ?? SystemEmailTemplate::createFromDefinition($key);

        if (Yii::$app->request->isPost && $template->load(Yii::$app->request->post())) {
            $template->template_key = $key;
            if ($template->save()) {
                $this->view->saved();
                return $this->redirect(['edit', 'key' => $key]);
            }

            $this->view->error(Yii::t('SystemEmailCustomizerModule.base', 'Could not save template: {errors}', [
                'errors' => implode(' ', $template->getFirstErrors()),
            ]));
        }

        return $this->render('edit', [
            'template' => $template,
            'definition' => $definition,
        ]);
    }

    public function actionPreview($key)
    {
        $definition = EmailDefinitionRegistry::getDefinition($key);
        if ($definition === null) {
            throw new NotFoundHttpException('Email definition not found.');
        }

        $template = SystemEmailTemplate::findByKey($key) ?? SystemEmailTemplate::createFromDefinition($key);
        $variables = VariableExtractor::getPreviewVariables($key);
        $processed = (new TemplateProcessor())->process($template, $variables, Yii::$app->user->identity, true);

        return $this->renderPartial('preview', [
            'template' => $template,
            'definition' => $definition,
            'processed' => $processed,
            'variables' => $variables,
        ]);
    }

    public function actionToggle($key)
    {
        $definition = EmailDefinitionRegistry::getDefinition($key);
        if ($definition === null) {
            throw new NotFoundHttpException('Email definition not found.');
        }

        $template = SystemEmailTemplate::findByKey($key) ?? SystemEmailTemplate::createFromDefinition($key);
        $template->is_active = $template->is_active ? 0 : 1;

        if ($template->save()) {
            $status = $template->is_active
                ? Yii::t('SystemEmailCustomizerModule.base', 'enabled')
                : Yii::t('SystemEmailCustomizerModule.base', 'disabled');
            $this->view->success(Yii::t('SystemEmailCustomizerModule.base', 'Custom template {status}. Default email will be used when disabled.', ['status' => $status]));
        } else {
            $this->view->error(Yii::t('SystemEmailCustomizerModule.base', 'Could not update template status.'));
        }

        return $this->redirect(['index']);
    }

    public function actionReset($key)
    {
        $definition = EmailDefinitionRegistry::getDefinition($key);
        if ($definition === null) {
            throw new NotFoundHttpException('Email definition not found.');
        }

        $template = SystemEmailTemplate::findByKey($key) ?? SystemEmailTemplate::createFromDefinition($key);
        $defaults = $definition['defaults'];
        $template->subject = $defaults['subject'] ?? '';
        $template->header = $defaults['header'] ?? '';
        $template->body = $defaults['body'] ?? '';
        $template->footer = $defaults['footer'] ?? '';
        $template->header_bg_color = $defaults['header_bg_color'] ?? '#f0f4f8';
        $template->footer_bg_color = $defaults['footer_bg_color'] ?? '#f8f9fa';
        $template->header_font_color = $defaults['header_font_color'] ?? '#1f2937';
        $template->footer_font_color = $defaults['footer_font_color'] ?? '#6b7280';

        if ($template->save()) {
            $this->view->success(Yii::t('SystemEmailCustomizerModule.base', 'Template reset to defaults.'));
        } else {
            $this->view->error(Yii::t('SystemEmailCustomizerModule.base', 'Could not reset template.'));
        }

        return $this->redirect(['edit', 'key' => $key]);
    }

    public function actionSettings()
    {
        $model = new SettingsForm();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
            return $this->refresh();
        }

        return $this->render('settings', ['model' => $model]);
    }

    /**
     * @return array<string, SystemEmailTemplate>
     */
    private function getTemplateMap(): array
    {
        $map = [];
        foreach (SystemEmailTemplate::find()->each() as $template) {
            $map[$template->template_key] = $template;
        }

        return $map;
    }

    /**
     * @return array<string, string>
     */
    private function getCategoryLabels(): array
    {
        return [
            'admin' => Yii::t('SystemEmailCustomizerModule.base', 'Administration'),
            'user' => Yii::t('SystemEmailCustomizerModule.base', 'User account'),
            'notifications' => Yii::t('SystemEmailCustomizerModule.base', 'Notifications'),
            'activity' => Yii::t('SystemEmailCustomizerModule.base', 'Activity'),
            'modules' => Yii::t('SystemEmailCustomizerModule.base', 'Modules'),
            'general' => Yii::t('SystemEmailCustomizerModule.base', 'General'),
        ];
    }
}
