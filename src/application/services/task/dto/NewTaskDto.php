<?php

namespace omarinina\application\services\task\dto;

class NewTaskDto
{

    /**
     * @param array $attributes
     * @param int $userId
     * @param string|null $formExpiryDate
     * @param object|null $geoObject
     */
    public function __construct(
        public array $attributes,
        public int $userId,
        public ?string $formExpiryDate,
        public ?object $geoObject
    ) {
    }
}
