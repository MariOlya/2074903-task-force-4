<?php

declare(strict_types=1);

namespace app\controllers;

use omarinina\application\services\respond\interfaces\RespondCreateInterface;
use omarinina\application\services\respond\interfaces\RespondStatusAddInterface;
use omarinina\application\services\respond\dto\NewRespondDto;
use omarinina\application\services\review\interfaces\ReviewCreateInterface;
use omarinina\domain\exception\task\AvailableActionsException;
use omarinina\domain\exception\task\CurrentActionException;
use omarinina\domain\exception\task\IdUserException;
use omarinina\domain\models\task\Responds;
use omarinina\domain\models\task\Tasks;
use omarinina\infrastructure\models\form\TaskResponseForm;
use omarinina\infrastructure\models\form\TaskAcceptanceForm;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;
use yii\web\ServerErrorHttpException;

class TaskActionsController extends SecurityController
{
    /** @var RespondStatusAddInterface */
    private RespondStatusAddInterface $respondStatusAdd;

    /** @var RespondCreateInterface */
    private RespondCreateInterface $respondCreate;

    /** @var ReviewCreateInterface  */
    private ReviewCreateInterface $reviewCreate;

    public function __construct(
        $id,
        $module,
        RespondStatusAddInterface $respondStatusAdd,
        RespondCreateInterface $respondCreate,
        ReviewCreateInterface $reviewCreate,
        $config = []
    ) {
        $this->respondStatusAdd = $respondStatusAdd;
        $this->respondCreate = $respondCreate;
        $this->reviewCreate = $reviewCreate;
        parent::__construct($id, $module, $config);
    }


    /**
     * @param int $respondId
     * @return Response|string
     * @throws ServerErrorHttpException|NotFoundHttpException
     */
    public function actionAcceptRespond(int $respondId) : Response|string
    {
        $respond = Responds::findOne($respondId);
        if (!$respond) {
            throw new NotFoundHttpException('Respond is not found', 404);
        }

        $userId = \Yii::$app->user->id;
        $task = $respond->task;

        if ($this->respondStatusAdd->addAcceptStatus($respond, $userId)->status) {
            $task->addExecutorId($respond->executorId)->addInWorkStatus();
            $this->respondStatusAdd->addRestRespondsRefuseStatus(
                $task->responds,
                $respond
            );
        }

        return $this->redirect(['tasks/view', 'id' => $task->id]);
    }

    /**
     * @param int $respondId
     * @return Response|string
     */
    public function actionRefuseRespond(int $respondId) : Response|string
    {
        $respond = Responds::findOne($respondId);
        if (!$respond) {
            throw new NotFoundHttpException('Respond is not found', 404);
        }

        $task = $respond->task;
        $userId = \Yii::$app->user->id;

        $this->respondStatusAdd->addRefuseStatus($respond, $userId);

        return $this->redirect(['tasks/view', 'id' => $task->id]);
    }

    /**
     * @param int $taskId
     * @return Response|string
     * @throws NotFoundHttpException
     * @throws AvailableActionsException
     * @throws CurrentActionException
     * @throws IdUserException
     */
    public function actionCancelTask(int $taskId) : Response|string
    {
        $task = Tasks::findOne($taskId);

        if (!$task) {
            throw new NotFoundHttpException('Task is not found', 404);
        }

        $userId = Yii::$app->user->id;

        if ($task->addCancelledStatus($userId)) {
            $this->respondStatusAdd->addRestRespondsRefuseStatus($task->responds);
        }

        return $this->redirect(['tasks/view', 'id' => $task->id]);
    }

    /**
     * @param int $taskId
     * @return Response|string
     * @throws NotFoundHttpException
     */
    public function actionRespondTask(int $taskId) : Response|string
    {
        $task = Tasks::findOne($taskId);

        if (!$task) {
            throw new NotFoundHttpException('Task is not found', 404);
        }
        $user = Yii::$app->user->identity;
        $taskResponseForm = new TaskResponseForm();

        if (Yii::$app->request->getIsPost()) {
            $taskResponseForm->load(Yii::$app->request->post());
            if ($taskResponseForm->validate() && $taskResponseForm->isAvailableAddRespond($user, $task)) {
                $attributes = $taskResponseForm->getAttributes();
                $this->respondCreate->createNewRespond(new NewRespondDto($user->id, $task->id, $attributes));

                return $this->redirect(['tasks/view', 'id' => $taskId]);
            }
        }
        throw new NotFoundHttpException('Page not found', 404);
    }

    /**
     * @param int $taskId
     * @return Response|string
     * @throws AvailableActionsException
     * @throws CurrentActionException
     * @throws IdUserException
     * @throws NotFoundHttpException
     */
    public function actionDenyTask(int $taskId) : Response|string
    {
        $task = Tasks::findOne($taskId);

        if (!$task) {
            throw new NotFoundHttpException('Task is not found', 404);
        }

        $userId = Yii::$app->user->id;
        $task->addFailedStatus($userId);

        return $this->redirect(['tasks/view', 'id' => $taskId]);
    }

    /**
     * @param int $taskId
     * @return Response|string
     * @throws AvailableActionsException
     * @throws CurrentActionException
     * @throws IdUserException
     * @throws NotFoundHttpException
     */
    public function actionAcceptTask(int $taskId) : Response|string
    {
        $task = Tasks::findOne($taskId);

        if (!$task) {
            throw new NotFoundHttpException('Task is not found', 404);
        }

        $userId = Yii::$app->user->id;
        $taskAcceptanceForm = new TaskAcceptanceForm();

        if (!$task->addDoneStatus($userId)) {
            throw new NotFoundHttpException('Page not found', 404);
        }

        if (Yii::$app->request->getIsPost()) {
            $taskAcceptanceForm->load(Yii::$app->request->post());
            if ($taskAcceptanceForm->validate()) {
                $attributes = $taskAcceptanceForm->getAttributes();
                $this->reviewCreate->createNewReview($task, $attributes);

                return $this->redirect(['tasks/view', 'id' => $taskId]);
            }
        }
    }
}
