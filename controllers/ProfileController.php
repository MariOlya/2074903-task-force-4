<?php

declare(strict_types=1);

namespace app\controllers;

use omarinina\application\services\file\interfaces\FileParseInterface;
use omarinina\application\services\user\interfaces\UserCategoriesUpdateInterface;
use omarinina\application\services\user\interfaces\UserShowInterface;
use omarinina\domain\models\Categories;
use omarinina\domain\models\user\Users;
use omarinina\infrastructure\models\form\EditProfileForm;
use omarinina\infrastructure\models\form\SecurityProfileForm;
use yii\base\Exception;
use yii\web\NotFoundHttpException;
use Yii;
use yii\web\Response;
use yii\web\UploadedFile;

class ProfileController extends SecurityController
{
    /** @var FileParseInterface */
    private FileParseInterface $fileParse;

    /** @var UserCategoriesUpdateInterface */
    private UserCategoriesUpdateInterface $userCategoriesUpdate;

    /** @var UserShowInterface */
    private UserShowInterface $userShow;

    public function __construct(
        $id,
        $module,
        FileParseInterface $fileParse,
        UserCategoriesUpdateInterface $userCategoriesUpdate,
        UserShowInterface $userShow,
        $config = []
    ) {
        $this->fileParse = $fileParse;
        $this->userCategoriesUpdate = $userCategoriesUpdate;
        $this->userShow = $userShow;
        parent::__construct($id, $module, $config);
    }

    /**
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView(int $id): string
    {
        $viewedUser = Users::findOne($id);
        if (!$viewedUser) {
            throw new NotFoundHttpException('User is not found', 404);
        }
        $currentUser = Yii::$app->user->id;
        $userProfile = ($currentUser === $id) ?
            $viewedUser :
            $this->userShow->getUserExecutorById($id);

        return $this->render('view', [
            'currentUser' => $userProfile
        ]);
    }

    /**
     * @return string|Response
     * @throws \Throwable
     */
    public function actionEdit() : Response|string
    {
        $editForm = new EditProfileForm($this->fileParse);
        $user = Yii::$app->user->identity;
        $categories = Categories::find()->all();

        if (Yii::$app->request->getIsPost()) {
            $editForm->load(Yii::$app->request->post());

            if ($editForm->validate()) {
                $avatar = UploadedFile::getInstance($editForm, 'avatar');
                $avatarSrc = $this->fileParse->parseAvatarFile($avatar);
                $user->updateProfile($editForm, $avatarSrc);
                $this->userCategoriesUpdate->updateExecutorCategories($user, $editForm->categories);
                return $this->redirect(['view', 'id' => $user->id]);
            }
        }

        return $this->render('edit', [
            'model' => $editForm,
            'user' => $user,
            'categories' => $categories
        ]);
    }

    /**
     * @return string|Response
     * @throws Exception
     */
    public function actionSecurity() : Response|string
    {
        $securityForm = new SecurityProfileForm();
        $user = Yii::$app->user->identity;

        if ($user->vkId) {
            return $this->redirect(['edit']);
        }

        if (Yii::$app->request->getIsPost()) {
            $securityForm->load(Yii::$app->request->post());

            if ($securityForm->validate()) {
                $user->updatePassword($securityForm->newPassword);

                return $this->redirect(['view', 'id' => $user->id]);
            }
        }

        return $this->render('security', [
            'model' => $securityForm,
        ]);
    }
}
