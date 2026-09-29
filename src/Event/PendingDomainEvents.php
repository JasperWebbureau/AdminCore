<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Event;

final class PendingDomainEvents
{
    /** @var DomainEvent[] */
    private $events = [];

    public function record(DomainEvent $event): void
    {
        $this->events[] = $event;
    }

    /** @return DomainEvent[] */
    public function all(): array
    {
        return $this->events;
    }

    /** @return DomainEvent[] */
    public function release(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }

    public function clear(): void
    {
        $this->events = [];
    }

    public function count(): int
    {
        return count($this->events);
    }
}
