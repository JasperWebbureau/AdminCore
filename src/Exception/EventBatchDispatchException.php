<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Exception;

final class EventBatchDispatchException extends \RuntimeException
{
    /** @var EventDispatchException[] */
    private $dispatchFailures;

    /** @param EventDispatchException[] $dispatchFailures */
    public function __construct(array $dispatchFailures)
    {
        $this->dispatchFailures = $dispatchFailures;

        parent::__construct(
            sprintf(
                '%d domeinevent(s) hadden één of meer falende handlers; alle events zijn aangeboden.',
                count($dispatchFailures)
            ),
            0,
            isset($dispatchFailures[0]) ? $dispatchFailures[0] : null
        );
    }

    /** @return EventDispatchException[] */
    public function getDispatchFailures(): array
    {
        return $this->dispatchFailures;
    }
}
