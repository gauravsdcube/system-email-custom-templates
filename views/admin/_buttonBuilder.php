<?php

use humhub\libs\Html;

/* @var $definition array */
/* @var $safeKey string */
?>

<hr>
<div class="sec-button-builder" data-safe-key="<?= Html::encode($safeKey) ?>">
    <p><strong><?= Yii::t('SystemEmailCustomizerModule.base', 'Email buttons') ?></strong></p>
    <p class="help-block"><?= Yii::t('SystemEmailCustomizerModule.base', 'Add call-to-action buttons that render like the default HumHub emails.') ?></p>

    <div class="form-group">
        <label class="control-label" for="sec-button-label"><?= Yii::t('SystemEmailCustomizerModule.base', 'Button label') ?></label>
        <input type="text" id="sec-button-label" class="form-control input-sm" value="<?= Html::encode(Yii::t('SystemEmailCustomizerModule.base', 'Continue')) ?>">
    </div>

    <div class="form-group">
        <label class="control-label" for="sec-button-url"><?= Yii::t('SystemEmailCustomizerModule.base', 'Button link') ?></label>
        <select id="sec-button-url" class="form-control input-sm">
            <?php foreach ($definition['variables'] as $variable => $label): ?>
                <option value="<?= Html::encode('{' . $variable . '}') ?>"><?= Html::encode($label) ?> ({<?= Html::encode($variable) ?>})</option>
            <?php endforeach; ?>
            <option value="__custom__"><?= Yii::t('SystemEmailCustomizerModule.base', 'Custom URL...') ?></option>
        </select>
        <input type="url" id="sec-button-custom-url" class="form-control input-sm sec-button-custom-url" placeholder="https://example.com" style="display:none;margin-top:6px;">
    </div>

    <div class="form-group">
        <label class="control-label" for="sec-button-target"><?= Yii::t('SystemEmailCustomizerModule.base', 'Insert into') ?></label>
        <select id="sec-button-target" class="form-control input-sm">
            <option value="body"><?= Yii::t('SystemEmailCustomizerModule.base', 'Body') ?></option>
            <option value="header"><?= Yii::t('SystemEmailCustomizerModule.base', 'Header / Banner') ?></option>
            <option value="footer"><?= Yii::t('SystemEmailCustomizerModule.base', 'Footer') ?></option>
        </select>
    </div>

    <button type="button" class="btn btn-sm btn-primary sec-insert-button">
        <?= Yii::t('SystemEmailCustomizerModule.base', 'Insert button') ?>
    </button>

    <p class="help-block sec-button-syntax" style="margin-top:10px;">
        <?= Yii::t('SystemEmailCustomizerModule.base', 'Syntax') ?>:
        <code>{button:<?= Yii::t('SystemEmailCustomizerModule.base', 'Label') ?>|{registration_url}}</code>
    </p>
</div>
