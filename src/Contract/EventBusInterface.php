<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Contract;

use Flexgrid\Modules\AdminCore\Event\DomainEvent;

interface EventBusInterface
{
    public function publish(DomainEvent $event): void;

    /** @param DomainEvent[] $events */
    public function publishAll(array $events): void;
}
