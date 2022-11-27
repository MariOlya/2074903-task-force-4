<?php

declare(strict_types=1);

namespace omarinina\application\services\user;

use omarinina\application\services\user\dto\NewUserDto;
use omarinina\application\services\user\interfaces\UserCreateInterface;
use omarinina\domain\models\user\Users;
use omarinina\infrastructure\constants\UserRoleConstants;
use omarinina\infrastructure\constants\ViewConstants;
use yii\base\Exception;
use yii\web\ServerErrorHttpException;
use Yii;

class UserCreateService implements UserCreateInterface
{
    /**
     * @param NewUserDto $dto
     * @return Users
     * @throws Exception
     * @throws ServerErrorHttpException
     */
    public function createNewUser(NewUserDto $dto) : Users
    {
        $createdUser = new Users();
        $createdUser->attributes = $dto->form->getAttributes();
        if ($dto->userData) {
            $createdUser->vkId = $dto->userData['id'];
        }
        $createdUser->avatarSrc = $dto->avatarVk ??
            ViewConstants::DEFAULT_AVATARS[array_rand(ViewConstants::DEFAULT_AVATARS, 1)];
        $createdUser->email = mb_strtolower($dto->form->email);
        $createdUser->password = Yii::$app->getSecurity()->generatePasswordHash($dto->form->password);
        $createdUser->role =  ($dto->form->executor === true) ?
            UserRoleConstants::ID_EXECUTOR_ROLE :
            UserRoleConstants::ID_CLIENT_ROLE;

        if (!$createdUser->save(false)) {
            throw new ServerErrorHttpException(
                'Your data has not been recorded, please try again later',
                500
            );
        }

        return $createdUser;
    }
}
