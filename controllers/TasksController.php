<?php

declare(strict_types=1);

namespace app\controllers;

use omarinina\application\services\file\interfaces\FileSaveInterface;
use omarinina\application\services\file\interfaces\FileTaskRelationsInterface;
use omarinina\application\services\location\interfaces\GeoObjectReceiveInterface;
use omarinina\application\services\task\dto\NewTaskDto;
use omarinina\application\services\task\interfaces\TaskCreateInterface;
use omarinina\application\services\task\interfaces\TaskFilterInterface;
use omarinina\infrastructure\constants\UserRoleConstants;
use omarinina\infrastructure\constants\TaskStatusConstants;
use Throwable;
use yii\base\InvalidConfigException;
use omarinina\domain\models\task\TaskStatuses;
use omarinina\domain\models\Categories;
use omarinina\domain\models\task\Tasks;
use omarinina\infrastructure\models\form\TaskFilterForm;
use omarinina\infrastructure\models\form\CreateTaskForm;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\data\Pagination;
use omarinina\infrastructure\constants\ViewConstants;

class TasksController extends SecurityController
{
    /** @var FileSaveInterface */
    private FileSaveInterface $fileSave;

    /** @var FileTaskRelationsInterface  */
    private FileTaskRelationsInterface $fileTaskRelations;

    /** @var TaskCreateInterface  */
    private TaskCreateInterface $taskCreate;

    /** @var TaskFilterInterface */
    private TaskFilterInterface $taskFilter;

    /** @var GeoObjectReceiveInterface */
    private GeoObjectReceiveInterface $geoObjectReceive;

    public function __construct(
        $id,
        $module,
        FileSaveInterface $fileSave,
        FileTaskRelationsInterface $fileTaskRelations,
        TaskCreateInterface $taskCreate,
        TaskFilterInterface $taskFilter,
        GeoObjectReceiveInterface $geoObjectReceive,
        $config = []
    ) {
        $this->fileSave = $fileSave;
        $this->fileTaskRelations = $fileTaskRelations;
        $this->taskCreate = $taskCreate;
        $this->taskFilter = $taskFilter;
        $this->geoObjectReceive = $geoObjectReceive;
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        $rules = parent::behaviors();
        $rule = [
            'allow' => false,
            'actions' => ['create'],
            'matchCallback' => function () {
                $user = Yii::$app->user->identity;
                return $user->userRole->role !== UserRoleConstants::CLIENT_ROLE;
            }
        ];
        array_unshift($rules['access']['rules'], $rule);
        return $rules;
    }

    /**
     * @param int|null $category
     * @return string
     */
    public function actionIndex(?int $category = null): string
    {
        $categories = Categories::find()->all();
        $taskFilterForm = new TaskFilterForm();

        if ($category) {
            $taskFilterForm->categories[] = $category;
        }

        $taskFilterForm->load(Yii::$app->request->post());
        if ($taskFilterForm->validate()) {
            $newTasks = $taskFilterForm
                ->filter(TaskStatuses::findOne(['taskStatus' => TaskStatusConstants::NEW_STATUS])
                ?->getNewTasks());
            $pagination = new Pagination([
                'totalCount' => $newTasks->count(),
                'pageSize' => ViewConstants::PAGE_COUNTER,
                'forcePageParam' => false,
                'pageSizeParam' => false
            ]);
            $newTasksWithPagination = $newTasks->offset($pagination->offset)
                    ->limit($pagination->limit)
                    ->all();
        }

        return $this->render('index', [
            'newTasks' => $newTasksWithPagination ?? null,
            'categories' => $categories,
            'model' => $taskFilterForm,
            'pagination' => $pagination ?? null
        ]);
    }

    /**
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView(int $id): string
    {
        $currentTask = Tasks::findOne($id);

        if (!$currentTask) {
            throw new NotFoundHttpException('Task is not found', 404);
        }
        $responds = Yii::$app->user->id === $currentTask->clientId ?
            $currentTask->responds :
            Yii::$app->user->identity->getResponds()->where(['taskId' => $currentTask->id])->all();

        return $this->render('view', [
            'currentTask' => $currentTask,
            'responds' => $responds
        ]);
    }

    /**
     * @return string|Response
     * @throws InvalidConfigException
     */
    public function actionCreate() : string|Response
    {
        $categories = Categories::find()->all();
        $createTaskForm = new CreateTaskForm();

        if (Yii::$app->request->getIsPost()) {
            $createTaskForm->load(Yii::$app->request->post());

            if ($createTaskForm->validate()) {
                if ($createTaskForm->location && !$createTaskForm->isLocationExistGeocoder()) {
                    Yii::$app->session->setFlash(
                        'error',
                        'Координаты вашего адреса не были найдены. Пожалуйста, попробуйте что-нибудь изменить.'
                    );
                    return $this->render('create', [
                        'model' => $createTaskForm,
                        'categories' => $categories
                    ]);
                }
                $createdTask = $this->taskCreate->createNewTask(new NewTaskDto(
                    $createTaskForm->getAttributes(),
                    Yii::$app->user->id,
                    $createTaskForm->expiryDate,
                    $this->geoObjectReceive->receiveGeoObjectFromYandexGeocoder($createTaskForm->location)
                ));
                foreach (UploadedFile::getInstances($createTaskForm, 'files') as $file) {
                    $savedFile = $this->fileSave->saveNewFile($file);
                    $this->fileTaskRelations->saveRelationsFileTask($createdTask->id, $savedFile->id);
                }
                return $this->redirect(['view', 'id' => $createdTask->id]);
            }
        }
        return $this->render('create', [
            'model' => $createTaskForm,
            'categories' => $categories
        ]);
    }

    /**
     * @param int|null $status
     * @return string
     * @throws Throwable
     */
    public function actionMine(?int $status = null) : string
    {
        $currentUser = Yii::$app->user->identity;
        $allTasks = $currentUser->role === UserRoleConstants::ID_CLIENT_ROLE ?
            $this->taskFilter->filterClientTasksByStatus($currentUser->id, $status) :
            $this->taskFilter->filterExecutorTasksByStatus($currentUser->id, $status);
        $title = $currentUser->role === UserRoleConstants::ID_CLIENT_ROLE ?
            ViewConstants::CLIENT_TASK_FILTER_TITLES[$status] :
            ViewConstants::EXECUTOR_TASK_FILTER_TITLES[$status];

        return $this->render('mine', [
            'title' => $title,
            'tasks' => $allTasks,
        ]);
    }
}
