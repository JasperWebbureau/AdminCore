<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Event;

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;

final class DomainEvent
{
    /** @var string */
    private $eventId;

    /** @var string */
    private $name;

    /** @var int */
    private $schemaVersion;

    /** @var TenantId */
    private $tenantId;

    /** @var string */
    private $aggregateType;

    /** @var string */
    private $aggregateId;

    /** @var int */
    private $occurredAt;

    /** @var array */
    private $payload;

    /** @var string|null */
    private $correlationId;

    /** @var string|null */
    private $causationId;

    public function __construct(
        string $eventId,
        string $name,
        int $schemaVersion,
        TenantId $tenantId,
        string $aggregateType,
        string $aggregateId,
        int $occurredAt,
        array $payload = [],
        ?string $correlationId = null,
        ?string $causationId = null
    ) {
        $this->eventId = self::identifier($eventId, 'Event-id');
        $this->name = self::eventName($name);

        if ($schemaVersion < 1) {
            throw new \InvalidArgumentException('Event-schemaversie moet minimaal 1 zijn.');
        }
        $this->schemaVersion = $schemaVersion;
        $this->tenantId = $tenantId;
        $this->aggregateType = self::eventName($aggregateType, 'Aggregate-type');
        $this->aggregateId = self::identifier($aggregateId, 'Aggregate-id');

        if ($occurredAt < 0) {
            throw new \InvalidArgumentException('Event-timestamp mag niet negatief zijn.');
        }
        $this->occurredAt = $occurredAt;

        self::assertPayloadValue($payload, 'payload', 0);
        $this->payload = $payload;
        $this->correlationId = self::optionalIdentifier($correlationId, 'Correlation-id');
        $this->causationId = self::optionalIdentifier($causationId, 'Causation-id');
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchemaVersion(): int
    {
        return $this->schemaVersion;
    }

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function getAggregateType(): string
    {
        return $this->aggregateType;
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function getOccurredAt(): int
    {
        return $this->occurredAt;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    public function getCausationId(): ?string
    {
        return $this->causationId;
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->name,
            'schema_version' => $this->schemaVersion,
            'tenant_id' => $this->tenantId->toString(),
            'aggregate_type' => $this->aggregateType,
            'aggregate_id' => $this->aggregateId,
            'occurred_at' => gmdate('Y-m-d\TH:i:s\Z', $this->occurredAt),
            'correlation_id' => $this->correlationId,
            'causation_id' => $this->causationId,
            'payload' => $this->payload,
        ];
    }

    private static function eventName(string $value, string $label = 'Eventnaam'): string
    {
        $value = strtolower(trim($value));

        if (preg_match('/^[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*$/D', $value) !== 1) {
            throw new \InvalidArgumentException($label . ' heeft geen geldige stabiele notatie.');
        }

        if (strlen($value) > 128) {
            throw new \InvalidArgumentException($label . ' mag maximaal 128 tekens bevatten.');
        }

        return $value;
    }

    private static function identifier(string $value, string $label): string
    {
        $value = trim($value);

        if ($value === '' || strlen($value) > 128) {
            throw new \InvalidArgumentException($label . ' moet 1 tot 128 tekens bevatten.');
        }

        return $value;
    }

    private static function optionalIdentifier(?string $value, string $label): ?string
    {
        return $value === null ? null : self::identifier($value, $label);
    }

    private static function assertPayloadValue($value, string $path, int $depth): void
    {
        if ($depth > 16) {
            throw new \InvalidArgumentException('Eventpayload is te diep genest bij ' . $path . '.');
        }

        if ($value === null || is_string($value) || is_int($value) || is_bool($value)) {
            return;
        }

        if (is_float($value)) {
            throw new \InvalidArgumentException('Floats zijn niet toegestaan in eventpayloads bij ' . $path . '.');
        }

        if (!is_array($value)) {
            throw new \InvalidArgumentException('Alleen scalars en arrays zijn toegestaan in eventpayloads bij ' . $path . '.');
        }

        foreach ($value as $key => $child) {
            self::assertPayloadValue($child, $path . '.' . (string)$key, $depth + 1);
        }
    }
}
