<?php

namespace omarinina\application\services\task\interfaces;

use omarinina\application\services\task\dto\NewTaskDto;
use omarinina\domain\models\task\Tasks;

interface TaskCreateInterface
{
    /**
     * @param NewTaskDto $dto
     * @return Tasks
     */
    public function createNewTask(NewTaskDto $dto) : Tasks;
}
