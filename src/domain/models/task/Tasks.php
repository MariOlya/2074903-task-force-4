<?php

declare(strict_types=1);

namespace omarinina\domain\models\task;

use omarinina\domain\models\user\Users;
use omarinina\domain\models\Categories;
use omarinina\domain\models\Files;
use omarinina\infrastructure\constants\TaskStatusConstants;
use omarinina\domain\traits\TimeCounter;
use omarinina\domain\actions\AcceptAction;
use omarinina\domain\actions\CancelAction;
use omarinina\domain\actions\DenyAction;
use omarinina\domain\actions\RespondAction;
use omarinina\domain\actions\AbstractAction;
use omarinina\domain\exception\task\IdUserException;
use omarinina\domain\exception\task\CurrentActionException;
use omarinina\domain\exception\task\AvailableActionsException;
use yii\web\ServerErrorHttpException;

/**
 * This is the model class for table "tasks".
 *
 * @property int $id
 * @property string $createAt
 * @property string $name
 * @property string $description
 * @property string|null $expiryDate
 * @property int $budget
 * @property int $categoryId
 * @property string|null $city
 * @property string|null $address
 * @property float|null $lat
 * @property float|null $lng
 * @property int $status
 * @property int|null $executorId
 * @property int $clientId
 *
 * @property Categories $category
 * @property Users $client
 * @property Users $executor
 * @property Responds[] $responds
 * @property Reviews $review
 * @property TaskStatuses $taskStatus
 * @property TaskFiles[] $taskFiles
 * @property Files[] $files
 *
 */
