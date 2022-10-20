<?php
namespace app\controllers;

use omarinina\domain\actions\AcceptAction;
use omarinina\domain\actions\CancelAction;
use omarinina\domain\actions\DenyAction;
use omarinina\domain\actions\RespondAction;
use omarinina\domain\exception\task\IdUserException;
use omarinina\domain\models\task\Responds;
use omarinina\domain\models\task\RespondStatuses;
use omarinina\domain\models\task\Reviews;
use omarinina\domain\models\task\Tasks;
use omarinina\infrastructure\models\form\LoginForm;
use omarinina\infrastructure\models\form\TaskResponseForm;
use omarinina\infrastructure\models\form\TaskAcceptanceForm;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;
use yii\widgets\ActiveForm;

class TaskActionsController extends SecurityController
{
    public const ACCEPT_ACTION = 'accepted';
    public const REFUSE_ACTION = 'refused';

    /**
     * @param int $respondId
     * @return Response
     */
    public function actionAcceptRespond(int $respondId) : Response
    {
        $respond = Responds::findOne($respondId);
        $task = $respond->task;
        $responds = $task->responds;

        if (\Yii::$app->user->id === $task->clientId && $task->taskStatus->taskStatus === Tasks::NEW_STATUS) {
            $task->executorId = $respond->executorId;
            $task->save(false);
            $respond->status = RespondStatuses::findOne(['status' => static::ACCEPT_ACTION])->id;
            $respond->save(false);

            foreach ($responds as $uniqueRespond) {
                if (!$uniqueRespond->status && $uniqueRespond->id !== $respond->id) {
                    $uniqueRespond->status = RespondStatuses::findOne(['status' => static::REFUSE_ACTION])->id;
                    $uniqueRespond->save(false);
                }
            }
        }

        return $this->redirect(['tasks/view', 'id' => $task->id]);
    }

    /**
     * @param int $respondId
     * @return Response
     */
    public function actionRefuseRespond(int $respondId) : Response
    {
        $respond = Responds::findOne($respondId);
        $task = $respond->task;

        if (\Yii::$app->user->id === $task->clientId && $task->taskStatus->taskStatus === Tasks::NEW_STATUS) {
            $respond->status = RespondStatuses::findOne(['status' => static::REFUSE_ACTION])->id;
            $respond->save(false);
        }

        return $this->redirect(['tasks/view', 'id' => $task->id]);
    }

    public function actionCancelTask(int $taskId)
    {
        $task = Tasks::findOne($taskId);
        if ($taskId) {
            if (Yii::$app->user->id === $task->clientId) {
                $task->status = $task->changeStatusByAction(
                    CancelAction::getInternalName(),
                    \Yii::$app->user->id
                );
                $task->save(false);
                if ($task->responds) {
                    foreach ($task->responds as $respond) {
                        if (!$respond->status) {
                            $respond->status = RespondStatuses::findOne(['status' => static::REFUSE_ACTION])->id;
                            $respond->save(false);
                        }
                    }
                }
                return $this->redirect(['tasks/view', 'id' => $task->id]);
            }
        }
        throw new NotFoundHttpException('Task is not found', 404);
    }

    public function actionRespondTask(int $taskId)
    {
        if ($taskId) {
            $task = Tasks::findOne($taskId);
            $user = Yii::$app->user->identity;
            $taskResponseForm = new TaskResponseForm();
            if ($user->userRole->role === 'executor' &&
                !$user->getResponds()->where(['taskId' => $taskId])->one() &&
                $task->taskStatus->taskStatus = Tasks::NEW_STATUS
            ) {
                if (Yii::$app->request->getIsPost()) {
                    $taskResponseForm->load(Yii::$app->request->post());
                    if ($taskResponseForm->validate()) {
                        $newRespond = new Responds();
                        $newRespond->attributes = Yii::$app->request->post('TaskResponseForm');
                        $newRespond->taskId = $taskId;
                        $newRespond->executorId = $user->id;
                        $newRespond->save(false);
                        return $this->redirect(['tasks/view', 'id' => $taskId]);
                    }
                }
            }
                throw new NotFoundHttpException('Page not found', 404);
        }
            throw new NotFoundHttpException('Task is not found', 404);
    }


    public function actionDenyTask(int $taskId)
    {
        if ($taskId) {
            $task = Tasks::findOne($taskId);
            if (Yii::$app->user->id === $task->executorId) {
                $task->status = $task->changeStatusByAction(
                    DenyAction::getInternalName(),
                    \Yii::$app->user->id
                );
                $task->save(false);
                return $this->redirect(['tasks/view', 'id' => $taskId]);
            }
        }
        throw new NotFoundHttpException('Task is not found', 404);
    }

    public function actionAcceptTask(int $taskId)
    {
        if ($taskId) {
            $task = Tasks::findOne($taskId);
            $taskAcceptanceForm = new TaskAcceptanceForm();
            if (Yii::$app->user->id === $task->clientId) {
                $task->status = $task->changeStatusByAction(
                    AcceptAction::getInternalName(),
                    \Yii::$app->user->id
                );
                $task->save(false);
                if (Yii::$app->request->getIsPost()) {
                    $taskAcceptanceForm->load(Yii::$app->request->post());
                    if ($taskAcceptanceForm->validate()) {
                        $newReview = new Reviews();
                        $newReview->attributes = Yii::$app->request->post('TaskAcceptanceForm');
                        $newReview->taskId = $taskId;
                        $newReview->executorId = $task->executorId;
                        $newReview->clientId = $task->clientId;
                        $newReview->save(false);
                        return $this->redirect(['tasks/view', 'id' => $taskId]);
                    }
                }
            }
            throw new NotFoundHttpException('Page not found', 404);
        }
        throw new NotFoundHttpException('Task is not found', 404);
    }
}
