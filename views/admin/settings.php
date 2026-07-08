<?php

use humhub\libs\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model humhub\modules\systemEmailCustomizer\models\SettingsForm */

$this->title = Yii::t('SystemEmailCustomizerModule.base', 'Settings');
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <strong><?= Html::encode($this->title) ?></strong>
        <p class="help-block" style="margin:8px 0 0;">
            <?= Yii::t('SystemEmailCustomizerModule.base', 'Choose which user group may manage system emails in addition to users with the dedicated permission.') ?>
        </p>
    </div>
    <div class="panel-body">
        <?php $form = ActiveForm::begin(['options' => ['data-pjax' => 0]]); ?>

        <?= $form->field($model, 'authorizedGroupName')->dropDownList($model->getGroupOptions()) ?>

        <div class="alert alert-info">
            <?= Yii::t('SystemEmailCustomizerModule.base', 'Edit, preview, and enable custom templates for system-generated emails.') ?>
            <br>
            <?= Yii::t('SystemEmailCustomizerModule.base', 'Assign the "Manage system emails" permission to any group under Administration → Groups.') ?>
        </div>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('SystemEmailCustomizerModule.base', 'Save settings'), ['class' => 'btn btn-primary']) ?>
            <?= Html::a(Yii::t('SystemEmailCustomizerModule.base', 'Back to list'), ['index'], ['class' => 'btn btn-default']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