class Tasks extends \yii\db\ActiveRecord
{
    use TimeCounter;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tasks';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createAt', 'expiryDate'], 'safe'],
            [['name',  'budget', 'categoryId', 'status', 'clientId', 'description'], 'required'],
            [['description'], 'string'],
            [['categoryId', 'status', 'executorId', 'clientId'], 'integer'],
            [['lat', 'lng'], 'number'],
            [['name', 'city', 'address'], 'string', 'max' => 255],
            [['budget'], 'integer', 'max' => 128],
            [['categoryId'], 'exist', 'skipOnError' => true, 'targetClass' => Categories::class, 'targetAttribute' => ['categoryId' => 'id']],
            [['clientId'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['clientId' => 'id']],
            [['executorId'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['executorId' => 'id']],
            [['status'], 'exist', 'skipOnError' => true, 'targetClass' => TaskStatuses::class, 'targetAttribute' => ['status' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'createAt' => 'Create At',
            'name' => 'Name',
            'description' => 'Description',
            'expiryDate' => 'Expiry Date',
            'budget' => 'Budget',
            'categoryId' => 'Category ID',
            'city' => 'City',
            'address' => 'Address',
            'status' => 'Status',
            'executorId' => 'Executor ID',
            'clientId' => 'Client ID',
            'lat' => 'Latitude',
            'lng' => 'Longitude'
        ];
    }

    /**
     * Gets query for [[Category]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Categories::class, ['id' => 'categoryId']);
    }

    /**
     * Gets query for [[Client]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClient()
    {
        return $this->hasOne(Users::class, ['id' => 'clientId']);
    }

    /**
     * Gets query for [[Executor]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExecutor()
    {
        return $this->hasOne(Users::class, ['id' => 'executorId']);
    }

    /**
     * Gets query for [[Responds]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getResponds()
    {
        return $this->hasMany(Responds::class, ['taskId' => 'id']);
    }

    /**
     * Gets query for [[Reviews]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getReview()
    {
        return $this->hasOne(Reviews::class, ['taskId' => 'id']);
    }

    /**
     * Gets query for [[TaskStatus]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTaskStatus()
    {
        return $this->hasOne(TaskStatuses::class, ['id' => 'status']);
    }

    /**
     * Gets query for [[TaskFiles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTaskFiles()
    {
        return $this->hasMany(TaskFiles::class, ['taskId' => 'id']);
    }

    /**
     *  Gets query for [[Files]].
     *
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getFiles()
    {
        return $this->hasMany(Files::class, ['id' => 'fileId'])
            ->viaTable('taskFiles', ['taskId' => 'id']);
    }

    /**
     * @param string $currentAction
     * @param int $idUser
     * @return int|null
     * @throws AvailableActionsException changes in this task status
     * @throws CurrentActionException Exception when user tries to choose action
     * which is unavailable for this task status
     * @throws IdUserException Exception when user doesn't have rights to add
     */
    private function changeStatusByAction(string $currentAction, int $idUser): ?int
    {
        if ($this->isValidAction($currentAction, $idUser)) {
            return $this->getLinkActionToStatus()[$currentAction];
        }
        if (!array_key_exists($currentAction, $this->getLinkActionToStatus())) {
            throw new CurrentActionException();
        }
        if ($this->getAvailableActions($idUser)->getInternalName() !== $currentAction) {
            throw new IdUserException();
        }
    }

    /**
     * @param int $idUser
     * @return AbstractAction|null
     */
    public function getAvailableActions(int $idUser): ?AbstractAction
    {
        if (!array_key_exists($this->taskStatus->id, $this->getLinkStatusToAction())) {
            return null;
        }
        $availableAction = array_values(array_filter(
            $this->getLinkStatusToAction()[$this->taskStatus->id],
            function (AbstractAction $action) use ($idUser) {
                return $action->isAvailableForUser($idUser);
            }
        ))[0] ?? null;
        if (!$availableAction) {
            return null;
        }
        return $availableAction;
    }

    /**
     * @return array
     */
    private function getLinkActionToStatus(): array
    {
        return [
            RespondAction::getInternalName() => TaskStatusConstants::ID_NEW_STATUS,
            CancelAction::getInternalName() => TaskStatusConstants::ID_CANCELLED_STATUS,
            AcceptAction::getInternalName() => TaskStatusConstants::ID_DONE_STATUS,
            DenyAction::getInternalName() => TaskStatusConstants::ID_FAILED_STATUS
        ];
    }

    /**
     * @return array
     */
    private function getLinkStatusToAction(): array
    {
        return [
            TaskStatusConstants::ID_NEW_STATUS => [
                new CancelAction($this),
                new RespondAction($this)
            ],
            TaskStatusConstants::ID_IN_WORK_STATUS => [
                new AcceptAction($this),
                new DenyAction($this)
            ]
        ];
    }

    /**
     * @param string $currentAction
     * @param int $idUser
     * @return bool
     * @throws AvailableActionsException
     * @throws IdUserException
     */
    private function isValidAction(string $currentAction, int $idUser): bool
    {
        if (array_key_exists($currentAction, $this->getLinkActionToStatus())) {
            return $this->getAvailableActions($idUser)->getInternalName() === $currentAction;
        }
        return false;
    }

    /**
     * @param int $userId
     * @return bool
     * @throws AvailableActionsException
     * @throws CurrentActionException
     * @throws IdUserException
     */
    public function addCancelledStatus(int $userId) : bool
    {
        $this->status = $this->changeStatusByAction(
            CancelAction::getInternalName(),
            $userId
        );
        return $this->save(false);
    }

    /**
     * @param int $userId
     * @return bool
     * @throws AvailableActionsException
     * @throws CurrentActionException
     * @throws IdUserException
     */
    public function addFailedStatus(int $userId) : bool
    {
        $this->status = $this->changeStatusByAction(
            DenyAction::getInternalName(),
            $userId
        );
        return $this->save(false);
    }

    /**
     * @param int $userId
     * @return bool
     * @throws AvailableActionsException
     * @throws CurrentActionException
     * @throws IdUserException
     */
    public function addDoneStatus(int $userId) : bool
    {
        $this->status = $this->changeStatusByAction(
            AcceptAction::getInternalName(),
            $userId
        );
        return $this->save(false);
    }

    /**
     * @return bool
     */
    public function addInWorkStatus() : bool
    {
        $this->status = TaskStatusConstants::ID_IN_WORK_STATUS;
        return $this->save(false);
    }

    /**
     * @param int $executorId
     * @return Tasks
     * @throws ServerErrorHttpException
     */
    public function addExecutorId(int $executorId) : Tasks
    {
        $this->executorId = $executorId;
        return $this->save(false) ?
            $this :
            throw new ServerErrorHttpException('something wrong, please try again later', 500);
    }
}
