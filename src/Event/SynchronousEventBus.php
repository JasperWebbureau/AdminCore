<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Event;

use Flexgrid\Modules\AdminCore\Contract\EventBusInterface;
use Flexgrid\Modules\AdminCore\Exception\EventBatchDispatchException;
use Flexgrid\Modules\AdminCore\Exception\EventDispatchException;

final class SynchronousEventBus implements EventBusInterface
{
    /** @var array<string, callable[]> */
    private $handlers = [];

    public function subscribe(string $eventName, callable $handler): void
    {
        $eventName = strtolower(trim($eventName));

        if ($eventName === '') {
            throw new \InvalidArgumentException('Eventnaam voor een subscription mag niet leeg zijn.');
        }

        if (!isset($this->handlers[$eventName])) {
            $this->handlers[$eventName] = [];
        }

        $this->handlers[$eventName][] = $handler;
    }

    public function publish(DomainEvent $event): void
    {
        $handlers = array_merge(
            isset($this->handlers[$event->getName()]) ? $this->handlers[$event->getName()] : [],
            isset($this->handlers['*']) ? $this->handlers['*'] : []
        );
        $failures = [];

        foreach ($handlers as $handler) {
            try {
                $handler($event);
            } catch (\Throwable $throwable) {
                $failures[] = $throwable;
            }
        }

        if ($failures !== []) {
            throw new EventDispatchException($event, $failures);
        }
    }

    public function publishAll(array $events): void
    {
        $dispatchFailures = [];

        foreach ($events as $event) {
            if (!$event instanceof DomainEvent) {
                throw new \InvalidArgumentException('EventBus accepteert alleen DomainEvent-instanties.');
            }

            try {
                $this->publish($event);
            } catch (EventDispatchException $exception) {
                $dispatchFailures[] = $exception;
            }
        }

        if ($dispatchFailures !== []) {
            throw new EventBatchDispatchException($dispatchFailures);
        }
    }
}
