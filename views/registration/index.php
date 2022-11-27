<?php

declare(strict_types=1);

use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use omarinina\domain\models\Cities;

/** @var yii\web\View $this */
/** @var omarinina\infrastructure\models\form\RegistrationForm $model */
/** @var omarinina\domain\models\Cities $cities */
/** @var array $userData */

if ($userData && array_key_exists('city', $userData)) {
    $idCity = Cities::findOne(['name' => $userData['city']['title']])->id;
    if ($idCity) {
        $selectedCity = $idCity;
        $valueCity = ['selected' => 'selected'];
        $promt = null;
        $check = null;
    }
} else {
    $selectedCity = null;
    $valueCity = null;
    $promt = 'prompt';
    $check = '-выбрать-';
}


?>
<div class="container container--registration">
    <div class="center-block">
        <div class="registration-form regular-form">
            <?php $form = ActiveForm::begin([
                'id' => 'registration-form',
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'control-label'],
                    'errorOptions' => ['tag' => 'span', 'class' => 'help-block']
                ]
            ])
?>
                <h3 class="head-main head-task">Регистрация нового пользователя</h3>
                <?= $form->field($model, 'name', ['options' => [
                    'class' => 'form-group',
                    ]])
                    ->textInput(['placeholder' => 'Иван Иванов',
                        'value' => $userData ?
                            implode(' ', array_filter($userData['first_name'] ?? '', $userData['last_name'] ?? '')) :
                            null
                    ]) ?>

                <div class="half-wrapper">
                    <?= $form->field($model, 'email', ['options' => [
                        'class' => ' form-group',
                        ]])
                        ->textInput(['placeholder' => 'something@google.com',
                            'value' => $userData && array_key_exists('email', $userData) ?
                                $userData['email'] :
                                null
                        ]); ?>
                    <?= $form->field($model, 'city', ['options' => ['class' => 'form-group']])
                        ->dropDownList(
                            ArrayHelper::map($cities, 'id', 'name'),
                            [
                                'class' => 'form-group',
                                'options' => [
                                $selectedCity => $valueCity
                                    ],
                                $promt => $check
                            ]
                        ); ?>
                </div>
            <?php if (!$userData) : ?>
                <?= $form->field($model, 'password', ['options' => ['class' => 'half-wrapper form-group']])
                    ->passwordInput(['placeholder' => 'пароль']); ?>
                <?= $form->field($model, 'repeatedPassword', ['options' => ['class' => 'half-wrapper form-group']])
                    ->passwordInput(['placeholder' => 'повторите пароль']); ?>
            <?php else : ?>
                <?php $password = Yii::$app->security->generateRandomString(8); ?>
                <?= $form->field(
                    $model,
                    'password',
                    ['options' => ['class' => 'half-wrapper form-group', 'style' => 'display:none']]
                )
                    ->passwordInput([
                        'placeholder' => 'пароль',
                        'value' => $password
                    ]); ?>
                <?= $form->field(
                    $model,
                    'repeatedPassword',
                    ['options' => ['class' => 'half-wrapper form-group', 'style' => 'display:none']]
                )
                    ->passwordInput([
                        'placeholder' => 'повторите пароль',
                        'value' => $password
                    ]); ?>
            <?php endif; ?>
            <?= $form->field(
                $model,
                'executor',
                ['options' => ['class' => 'form-group']]
            )->checkbox(['class' => 'control-label checkbox-label']) ?>
                <input type="submit" class="button button--blue" value="Создать аккаунт">
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
