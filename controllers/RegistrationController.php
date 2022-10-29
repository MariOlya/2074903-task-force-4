<?php
namespace app\controllers;

use omarinina\application\services\user\create\ServiceUserCreate;
use omarinina\infrastructure\models\form\RegistrationCityRoleForm;
use omarinina\infrastructure\models\form\RegistrationForm;
use omarinina\domain\models\Cities;
use omarinina\infrastructure\models\form\RegistrationRoleForm;
use Yii;
use yii\base\Exception;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\web\ServerErrorHttpException;

class RegistrationController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
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
     * @return string|Response
     */
    public function actionIndex(?array $userData = null): string|Response
    {
        try {
            $registrationForm = new RegistrationForm();
            $cities = Cities::find()->all();

            if (Yii::$app->request->getIsPost()) {
                $registrationForm->load(Yii::$app->request->post());

                if ($registrationForm->validate()) {
                    $newUser = ServiceUserCreate::createNewUser(
                        $registrationForm,
                        Yii::$app->request->post('RegistrationForm')
                    );
                    if ($userData) {
                        return $this->redirect(['auth/login', 'user' => $newUser]);
                    }
                    return $this->redirect(['site/index']);
                }
            }

            return $this->render('index', [
                'model' => $registrationForm,
                'cities' => $cities,
                'userData' => $userData
            ]);
        } catch (ServerErrorHttpException|\yii\base\Exception $e) {
            return $e->getMessage();
        } catch (\Throwable $e) {
            return 'Something wrong. Sorry, please, try again later';
        }
    }
}
