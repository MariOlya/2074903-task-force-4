<?php

declare(strict_types=1);

namespace omarinina\application\services\file;

use omarinina\application\services\file\interfaces\FileSaveInterface;
use omarinina\domain\models\Files;
use omarinina\infrastructure\constants\HelperConstants;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;
use Yii;

class FileSaveService implements FileSaveInterface
{
    /**
     * @param UploadedFile $file
     * @return Files
     * @throws ServerErrorHttpException
     */
    public function saveNewFile(UploadedFile $file) : Files
    {
        $savedFile = new Files();
        $name = uniqid('upload', true) . '.' . $file->getExtension();

        $uploadPath = Yii::getAlias('@webroot') . HelperConstants::PART_PATH_FILE;

        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $file->saveAs('@webroot' . HelperConstants::PART_PATH_FILE . $name);
        $savedFile->fileSrc = HelperConstants::PART_PATH_FILE . $name;

        if (!$savedFile->save(false)) {
            throw new ServerErrorHttpException(
                'Your data has not been recorded, please try again later',
                500
            );
        }

        return $savedFile;
    }
}
