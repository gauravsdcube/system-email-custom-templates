<?php

use humhub\libs\Html;
use humhub\modules\systemEmailCustomizer\assets\SystemEmailCustomizerAsset;
use humhub\modules\systemEmailCustomizer\models\SystemEmailTemplate;
use humhub\widgets\Button;

/* @var $this yii\web\View */
/* @var $groupedDefinitions array<string, array<string, array>> */
/* @var $templates array<string, SystemEmailTemplate> */
/* @var $categoryLabels array<string, string> */

$this->title = Yii::t('SystemEmailCustomizerModule.base', 'System Email Templates');
SystemEmailCustomizerAsset::register($this);
?>

<div class="panel panel-default sec-email-index">
    <div class="panel-heading">
        <div class="pull-right">
            <?= Html::a(
                '<i class="fa fa-cog"></i> ' . Yii::t('SystemEmailCustomizerModule.base', 'Settings'),
                ['settings'],
                ['class' => 'btn btn-sm btn-default']
            ) ?>
        </div>
        <strong><?= Html::encode($this->title) ?></strong>
        <p class="help-block" style="margin:8px 0 0;">
            <?= Yii::t('SystemEmailCustomizerModule.base', 'Customize how system emails look and read. Disable any template to fall back to the default HumHub email.') ?>
        </p>
    </div>

    <div class="panel-body">
        <?php foreach ($groupedDefinitions as $category => $definitions): ?>
            <div class="sec-category-block">
                <h4 class="sec-category-title">
                    <?= Html::encode($categoryLabels[$category] ?? ucfirst($category)) ?>
                    <span class="badge"><?= count($definitions) ?></span>
                </h4>

                <div class="table-responsive">
                    <table class="table table-hover sec-email-table">
                        <thead>
                            <tr>
                                <th style="width:34%;"><?= Yii::t('SystemEmailCustomizerModule.base', 'Email') ?></th>
                                <th style="width:28%;"><?= Yii::t('SystemEmailCustomizerModule.base', 'When sent') ?></th>
                                <th style="width:14%;"><?= Yii::t('SystemEmailCustomizerModule.base', 'Use custom template') ?></th>
                                <th style="width:24%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($definitions as $key => $definition): ?>
                                <?php $template = $templates[$key] ?? null; ?>
                                <tr>
                                    <td>
                                        <div class="sec-email-title"><?= Html::encode($definition['title']) ?></div>
                                        <div class="text-muted sec-email-description"><?= Html::encode($definition['description']) ?></div>
                                    </td>
                                    <td>
                                        <div class="sec-trigger"><?= Html::encode($definition['trigger']) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($template && $template->is_active): ?>
                                            <span class="label label-success"><?= Yii::t('SystemEmailCustomizerModule.base', 'Custom template active') ?></span>
                                        <?php elseif ($template): ?>
                                            <span class="label label-warning"><?= Yii::t('SystemEmailCustomizerModule.base', 'Using default email') ?></span>
                                        <?php else: ?>
                                            <span class="label label-default"><?= Yii::t('SystemEmailCustomizerModule.base', 'Not configured') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right">
                                        <div class="btn-group">
                                            <?= Html::a(
                                                '<i class="fa fa-pencil"></i>',
                                                ['edit', 'key' => $key],
                                                ['class' => 'btn btn-sm btn-primary', 'title' => Yii::t('SystemEmailCustomizerModule.base', 'Edit template')]
                                            ) ?>
                                            <?= Html::a(
                                                '<i class="fa fa-eye"></i>',
                                                ['preview', 'key' => $key],
                                                ['class' => 'btn btn-sm btn-info sec-preview-link', 'title' => Yii::t('SystemEmailCustomizerModule.base', 'Preview'), 'target' => '_blank']
                                            ) ?>
                                            <?= Html::a(
                                                '<i class="fa fa-power-off"></i>',
                                                ['toggle', 'key' => $key],
                                                [
                                                    'class' => 'btn btn-sm ' . (($template && $template->is_active) ? 'btn-warning' : 'btn-success'),
                                                    'title' => ($template && $template->is_active)
                                                        ? Yii::t('SystemEmailCustomizerModule.base', 'Disable custom')
                                                        : Yii::t('SystemEmailCustomizerModule.base', 'Enable custom'),
                                                    'data-method' => 'POST',
                                                    'data-confirm' => Yii::t('SystemEmailCustomizerModule.base', 'Are you sure you want to toggle this template?'),
                                                ]
                                            ) ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-body">
        <?= Button::defaultType(Yii::t('SystemEmailCustomizerModule.base', 'Back to list'))
            ->link(['/admin/module/list'])
            ->icon('arrow-left') ?>
    </div>
</div>
