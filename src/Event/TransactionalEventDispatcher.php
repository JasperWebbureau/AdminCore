<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Event;

use Flexgrid\Modules\AdminCore\Contract\EventBusInterface;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;

final class TransactionalEventDispatcher
{
    /** @var TransactionManagerInterface */
    private $transactions;

    /** @var EventBusInterface */
    private $eventBus;

    public function __construct(TransactionManagerInterface $transactions, EventBusInterface $eventBus)
    {
        $this->transactions = $transactions;
        $this->eventBus = $eventBus;
    }

    /**
     * De operation ontvangt een PendingDomainEvents als eerste argument.
     *
     * @param callable $operation
     * @return mixed
     */
    public function execute(callable $operation)
    {
        $pendingEvents = new PendingDomainEvents();

        try {
            $result = $this->transactions->transactional(function () use ($operation, $pendingEvents) {
                return $operation($pendingEvents);
            });
        } catch (\Throwable $throwable) {
            $pendingEvents->clear();
            throw $throwable;
        }

        $this->eventBus->publishAll($pendingEvents->release());

        return $result;
    }
}
