<?php

namespace omarinina\application\services\file\interfaces;

use omarinina\domain\models\Files;
use yii\web\UploadedFile;

interface FileSaveInterface
{
    /**
     * @param UploadedFile $file
     * @return Files
     */
    public function saveNewFile(UploadedFile $file) : Files;
}
