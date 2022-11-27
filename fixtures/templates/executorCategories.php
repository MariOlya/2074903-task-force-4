<?php

declare(strict_types=1);

use omarinina\domain\models\user\Roles;

unset(Roles::findOne(['role' => 'executor'])->users);
$executors = array_map(
    static function ($users) {
        return $users->id;
    },
    Roles::findOne(['role' => 'executor'])->users
);

/**
 * @var $faker \Faker\Generator
 */
return [
    'executorId' => $faker->randomElement($executors),
    'categoryId' => $faker->numberBetween(1, 8)
];
