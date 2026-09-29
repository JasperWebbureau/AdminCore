<?php

declare(strict_types=1);

$source = dirname(__DIR__) . '/src';
$flexgridSource = dirname(__DIR__, 3) . '/flexgrid/src';

require_once $source . '/ValueObject/TenantId.php';
require_once $source . '/Context/TenantContext.php';
require_once $source . '/ValueObject/Currency.php';
require_once $source . '/Support/IntegerMath.php';
require_once $source . '/ValueObject/Money.php';
require_once $source . '/ValueObject/TaxRate.php';
require_once $source . '/Contract/PublicIdGeneratorInterface.php';
require_once $source . '/Infrastructure/UuidV4Generator.php';
require_once $source . '/Contract/TransactionManagerInterface.php';
require_once $source . '/Contract/NumberSequenceInterface.php';
require_once $source . '/Contract/EventBusInterface.php';
require_once $source . '/Infrastructure/PdoTransactionManager.php';
require_once $source . '/Infrastructure/PdoNumberSequence.php';
require_once $source . '/Context/AuditContext.php';
require_once $source . '/ValueObject/ModulePermission.php';
require_once $source . '/Event/DomainEvent.php';
require_once $source . '/Event/PendingDomainEvents.php';
require_once $source . '/Exception/EventDispatchException.php';
require_once $source . '/Exception/EventBatchDispatchException.php';
require_once $source . '/Event/SynchronousEventBus.php';
require_once $source . '/Event/TransactionalEventDispatcher.php';
require_once $source . '/Integration/Flexgrid/Service/AdministrationPanelService.php';
require_once $flexgridSource . '/Utils/_Time.php';

function adminCoreAssert($condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function adminCoreAssertThrows(string $exceptionClass, callable $callback, string $message): void
{
    try {
        $callback();
    } catch (Throwable $throwable) {
        if ($throwable instanceof $exceptionClass) {
            return;
        }

        throw new RuntimeException($message . ' Ontvangen: ' . get_class($throwable));
    }

    throw new RuntimeException($message . ' Er werd geen exception gegooid.');
}
