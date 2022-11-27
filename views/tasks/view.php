<?php

declare(strict_types=1);

/** @var $this View */
/** @var omarinina\domain\models\task\Tasks $currentTask */
/** @var omarinina\domain\models\task\Responds[] $responds */
/** @var omarinina\infrastructure\models\form\TaskResponseForm $model */

use yii\web\View;
use yii\helpers\Url;
use omarinina\domain\models\user\Users;
use omarinina\infrastructure\models\form\TaskResponseForm;
use omarinina\infrastructure\models\form\TaskAcceptanceForm;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use phpnt\yandexMap\YandexMaps;

$this->registerJsFile(Yii::$app->request->baseUrl.'/js/main.js');
?>
<div class="main-content container">
    <div class="left-column">
        <div class="head-wrapper">
            <h3 class="head-main"><?= Html::encode($currentTask->name); ?></h3>
            <?php if ($currentTask->budget) : ?>
            <p class="price price--big"><?= $currentTask->budget . ' ₽'; ?></p>
            <?php endif; ?>
        </div>
        <p class="task-description"><?= Html::encode($currentTask->description); ?></p>
        <?php if ($currentTask->getAvailableActions(\Yii::$app->user->id)) : ?>
            <?= $currentTask->getAvailableActions(\Yii::$app->user->id)->getViewAvailableButton() ?>
        <?php endif; ?>
        <?php if (isset($currentTask->address)) : ?>
        <div class="task-map">
            <div class="map">
                <?php
                $items = [
                    [
                        'latitude' => $currentTask->lat,
                        'longitude' => $currentTask->lng,
                        'options' => [
                            'preset' => 'islands#icon',
                            'iconColor' => '#19a111'
                        ]
                    ],
                ]; ?>

                <?= YandexMaps::widget(
                    [
                        'myPlacemarks' => [
                            [
                                'latitude' => $currentTask->lat,
                                'longitude' => $currentTask->lng,
                                'options' => []
                            ],
                        ],
                        'mapOptions' => [
                            'center' => [$currentTask->lat, $currentTask->lng],
                            'zoom' => 13,
                            'controls' => ['zoomControl', 'fullscreenControl', 'searchControl'],
                            'control' => [
                                'zoomControl' => [
                                    'top' => 75,
                                    'left' => 5
                                ],
                            ],
                        ],
                        'disableScroll' => true,
                        'windowWidth' => '725px',
                        'windowHeight' => '346px',
                    ]
                ); ?>

            </div>
            <p class="map-address town"><?= $currentTask->city ?></p>
            <p class="map-address"><?= $currentTask->address ?></p>
        </div>
        <?php endif; ?>
        <?php if (isset($responds[0])) : ?>
        <h4 class="head-regular">Отклики на задание</h4>
            <?php foreach ($responds as $respond) : ?>
        <div class="response-card">
            <img class="customer-photo"
                 src="<?= $respond->executor->avatarSrc ?>"
                 width="146" height="156" alt="Фото заказчиков">
            <div class="feedback-wrapper">
                <a href="<?= Url::to(['profile/view', 'id' => $respond->executor->id]) ?>"
                   class="link link--block link--big">
                    <?= Html::encode($respond->executor->name) ?></a>
                <div class="response-wrapper">
                    <div class="stars-rating small">
                        <?= str_repeat(
                            '<span class="fill-star">&nbsp;</span>',
                            (int)round(
                                Users::findOne($respond->executor->id)->getExecutorRating()
                            )
                        ) ?>
                        <?= str_repeat(
                            '<span>&nbsp;</span>',
                            Users::MAX_RATING - (int)round(
                                Users::findOne($respond->executor->id)->getExecutorRating()
                            )
                        ) ?>
                    </div>
                    <p class="reviews">
                        <?= \morphos\Russian\pluralize(
                            Users::findOne($respond->executor->id)->getCountReviews(),
                            'отзыв'
                        ) ?></p>
                </div>
                <p class="response-message">
                    <?= Html::encode($respond->comment) ?>
                </p>

            </div>

            <div class="feedback-wrapper">
                <p class="info-text">
                    <span class="current-time"><?= $respond->countTimeAgoPost($respond->createAt) ?>
                    </span> назад</p>
                <?php if ($respond->price) : ?>
                <p class="price price--small"><?= $respond->price ?> ₽</p>
                <?php endif; ?>
            </div>
                <?php if (!$respond->status && \Yii::$app->user->id === $currentTask->clientId) : ?>
            <div class="button-popup">
                <a href="<?= Url::toRoute([
                    'task-actions/accept-respond',
                    'respondId' => $respond->id
                ]) ?>" class="button button--blue button--small">Принять</a>
                <a href="<?= Url::to([
                    'task-actions/refuse-respond',
                    'respondId' => $respond->id
                ]) ?>" class="button button--orange button--small">Отказать</a>
            </div>
                <?php endif; ?>
        </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="right-column">
        <div class="right-card black info-card">
            <h4 class="head-card">Информация о задании</h4>
            <dl class="black-list">
                <dt>Категория</dt>
                <dd><?= $currentTask->category->name ?></dd>
                <dt>Дата публикации</dt>
                <dd><?= $currentTask->countTimeAgoPost($currentTask->createAt) ?> назад</dd>
                <dt>Срок выполнения</dt>
                <dd><?= Yii::$app->formatter->asDate($currentTask->expiryDate, 'dd MMMM, HH:mm') ?></dd>
                <dt>Статус</dt>
                <dd><?= $currentTask->taskStatus->name ?></dd>
            </dl>
        </div>
        <?php if ($currentTask->files) : ?>
        <div class="right-card white file-card">
            <h4 class="head-card">Файлы задания</h4>
            <ul class="enumeration-list">
                <?php foreach ($currentTask->files as $file) : ?>
                <li class="enumeration-item">
                    <a href="<?= Url::to(['file/download', 'fileId' => $file->id]) ?>"
                       class="link link--block link--clip">
                        <?= str_replace(
                            '/uploads/',
                            '',
                            $file->fileSrc
                        ) ?>
                    </a>
                    <p class="file-size">
                        <?php echo Yii::$app->formatter->asShortSize(
                            filesize(Yii::getAlias('@webroot').$file->fileSrc)
                        ) ?>
                    </p>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $this->beginBlock('pop-ups'); ?>

