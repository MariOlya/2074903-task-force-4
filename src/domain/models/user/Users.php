<?php

declare(strict_types=1);

namespace omarinina\domain\models\user;

use omarinina\domain\models\Cities;
use omarinina\domain\models\task\Responds;
use omarinina\domain\models\task\Reviews;
use omarinina\domain\models\task\Tasks;
use omarinina\domain\models\Categories;
use omarinina\domain\valueObjects\Phone;
use omarinina\infrastructure\constants\ReviewConstants;
use omarinina\infrastructure\constants\TaskConstants;
use omarinina\infrastructure\constants\TaskStatusConstants;
use omarinina\infrastructure\constants\UserRoleConstants;
use omarinina\infrastructure\models\form\EditProfileForm;
use Yii;
use yii\base\InvalidConfigException;
use yii\caching\TagDependency;
use yii\web\IdentityInterface;
use yii\web\UploadedFile;

/**
 * This is the model class for table "users".
 *
 * @property int $id
 * @property int $vkId
 * @property string $createAt
 * @property string $email
 * @property string $name
 * @property string $password
 * @property int $role
 * @property int $city
 * @property string|null $avatarSrc
 * @property string|null $birthDate
 * @property string|null $phone
 * @property string|null $telegram
 * @property string|null $bio
 * @property bool|null $hidden
 *
 * @property Cities $userCity
 * @property Categories[] $executorCategories
 * @property Responds[] $responds
 * @property Reviews[] $clientReviews
 * @property Reviews[] $executorReviews
 * @property Roles $userRole
 * @property Tasks[] $clientTasks
 * @property Tasks[] $executorTasks
 * @property Tasks[] $executorInWorkTasks
 * @property Tasks[] $executorFailedTasks
 * @property Tasks[] $executorDoneTasks
 */
class Users extends \yii\db\ActiveRecord implements IdentityInterface
{
    public const STATUS_BUSY = 'busy';
    public const STATUS_FREE = 'free';

    public const STATUS_BUSY_NAME = 'Занят';
    public const STATUS_FREE_NAME = 'Открыт для новых заказов';

