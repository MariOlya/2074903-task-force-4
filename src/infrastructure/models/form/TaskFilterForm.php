<?php

declare(strict_types=1);

namespace omarinina\infrastructure\models\form;

use yii\base\Model;
use omarinina\domain\models\Categories;
use yii\db\ActiveQuery;

class TaskFilterForm extends Model
{
    public const PERIOD_1_HOUR = '1';
    public const PERIOD_12_HOURS = '12';
    public const PERIOD_24_HOURS = '24';
    public const PERIOD_ALL = 'all';

    public const NAME_PERIOD_1 = '1 час';
    public const NAME_PERIOD_12 = '12 часов';
    public const NAME_PERIOD_24 = '24 часа';
    public const NAME_PERIOD_ALL = 'За всё время';

    /** @var array */
    public array $categories = [];

    /** @var bool */
    public bool $noResponds = false;

    /** @var bool */
    public bool $remote = false;

    /** @var string */
    public string $period = '';

    public function attributeLabels(): array
    {
        return [
            'categories' => '',
            'remote' => 'Удаленная работа',
            'noResponds' => 'Без откликов',
            'period' => 'Период'
        ];
    }

    public function getPeriods(): array
    {
        return
            [
                self::PERIOD_1_HOUR => self::NAME_PERIOD_1,
                self::PERIOD_12_HOURS => self::NAME_PERIOD_12,
                self::PERIOD_24_HOURS => self::NAME_PERIOD_24,
                self::PERIOD_ALL => self::NAME_PERIOD_ALL
            ];
    }

    public function rules(): array
    {
        return [
            ['categories', 'each',
                'rule' => ['exist', 'targetClass' => Categories::class,
                    'targetAttribute' => ['categories' => 'id']]],
            [['noResponds', 'remote'], 'boolean'],
            ['period', 'in', 'range' => array_keys($this->getPeriods())],
        ];
    }

    /**
     * @param ActiveQuery $tasks
     * @return ActiveQuery
     */
    public function filter(ActiveQuery $tasks): ActiveQuery
    {
        if ($this->noResponds) {
            $tasks->joinWith(['responds'])->where(['taskId' => null]);
        }
        if ($this->categories) {
            $tasks->andWhere(['in', 'categoryId', $this->categories]);
        }
        if ($this->remote) {
            $tasks->andWhere(['city' => null]);
        }
        if (array_key_exists($this->period, $this->getPeriods())) {
            switch ($this->period) {
                case self::PERIOD_1_HOUR:
                    $tasks->andWhere('tasks.createAt >= NOW() - INTERVAL 1 HOUR');
                    break;
                case self::PERIOD_12_HOURS:
                    $tasks->andWhere('tasks.createAt >= NOW() - INTERVAL 12 HOUR');
                    break;
                case self::PERIOD_24_HOURS:
                    $tasks->andWhere('tasks.createAt >= NOW() - INTERVAL 24 HOUR');
                    break;
                case self::PERIOD_ALL:
                    break;
            }
        }

        return $tasks;
    }
}
