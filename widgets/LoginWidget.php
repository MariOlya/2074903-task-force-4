<?php

declare(strict_types=1);

namespace app\widgets;

use Yii;
use yii\base\Widget;
use omarinina\infrastructure\models\form\LoginForm;

class LoginWidget extends Widget
{

    public function run()
    {
        if (Yii::$app->user->isGuest) {
            $model = new LoginForm();
            return $this->render('loginWidget', [
                'model' => $model,
            ]);
        }

        return null;
    }

}
