<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Flexgrid\Modules\AdminCore\Context\AuditContext;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminCore\Event\DomainEvent;
use Flexgrid\Modules\AdminCore\Event\PendingDomainEvents;
use Flexgrid\Modules\AdminCore\Event\SynchronousEventBus;
use Flexgrid\Modules\AdminCore\Event\TransactionalEventDispatcher;
use Flexgrid\Modules\AdminCore\Exception\EventBatchDispatchException;
use Flexgrid\Modules\AdminCore\Exception\EventDispatchException;
use Flexgrid\Modules\AdminCore\Infrastructure\PdoTransactionManager;
use Flexgrid\Modules\AdminCore\Infrastructure\PdoNumberSequence;
use Flexgrid\Modules\AdminCore\ValueObject\ModulePermission;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;

$tenantId = new TenantId('default');
$audit = new AuditContext($tenantId, 'user', '42', 'request-1', 0, 'correlation-1');
$auditData = $audit->toArray();
adminCoreAssert($auditData['tenant_id'] === 'default', 'Auditcontext moet de tenant vastleggen.');
adminCoreAssert($auditData['occurred_at'] === '1970-01-01T00:00:00Z', 'Auditcontext moet tijd in UTC serialiseren.');

$permission = new ModulePermission('invoice.finalize');
adminCoreAssert($permission->toString() === 'invoice.finalize', 'Permission key moet stabiel blijven.');
adminCoreAssertThrows(InvalidArgumentException::class, function (): void {
    new ModulePermission('finalize');
}, 'Permission key zonder moduledeel moet worden geweigerd.');

$event = new DomainEvent(
    '82fa38d8-8cab-40cb-951a-b594a9e26488',
    'invoice.finalized',
    1,
    $tenantId,
    'invoice',
    '014a695c-0828-46d9-869f-e79045e50fd8',
    0,
    ['total_minor' => 12100, 'currency' => 'EUR'],
    'correlation-1'
);
adminCoreAssert($event->toArray()['occurred_at'] === '1970-01-01T00:00:00Z', 'Eventtijd moet in UTC serialiseren.');
adminCoreAssertThrows(InvalidArgumentException::class, function () use ($tenantId): void {
    new DomainEvent('event-1', 'invoice.finalized', 1, $tenantId, 'invoice', 'invoice-1', 0, ['amount' => 1.5]);
}, 'Eventpayloads mogen geen floats bevatten.');

$pending = new PendingDomainEvents();
$pending->record($event);
adminCoreAssert($pending->count() === 1, 'Pending events moet events verzamelen.');
adminCoreAssert(count($pending->release()) === 1 && $pending->count() === 0, 'Release moet pending events leegmaken.');

$calls = [];
$bus = new SynchronousEventBus();
$bus->subscribe('invoice.finalized', function (DomainEvent $published) use (&$calls): void {
    $calls[] = 'specific:' . $published->getEventId();
});
$bus->subscribe('*', function () use (&$calls): void {
    $calls[] = 'wildcard';
});
$bus->publish($event);
adminCoreAssert(count($calls) === 2, 'Eventbus moet specifieke en wildcardhandlers uitvoeren.');

$failureCalls = 0;
$failingBus = new SynchronousEventBus();
$failingBus->subscribe('invoice.finalized', function (): void {
    throw new RuntimeException('Handler failure');
});
$failingBus->subscribe('invoice.finalized', function () use (&$failureCalls): void {
    $failureCalls++;
});
adminCoreAssertThrows(EventDispatchException::class, function () use ($failingBus, $event): void {
    $failingBus->publish($event);
}, 'Eventbus moet handlerfouten na uitvoering rapporteren.');
adminCoreAssert($failureCalls === 1, 'Een falende handler mag volgende handlers niet blokkeren.');

