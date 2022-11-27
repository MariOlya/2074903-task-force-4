<?php

declare(strict_types=1);

namespace omarinina\domain\models\user;

use omarinina\domain\models\Categories;

/**
 * This is the model class for table "executorCategories".
 *
 * @property int $id
 * @property int $categoryId
 * @property int $executorId
 *
 * @property Categories $category
 * @property Users $executor
 */
class ExecutorCategories extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'executorCategories';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['categoryId', 'executorId'], 'required'],
            [['categoryId', 'executorId'], 'integer'],
            [
                ['executorId'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Users::class,
                'targetAttribute' => ['executorId' => 'id']
            ],
            [
                ['categoryId'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Categories::class,
                'targetAttribute' => ['categoryId' => 'id']
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'categoryId' => 'Category ID',
            'executorId' => 'Executor ID',
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
     * Gets query for [[Executor]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExecutor()
    {
        return $this->hasOne(Users::class, ['id' => 'executorId']);
    }

    /**
     * @return void
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteExecutorCategory(): void
    {
        $this->delete();
    }
}
