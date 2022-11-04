<?php

declare(strict_types=1);

use omarinina\domain\models\task\Tasks;
use omarinina\domain\models\user\Roles;

unset(Roles::findOne(['role' => 'executor'])->users);
$executors = array_map(
    function ($users) {
        return $users->id;
    },
    Roles::findOne(['role' => 'executor'])->users
);

$tasks = Tasks::find()->select('id')->asArray()->all();

/**
 * @var $faker \Faker\Generator
 */
return [
    'taskId' => $faker->randomElement($tasks)['id'],
    'executorId' => $faker->randomElement($executors),
    'price' => $faker->randomNumber(4, true),
    'comment' => $faker->text()
];
