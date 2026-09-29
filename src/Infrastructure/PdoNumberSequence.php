<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Infrastructure;

use Flexgrid\Modules\AdminCore\Contract\NumberSequenceInterface;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;

final class PdoNumberSequence implements NumberSequenceInterface
{
    private const TABLE = 'admin_core_number_sequence';

    /** @var \PDO */
    private $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function reserve(TenantId $tenantId, string $sequenceKey, string $periodKey): int
    {
        $sequenceKey = $this->validateKey($sequenceKey, 64, 'Sequence key');
        $periodKey = $this->validateKey($periodKey, 32, 'Period key');

        if (!$this->connection->inTransaction()) {
            throw new \LogicException('Een documentnummer kan alleen binnen een actieve transactie worden gereserveerd.');
        }

        $upsert = $this->connection->prepare(
            'INSERT INTO `' . self::TABLE . '` '
            . '(`tenant_id`, `sequence_key`, `period_key`, `current_value`) '
            . 'VALUES (:tenant_id, :sequence_key, :period_key, 1) '
            . 'ON DUPLICATE KEY UPDATE `current_value` = `current_value` + 1'
        );
        $values = [
            ':tenant_id' => $tenantId->toString(),
            ':sequence_key' => $sequenceKey,
            ':period_key' => $periodKey,
        ];
        $upsert->execute($values);

        $select = $this->connection->prepare(
            'SELECT `current_value` FROM `' . self::TABLE . '` '
            . 'WHERE `tenant_id` = :tenant_id '
            . 'AND `sequence_key` = :sequence_key '
            . 'AND `period_key` = :period_key '
            . 'FOR UPDATE'
        );
        $select->execute($values);
        $value = $select->fetchColumn();

        if ($value === false || !is_numeric($value) || (string)(int)$value !== (string)$value) {
            throw new \RuntimeException('De gereserveerde nummerreekswaarde is ongeldig.');
        }

        $value = (int)$value;
        if ($value < 1) {
            throw new \RuntimeException('De gereserveerde nummerreekswaarde moet positief zijn.');
        }

        return $value;
    }

    private function validateKey(string $value, int $maximumLength, string $label): string
    {
        $value = trim($value);

        if ($value === '' || strlen($value) > $maximumLength) {
            throw new \InvalidArgumentException($label . ' moet 1 tot ' . $maximumLength . ' tekens bevatten.');
        }

        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]*$/D', $value) !== 1) {
            throw new \InvalidArgumentException($label . ' bevat ongeldige tekens.');
        }

        return $value;
    }
}
