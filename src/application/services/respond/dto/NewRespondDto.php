<?php

namespace omarinina\application\services\respond\dto;

class NewRespondDto
{
    public function __construct(
        public readonly int $userId,
        public int $taskId,
        public ?array $attributes = null,
    ) {
    }
}
