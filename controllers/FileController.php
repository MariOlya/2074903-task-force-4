<?php

declare(strict_types=1);

namespace app\controllers;

use omarinina\domain\models\Files;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;

class FileController extends SecurityController
{
    /**
     * @param int $fileId
     * @return Response|string
     * @throws NotFoundHttpException
     */
    public function actionDownload(int $fileId): Response|string
    {
        $file = Files::findOne($fileId);
        if (!$file) {
            throw new NotFoundHttpException('File is not found', 404);
        }

        $filePath = Yii::getAlias('@webroot') . $file->fileSrc;
        return Yii::$app->response->sendFile($filePath);
    }
}
