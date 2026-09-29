<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Infrastructure;

use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;

final class PdoTransactionManager implements TransactionManagerInterface
{
    /** @var \PDO */
    private $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function transactional(callable $operation)
    {
        $ownsTransaction = !$this->connection->inTransaction();

        if ($ownsTransaction && !$this->connection->beginTransaction()) {
            throw new \RuntimeException('Databasetransactie kon niet worden gestart.');
        }

        try {
            $result = $operation();

            if ($ownsTransaction && !$this->connection->commit()) {
                throw new \RuntimeException('Databasetransactie kon niet worden vastgelegd.');
            }

            return $result;
        } catch (\Throwable $throwable) {
            if ($ownsTransaction && $this->connection->inTransaction()) {
                try {
                    $this->connection->rollBack();
                } catch (\Throwable $rollbackFailure) {
                    throw new \RuntimeException(
                        'Rollback is mislukt na transactiefout: ' . $rollbackFailure->getMessage(),
                        0,
                        $throwable
                    );
                }
            }

            throw $throwable;
        }
    }

    public function getConnection(): \PDO
    {
        return $this->connection;
    }
}
