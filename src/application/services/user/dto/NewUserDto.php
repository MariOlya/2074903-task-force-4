<?php

namespace omarinina\application\services\user\dto;

use omarinina\infrastructure\models\form\RegistrationForm;

class NewUserDto
{
    /** @var RegistrationForm */
    public RegistrationForm $form;

    /** @var array|null */
    public ?array $userData = null;

    /** @var string|null */
    public ?string $avatarVk = null;

    /**
     * @param RegistrationForm $form
     * @param array|null $userData
     * @param string|null $avatarVk
     */
    public function __construct(RegistrationForm $form, ?array $userData = null, ?string $avatarVk = null)
    {
        $this->form = $form;
        $this->userData = $userData;
        $this->avatarVk = $avatarVk;
    }


}
