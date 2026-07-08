<?php

use humhub\libs\Html;

/* @var $this yii\web\View */
/* @var $template humhub\modules\systemEmailCustomizer\models\SystemEmailTemplate */
/* @var $definition array */
/* @var $processed array */
/* @var $variables array */

$this->title = Yii::t('SystemEmailCustomizerModule.base', 'Preview') . ': ' . $definition['title'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Html::encode($this->title) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #eef2f6; margin: 0; padding: 24px; color: #1f2937; }
        .preview-shell { max-width: 900px; margin: 0 auto; }
        .preview-meta { background: #fff; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .preview-meta h1 { font-size: 18px; margin: 0 0 8px; }
        .preview-meta .subject { font-size: 14px; color: #4b5563; }
        .preview-frame { background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .preview-variables { margin-top: 16px; background: #fff; border-radius: 8px; padding: 16px 20px; font-size: 13px; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
<div class="preview-shell">
    <div class="preview-meta">
        <h1><?= Html::encode($definition['title']) ?></h1>
        <div class="subject"><strong><?= Yii::t('SystemEmailCustomizerModule.base', 'Subject') ?>:</strong> <?= Html::encode($processed['subject']) ?></div>
    </div>

    <div class="preview-frame">
        <?= $processed['body'] ?>
    </div>

    <div class="preview-variables">
        <strong><?= Yii::t('SystemEmailCustomizerModule.base', 'Available variables') ?></strong>
        <ul>
            <?php foreach ($definition['variables'] as $variable => $label): ?>
                <li><code>{<?= Html::encode($variable) ?>}</code> — <?= Html::encode($label) ?>: <?= Html::encode($variables[$variable] ?? '') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
</body>
</html>
