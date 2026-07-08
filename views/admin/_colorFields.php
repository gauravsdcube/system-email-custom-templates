<?php

use yii\widgets\ActiveForm;

/* @var $form ActiveForm */
/* @var $model humhub\modules\systemEmailCustomizer\models\SystemEmailTemplate */
/* @var $prefix string */
?>

<div class="sec-color-group">
    <?= $form->field($model, $prefix . '_bg_color')->input('color', [
        'class' => 'form-control sec-color-picker',
    ]) ?>
    <?= $form->field($model, $prefix . '_font_color')->input('color', [
        'class' => 'form-control sec-color-picker',
    ]) ?>
</div>