$secondEvent = new DomainEvent(
    '6d443cab-f1bf-44f9-847d-dc33743d95b0',
    'customer.created',
    1,
    $tenantId,
    'customer',
    'ef804760-9a1c-4397-8ae0-c71dc9281bf9',
    0
);
$batchCalls = 0;
$batchBus = new SynchronousEventBus();
$batchBus->subscribe('invoice.finalized', function (): void {
    throw new RuntimeException('First event failed');
});
$batchBus->subscribe('customer.created', function () use (&$batchCalls): void {
    $batchCalls++;
});
adminCoreAssertThrows(EventBatchDispatchException::class, function () use ($batchBus, $event, $secondEvent): void {
    $batchBus->publishAll([$event, $secondEvent]);
}, 'Eventbatch moet handlerfouten na aanbieding van alle events rapporteren.');
adminCoreAssert($batchCalls === 1, 'Een falend event mag latere events in dezelfde batch niet blokkeren.');

$trace = new ArrayObject();
$traceTransactions = new class($trace) implements TransactionManagerInterface {
    /** @var ArrayObject */
    private $trace;

    public function __construct(ArrayObject $trace)
    {
        $this->trace = $trace;
    }

    public function transactional(callable $operation)
    {
        $this->trace->append('begin');
        try {
            $result = $operation();
            $this->trace->append('commit');
            return $result;
        } catch (Throwable $throwable) {
            $this->trace->append('rollback');
            throw $throwable;
        }
    }
};
$postCommitBus = new SynchronousEventBus();
$postCommitBus->subscribe('invoice.finalized', function () use ($trace): void {
    $trace->append('event');
});
$dispatcher = new TransactionalEventDispatcher($traceTransactions, $postCommitBus);
$dispatchResult = $dispatcher->execute(function (PendingDomainEvents $events) use ($trace, $event): string {
    $trace->append('work');
    $events->record($event);
    return 'done';
});
adminCoreAssert($dispatchResult === 'done', 'Dispatcher moet het use-caseresultaat teruggeven.');
adminCoreAssert(
    iterator_to_array($trace) === ['begin', 'work', 'commit', 'event'],
    'Domeinevents mogen pas na een succesvolle commit worden gepubliceerd.'
);

$eventsBeforeRollbackTest = $trace->count();
try {
    $dispatcher->execute(function (PendingDomainEvents $events) use ($event): void {
        $events->record($event);
        throw new RuntimeException('use-case failed');
    });
} catch (RuntimeException $exception) {
    adminCoreAssert($exception->getMessage() === 'use-case failed', 'Use-casefout moet na rollback behouden blijven.');
}
adminCoreAssert(
    $trace->count() === $eventsBeforeRollbackTest + 2,
    'Rollback mag geen verzamelde domeinevents publiceren.'
);

if (in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE transaction_test (value INTEGER NOT NULL)');
    $transactions = new PdoTransactionManager($pdo);

    $result = $transactions->transactional(function () use ($pdo): string {
        $pdo->exec('INSERT INTO transaction_test (value) VALUES (1)');
        return 'committed';
    });
    adminCoreAssert($result === 'committed', 'Transactie moet callbackresultaat teruggeven.');

    try {
        $transactions->transactional(function () use ($pdo): void {
            $pdo->exec('INSERT INTO transaction_test (value) VALUES (2)');
            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException $exception) {
        adminCoreAssert($exception->getMessage() === 'rollback', 'Originele transactiefout moet behouden blijven.');
    }

    $count = (int)$pdo->query('SELECT COUNT(*) FROM transaction_test')->fetchColumn();
    adminCoreAssert($count === 1, 'Transactieadapter moet bij iedere Throwable rollbacken.');

    $sequence = new PdoNumberSequence($pdo);
    adminCoreAssertThrows(LogicException::class, function () use ($sequence, $tenantId): void {
        $sequence->reserve($tenantId, 'invoice', '2026');
    }, 'Nummerreservering buiten een actieve transactie moet worden geweigerd.');
}

echo "AdminCore infrastructure tests passed.\n";