    public const MAX_RATING = 5;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createAt', 'birthDate'], 'safe'],
            [['email', 'name', 'password', 'role', 'city'], 'required'],
            [['role', 'city', 'vkId'], 'integer'],
            [['bio', 'avatarSrc'], 'string'],
            [['email'], 'string', 'max' => 128],
            [['name', 'password'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 30],
            [['telegram'], 'string', 'max' => 64],
            [['email', 'vkId'], 'unique'],
            [['hidden'], 'boolean'],
            [['city'], 'exist', 'skipOnError' => true, 'targetClass' => Cities::class, 'targetAttribute' => ['city' => 'id']],
            [['role'], 'exist', 'skipOnError' => true, 'targetClass' => Roles::class, 'targetAttribute' => ['role' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'vkId' => 'VKontakte ID',
            'createAt' => 'Create At',
            'email' => 'Email',
            'name' => 'Name',
            'password' => 'Password',
            'role' => 'Role',
            'city' => 'City',
            'avatarSrc' => 'Avatar Src',
            'birthDate' => 'Birth Date',
            'phone' => 'Phone',
            'telegram' => 'Telegram',
            'bio' => 'Bio',
        ];
    }

    /**
     * Gets query for [[UserCity]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserCity()
    {
        return $this->hasOne(Cities::class, ['id' => 'city']);
    }

    /**
     * Gets query for [[ExecutorCategories]].
     *
     * @return \yii\db\ActiveQuery
     * @throws InvalidConfigException
     */
    public function getExecutorCategories()
    {
        return $this->hasMany(Categories::class, ['id' => 'categoryId'])
            ->viaTable('executorCategories', ['executorId' => 'id']);
    }

    /**
     * Gets query for [[Responds]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getResponds()
    {
        return $this->hasMany(Responds::class, ['executorId' => 'id']);
    }

    /**
     * Gets query for [[ClientReviews]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClientReviews()
    {
        return $this->hasMany(Reviews::class, ['clientId' => 'id']);
    }

    /**
     * Gets query for [[ExecutorReviews]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExecutorReviews()
    {
        return $this->hasMany(Reviews::class, ['executorId' => 'id'])
            ->cache(true, new TagDependency(['tags' => ReviewConstants::CACHE_TAG]));
    }

    /**
     * Gets query for [[UserRole]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserRole()
    {
        return $this->hasOne(Roles::class, ['id' => 'role']);
    }

    /**
     * Gets query for [[ClientTasks]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClientTasks()
    {
        return $this->hasMany(Tasks::class, ['clientId' => 'id']);
    }

    /**
     * Gets query for [[ExecutorTasks]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExecutorTasks()
    {
        return $this->hasMany(Tasks::class, ['executorId' => 'id'])
            ->cache(true, new TagDependency(['tags' => TaskConstants::CACHE_EXECUTOR_TAG]));
    }

    public static function findIdentity($id)
    {
        return self::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        // TODO: Implement findIdentityByAccessToken() method.
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        // TODO: Implement getAuthKey() method.
    }

    public function validateAuthKey($authKey)
    {
        // TODO: Implement validateAuthKey() method.
    }

    /**
     * Gets query for [[ExecutorInWorkTasks]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExecutorInWorkTasks()
    {
        return $this->getExecutorTasks()
            ->where(['tasks.status' => TaskStatusConstants::ID_IN_WORK_STATUS]);
    }

    /**
     * Gets query for [[ExecutorFailedTasks]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExecutorFailedTasks()
    {
        return $this->getExecutorTasks()
            ->where(['tasks.status' => TaskStatusConstants::ID_FAILED_STATUS]);
    }

    /**
     * Gets query for [[ExecutorDoneTasks]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExecutorDoneTasks()
    {
        return $this->getExecutorTasks()
            ->where(['tasks.status' => TaskStatusConstants::ID_DONE_STATUS]);
    }

    /**
     * @return string[]
     */
    private function getMapExecutorStatus(): array
    {
        return [
            self::STATUS_BUSY => self::STATUS_BUSY_NAME,
            self::STATUS_FREE => self::STATUS_FREE_NAME
        ];
    }

    /**
     * @return string
     */
    public function getExecutorCurrentStatus(): string
    {
        $currentTask = $this->executorInWorkTasks;
        return $currentTask ?
            $this->getMapExecutorStatus()[self::STATUS_BUSY] :
            $this->getMapExecutorStatus()[self::STATUS_FREE];
    }

    /**
     * @return int
     */
    public function getCountReviews(): int
    {
        return $this->getExecutorReviews()->count();
    }

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function getExecutorCreateAt(): string
    {
        return Yii::$app->formatter->asDate($this->createAt, 'dd MMMM, HH:mm');
    }

    /**
     * @return float
     */
    public function getExecutorRating(): float
    {
        $commonScore = 0;
        foreach ($this->executorReviews as $executorReview) {
            $commonScore += $executorReview->score;
        }
        return $this->getCountReviews() ?
            round($commonScore / ($this->getCountReviews() + $this->getCountFailedTasks()), 2) :
            0;
    }

    /**
     * @return int
     */
    public function getCountFailedTasks(): int
    {
        return $this->getExecutorFailedTasks()->count();
    }

    /**
     * @return int
     */
    public function getCountDoneTasks(): int
    {
        return $this->getExecutorDoneTasks()->count();
    }

    /**
     * @param Users $user
     * @return float
     */
    private function getExecutorRatingPlace(Users $user): float
    {
        $reviewTasks = array_map(
            function (Reviews $executorReviews) {
                return $executorReviews->taskId;
            },
            $user->executorReviews
        );
        $doneTasks = array_map(
            function ($executorDoneTasks) {
                return $executorDoneTasks->id;
            },
            $user->executorDoneTasks
        );
        foreach ($reviewTasks as $reviewTask) {
            if (!in_array($reviewTask, $doneTasks)) {
                $taskKey = array_search($reviewTask, $reviewTasks);
                unset($reviewTasks[$taskKey]);
            }
        }
        $commonScore = array_sum($reviewTasks);
        $countReviewDoneTasks = count($reviewTasks);
        return $countReviewDoneTasks ?
            round($commonScore / $countReviewDoneTasks, 2) :
            0;
    }

    /**
     * @return int
     */
    public function getExecutorPlace(): int
    {
        $allRating = array_map(
            function ($users) {
                return $this->getExecutorRatingPlace($users);
            },
            Roles::findOne(['role' => UserRoleConstants::EXECUTOR_ROLE])->users
        );
        $currentExecutorRating = $this->getExecutorRatingPlace($this);
        rsort($allRating);
        return array_search($currentExecutorRating, $allRating) + 1;
    }

    /**
     * @param int $vkId
     * @return bool
     */
    public function addVkId(int $vkId): bool
    {
        $this->vkId = $vkId;
        return $this->save(false);
    }

    /**
     * @param string $newPassword
     * @return bool
     * @throws \yii\base\Exception
     */
    public function updatePassword(string $newPassword) : bool
    {
        $this->password = Yii::$app->getSecurity()->generatePasswordHash($newPassword);
        return $this->save(false);
    }

    /**
     * @param EditProfileForm $form
     * @param string|null $avatarSrc
     * @return bool
     * @throws InvalidConfigException
     */
    public function updateProfile(EditProfileForm $form, ?string $avatarSrc = null) : bool
    {
        $this->name = $form->name;
        $this->email = $form->email;
        if ($form->phone) {
            $this->phone = (new Phone($form->phone))->getPhone();
        }
        $this->telegram = $form->telegram;
        $this->bio = $form->bio;
        $this->hidden = $form->hidden;
        if ($avatarSrc) {
            if ($this->avatarSrc) {
                $currentAvatarSrc = Yii::$app->basePath . '/web/' . $this->avatarSrc;
                if (file_exists($currentAvatarSrc)) {
                    unlink($currentAvatarSrc);
                }
            }
            $this->avatarSrc = $avatarSrc;
        }
        if ($form->birthDate !== null) {
            $this->birthDate = Yii::$app->formatter->asDate(
                $form->birthDate,
                'yyyy-MM-dd'
            );
        }
        return $this->save(false);
    }
}
