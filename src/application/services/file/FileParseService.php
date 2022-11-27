<?php

declare(strict_types=1);

namespace omarinina\application\services\file;

use omarinina\application\services\file\interfaces\FileParseInterface;
use Yii;
use yii\web\UploadedFile;
use omarinina\infrastructure\constants\HelperConstants;

class FileParseService implements FileParseInterface
{
    /**
     * @param UploadedFile|null $avatar
     * @return string|null
     */
    public function parseAvatarFile(?UploadedFile $avatar = null) : ?string
    {
        if ($avatar) {
            $name = uniqid('upload', true) . '.' . $avatar->getExtension();
            $uploadPath = Yii::getAlias('@webroot') . HelperConstants::PART_PATH_AVATAR;

            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $avatar->saveAs('@webroot' . HelperConstants::PART_PATH_AVATAR . $name);

            return HelperConstants::PART_PATH_AVATAR . $name;
        }
        return null;
    }

    public function parseAvatarVkFile(?string $urlAvatarVk = null) : ?string
    {
        if ($urlAvatarVk) {
            $url = $urlAvatarVk;
            $fileName = HelperConstants::PART_PATH_AVATAR . uniqid('upload', true) . '.' . 'jpg';

            $uploadPath = Yii::getAlias('@webroot') . HelperConstants::PART_PATH_AVATAR;

            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $fullPath = Yii::getAlias('@webroot') . $fileName;
            file_put_contents($fullPath, file_get_contents($url));
            return $fileName;
        }
        return null;
    }
}