<section class="pop-up pop-up--refusal pop-up--close">
    <div class="pop-up--wrapper">
        <h4>Отказ от задания</h4>
        <p class="pop-up-text">
            <b>Внимание!</b><br>
            Вы собираетесь отказаться от выполнения этого задания.<br>
            Это действие плохо скажется на вашем рейтинге и увеличит счетчик проваленных заданий.
        </p>
        <a href="<?= Url::to(['task-actions/deny-task', 'taskId' => $currentTask->id]) ?>"
           class="button button--pop-up button--orange">Отказаться</a>
        <div class="button-container">
            <button class="button--close" type="button">Закрыть окно</button>
        </div>
    </div>
</section>
<section class="pop-up pop-up--cancel pop-up--close">
    <div class="pop-up--wrapper">
        <h4>Отмена задания</h4>
        <p class="pop-up-text">
            <b>Внимание!</b><br>
            Вы собираетесь отменить ваше задание.<br>
            Оно пропадёт из поиска и не сможет быть выполненным.
        </p>
        <a href="<?= Url::to(['task-actions/cancel-task', 'taskId' => $currentTask->id]) ?>"
           class="button button--pop-up button--orange">Отменить</a>
        <div class="button-container">
            <button class="button--close" type="button">Закрыть окно</button>
        </div>
    </div>
</section>
<section class="pop-up pop-up--completion pop-up--close">
    <div class="pop-up--wrapper">
        <h4>Завершение задания</h4>
        <p class="pop-up-text">
            Вы собираетесь отметить это задание как выполненное.
            Пожалуйста, оставьте отзыв об исполнителе и отметьте отдельно, если возникли проблемы.
        </p>
        <div class="completion-form pop-up--form regular-form">

            <?php
            $model = new TaskAcceptanceForm();

            $form = ActiveForm::begin([
                'id' => 'acceptance-form',
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'control-label'],
                    'errorOptions' => ['tag' => 'span', 'class' => 'help-block']
                ],
                'action' => ['task-actions/accept-task', 'taskId' => $currentTask->id]
            ]);
            ?>

            <p>
                <?= $form->field($model, 'comment', ['options' => ['class' => 'form-group']])
                    ->textarea(['placeholder' => 'Напишите то, что важно']); ?>
            </p>
            <p>
                <?= $form->field($model, 'score', ['options' => [
                    'class'=> 'completion-head control-label']])
                    ->hiddenInput(['id' => 'acceptance-form-rate',
                    ]) ?>
            </p>
            <div class="stars-rating big active-stars">
                <span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span>
            </div>

            <?php
            echo Html::submitInput('Завершить', ['class' => 'button button--pop-up button--blue']);

            ActiveForm::end();

            ?>
            <div class="button-container">
                <?php
                echo Html::button('Закрыть', ['class' => 'button--close', 'data-bs-dismiss' => 'modal']);
                ?>
            </div>
        </div>
</section>
<section class="pop-up pop-up--act_response pop-up--close">
    <div class="pop-up--wrapper">
        <h4>Добавление отклика к заданию</h4>
        <p class="pop-up-text">
            Вы собираетесь оставить свой отклик к этому заданию.
            Пожалуйста, укажите стоимость работы и добавьте комментарий, если необходимо.
        </p>
        <div class="addition-form pop-up--form regular-form">
            <?php
            $model = new TaskResponseForm();

            $form = ActiveForm::begin([
                'id' => $model->formName(),
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'control-label'],
                    'errorOptions' => ['tag' => 'span', 'class' => 'help-block']
                ],
                'action' => ['task-actions/respond-task', 'taskId' => $currentTask->id]
            ]);
            ?>
            <?= $form->field($model, 'comment', ['options' => ['class' => 'form-group']])
                ->textarea(['placeholder' => 'Напишите то, что важно']); ?>
            <?= $form->field($model, 'price', ['options' => ['class'=> 'form-group']])
                ->textInput(['placeholder' => '1000']) ?>
            <?php
            echo Html::submitInput(
                'Завершить',
                [
                    'class' => 'button button--pop-up button--blue'
                ]
            );

            ActiveForm::end();

            ?>
        </div>
        <div class="button-container">
            <?php
            echo Html::button('Закрыть', ['class' => 'button--close']);
            ?>
        </div>
    </div>
</section>
<div class="overlay"></div>

<?php $this->endBlock(); ?>






