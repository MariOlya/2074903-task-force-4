<?php

declare(strict_types=1);

namespace omarinina\application\services\task;

use omarinina\application\services\task\dto\NewTaskDto;
use omarinina\application\services\task\interfaces\TaskCreateInterface;
use omarinina\domain\models\Cities;
use omarinina\domain\models\task\Tasks;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use omarinina\infrastructure\constants\TaskStatusConstants;

class TaskCreateService implements TaskCreateInterface
{
    /**
     * @param NewTaskDto $dto
     * @return Tasks
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function createNewTask(NewTaskDto $dto) : Tasks
    {
        $createdTask = new Tasks();
        $createdTask->attributes = $dto->attributes;
        $createdTask->clientId = $dto->userId;
        $createdTask->status = TaskStatusConstants::ID_NEW_STATUS;
        if ($dto->geoObject) {
            $city = explode(',', $dto->geoObject->description)[0];
            if (!Cities::findOne(['name' => $city])) {
                throw new NotFoundHttpException('This city is not founded', 404);
            }
            $createdTask->city = $city;
            $createdTask->address = $dto->geoObject->name;
            $coordinates = explode(' ', $dto->geoObject->Point->pos);
            $createdTask->lat = $coordinates[1];
            $createdTask->lng = $coordinates[0];
        }
        if ($dto->formExpiryDate !== null) {
            $createdTask->expiryDate = Yii::$app->formatter->asDate(
                $dto->formExpiryDate,
                'yyyy-MM-dd HH:mm:ss'
            );
        }

        if (!$createdTask->save(false)) {
            throw new ServerErrorHttpException(
                'Your data has not been recorded, please try again later',
                500
            );
        }

        return $createdTask;
    }
}
