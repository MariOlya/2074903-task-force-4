<?php

declare(strict_types=1);

namespace app\controllers;

use omarinina\application\services\file\interfaces\FileParseInterface;
use omarinina\application\services\user\dto\NewUserDto;
use omarinina\application\services\user\interfaces\UserCreateInterface;
use omarinina\infrastructure\models\form\RegistrationForm;
use omarinina\domain\models\Cities;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;

class RegistrationController extends Controller
{
    /** @var FileParseInterface */
    private FileParseInterface $fileParse;

    /** @var UserCreateInterface */
    private UserCreateInterface $userCreate;

    public function __construct(
        $id,
        $module,
        FileParseInterface $fileParse,
        UserCreateInterface $userCreate,
        $config = []
    ) {
        $this->fileParse = $fileParse;
        $this->userCreate = $userCreate;
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['index'],
                'rules' => [
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['?']
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array|null $userData
     * @return string|Response
     */
    public function actionIndex(?array $userData = null): string|Response
    {
        $registrationForm = new RegistrationForm();
        $cities = Cities::find()->all();

        if (Yii::$app->request->getIsPost()) {
            $registrationForm->load(Yii::$app->request->post());

            if ($registrationForm->validate()) {
                $avatarVk = $this->fileParse->parseAvatarVkFile($userData['photo'] ?? null);
                $newUser = $this->userCreate->createNewUser(
                    new NewUserDto(
                        $registrationForm,
                        $userData,
                        $avatarVk
                    )
                );
                return $userData
                    ? $this->redirect(['auth/login', 'userId' => $newUser->id])
                    : $this->redirect(['site/index']);
            }
        }

        return $this->render('index', [
            'model' => $registrationForm,
            'cities' => $cities,
            'userData' => $userData
        ]);
    }
}
