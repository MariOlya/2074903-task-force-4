<?php

declare(strict_types=1);

namespace omarinina\application\services\respond;

use omarinina\application\services\respond\dto\NewRespondDto;
use omarinina\application\services\respond\interfaces\RespondCreateInterface;
use omarinina\domain\models\task\Responds;
use yii\web\ServerErrorHttpException;

class RespondCreateService implements RespondCreateInterface
{
    /**
     * @param NewRespondDto $dto
     * @return Responds|null
     * @throws ServerErrorHttpException
     */
    public function createNewRespond(NewRespondDto $dto): ?Responds
    {
        $newRespond = new Responds();
        if ($dto->attributes) {
            $newRespond->attributes = $dto->attributes;
        }
        $newRespond->taskId = $dto->taskId;
        $newRespond->executorId = $dto->userId;
        if (!$newRespond->save(false)) {
            throw new ServerErrorHttpException(
                'Your data has not been recorded, please try again later',
                500
            );
        }
        return $newRespond;
    }
}
