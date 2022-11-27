<?php

namespace omarinina\application\services\user\interfaces;

use omarinina\application\services\user\dto\NewUserDto;
use omarinina\domain\models\user\Users;

interface UserCreateInterface
{
    public function createNewUser(NewUserDto $dto) : Users;
}
