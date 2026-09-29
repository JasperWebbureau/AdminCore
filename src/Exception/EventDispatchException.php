<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Exception;

use Flexgrid\Modules\AdminCore\Event\DomainEvent;

final class EventDispatchException extends \RuntimeException
{
    /** @var DomainEvent */
    private $event;

    /** @var \Throwable[] */
    private $failures;

    /** @param \Throwable[] $failures */
    public function __construct(DomainEvent $event, array $failures)
    {
        $this->event = $event;
        $this->failures = $failures;

        parent::__construct(
            sprintf(
                '%d handler(s) voor domeinevent %s zijn mislukt; de bus heeft alle handlers uitgevoerd.',
                count($failures),
                $event->getName()
            ),
            0,
            isset($failures[0]) ? $failures[0] : null
        );
    }

    public function getEvent(): DomainEvent
    {
        return $this->event;
    }

    /** @return \Throwable[] */
    public function getFailures(): array
    {
        return $this->failures;
    }
}
