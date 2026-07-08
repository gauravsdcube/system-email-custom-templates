<?php

use humhub\libs\Html;
use humhub\modules\content\widgets\richtext\RichTextField;
use humhub\modules\systemEmailCustomizer\assets\SystemEmailCustomizerAsset;
use humhub\modules\systemEmailCustomizer\models\SystemEmailTemplate;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $template SystemEmailTemplate */
/* @var $definition array */

$this->title = Yii::t('SystemEmailCustomizerModule.base', 'Edit Email Template') . ': ' . $definition['title'];
SystemEmailCustomizerAsset::register($this);

$safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $template->template_key);
?>

<div class="panel panel-default sec-email-edit">
    <div class="panel-heading">
        <strong><?= Html::encode($definition['title']) ?></strong>
        <p class="help-block" style="margin:8px 0 0;"><?= Html::encode($definition['description']) ?></p>
    </div>

    <div class="panel-body">
        <div class="row">
            <div class="col-md-8">
                <?php $form = ActiveForm::begin([
                    'id' => 'sec-email-template-form',
                    'action' => ['edit', 'key' => $template->template_key],
                    'options' => ['data-pjax' => 0],
                ]); ?>

                <?= $form->field($template, 'template_key')->hiddenInput()->label(false) ?>
                <?= $form->field($template, 'id')->hiddenInput()->label(false) ?>

                <?= $form->field($template, 'subject')->textInput(['maxlength' => 255]) ?>

                <div class="sec-section">
                    <h5><?= Yii::t('SystemEmailCustomizerModule.base', 'Email header') ?></h5>
                    <p class="help-block"><?= Yii::t('SystemEmailCustomizerModule.base', 'Optional banner area for logos, titles, or branded headings.') ?></p>
                    <div class="row">
                        <div class="col-md-7">
                            <?= $form->field($template, 'header')->widget(RichTextField::class, [
                                'id' => 'sec_header_' . $safeKey,
                                'layout' => RichTextField::LAYOUT_BLOCK,
                                'preset' => 'full',
                                'pluginOptions' => ['maxHeight' => '180px'],
                                'backupInterval' => 0,
                            ])->label(false) ?>
                        </div>
                        <div class="col-md-5">
                            <?= $this->render('_colorFields', ['form' => $form, 'model' => $template, 'prefix' => 'header']) ?>
                        </div>
                    </div>
                </div>

                <div class="sec-section">
                    <h5><?= Yii::t('SystemEmailCustomizerModule.base', 'Email body') ?></h5>
                    <p class="help-block"><?= Yii::t('SystemEmailCustomizerModule.base', 'Main message content. Supports rich text, images, links, and tables.') ?></p>
                    <?= $form->field($template, 'body')->widget(RichTextField::class, [
                        'id' => 'sec_body_' . $safeKey,
                        'layout' => RichTextField::LAYOUT_BLOCK,
                        'preset' => 'full',
                        'pluginOptions' => ['maxHeight' => '420px'],
                        'backupInterval' => 0,
                    ])->label(false) ?>
                </div>

                <div class="sec-section">
                    <h5><?= Yii::t('SystemEmailCustomizerModule.base', 'Email footer') ?></h5>
                    <p class="help-block"><?= Yii::t('SystemEmailCustomizerModule.base', 'Optional footer for disclaimers, contact details, or social links.') ?></p>
                    <div class="row">
                        <div class="col-md-7">
                            <?= $form->field($template, 'footer')->widget(RichTextField::class, [
                                'id' => 'sec_footer_' . $safeKey,
                                'layout' => RichTextField::LAYOUT_BLOCK,
                                'preset' => 'full',
                                'pluginOptions' => ['maxHeight' => '180px'],
                                'backupInterval' => 0,
                            ])->label(false) ?>
                        </div>
                        <div class="col-md-5">
                            <?= $this->render('_colorFields', ['form' => $form, 'model' => $template, 'prefix' => 'footer']) ?>
                        </div>
                    </div>
                </div>

                <?= $form->field($template, 'is_active')->checkbox([
                    'label' => Yii::t('SystemEmailCustomizerModule.base', 'Use custom template'),
                ]) ?>

                <div class="form-group">
                    <?= Html::submitButton(Yii::t('SystemEmailCustomizerModule.base', 'Save template'), ['class' => 'btn btn-primary']) ?>
                    <?= Html::a(Yii::t('SystemEmailCustomizerModule.base', 'Preview'), ['preview', 'key' => $template->template_key], ['class' => 'btn btn-info', 'target' => '_blank']) ?>
                    <?= Html::a(Yii::t('SystemEmailCustomizerModule.base', 'Reset defaults'), ['reset', 'key' => $template->template_key], [
                        'class' => 'btn btn-default',
                        'data-method' => 'POST',
                        'data-confirm' => Yii::t('SystemEmailCustomizerModule.base', 'Are you sure you want to reset this template to the default content?'),
                    ]) ?>
                    <?= Html::a(Yii::t('SystemEmailCustomizerModule.base', 'Back to list'), ['index'], ['class' => 'btn btn-default']) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>

            <div class="col-md-4">
                <div class="panel panel-info sec-side-panel">
                    <div class="panel-heading">
                        <strong><?= Yii::t('SystemEmailCustomizerModule.base', 'About this email') ?></strong>
                    </div>
                    <div class="panel-body">
                        <p><strong><?= Yii::t('SystemEmailCustomizerModule.base', 'Purpose') ?></strong><br><?= Html::encode($definition['description']) ?></p>
                        <p><strong><?= Yii::t('SystemEmailCustomizerModule.base', 'Trigger') ?></strong><br><?= Html::encode($definition['trigger']) ?></p>

                        <hr>
                        <p><strong><?= Yii::t('SystemEmailCustomizerModule.base', 'Insert variables') ?></strong></p>
                        <p class="help-block"><?= Yii::t('SystemEmailCustomizerModule.base', 'Click a variable to copy it. Paste into the subject, header, body, or footer.') ?></p>
                        <div class="sec-variable-list">
                            <?php foreach ($definition['variables'] as $variable => $label): ?>
                                <button type="button" class="btn btn-xs btn-default sec-copy-variable" data-variable="<?= Html::encode('{' . $variable . '}') ?>" title="<?= Html::encode($label) ?>">
                                    <code>{<?= Html::encode($variable) ?>}</code>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <?= $this->render('_buttonBuilder', ['definition' => $definition, 'safeKey' => $safeKey]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script <?= Html::nonce() ?>>
$(function () {
    if (typeof sessionStorage === 'undefined') {
        return;
    }

    var backupKey = 'RichTextEditor.backup';
    var inputIds = [
        'sec_header_<?= $safeKey ?>_input',
        'sec_body_<?= $safeKey ?>_input',
        'sec_footer_<?= $safeKey ?>_input'
    ];

    try {
        var backup = JSON.parse(sessionStorage.getItem(backupKey) || '{}');
        inputIds.forEach(function (id) {
            delete backup[id];
        });
        if (Object.keys(backup).length) {
            sessionStorage.setItem(backupKey, JSON.stringify(backup));
        } else {
            sessionStorage.removeItem(backupKey);
        }
    } catch (e) {
        sessionStorage.removeItem(backupKey);
    }
});
</script>
