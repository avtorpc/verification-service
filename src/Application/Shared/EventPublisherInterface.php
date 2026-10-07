<?php

namespace App\Application\Shared;

interface EventPublisherInterface
{
    public function publish(array $event): void;
}
