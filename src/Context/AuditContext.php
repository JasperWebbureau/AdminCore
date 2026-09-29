<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Context;

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;

final class AuditContext
{
    /** @var TenantId */
    private $tenantId;

    /** @var string */
    private $actorType;

    /** @var string */
    private $actorId;

    /** @var string */
    private $requestId;

    /** @var string|null */
    private $correlationId;

    /** @var int */
    private $timestamp;

    public function __construct(
        TenantId $tenantId,
        string $actorType,
        string $actorId,
        string $requestId,
        int $timestamp,
        ?string $correlationId = null
    ) {
        $this->tenantId = $tenantId;
        $this->actorType = self::requiredIdentifier($actorType, 'Actortype');
        $this->actorId = self::requiredIdentifier($actorId, 'Actor-id');
        $this->requestId = self::requiredIdentifier($requestId, 'Request-id');
        $this->correlationId = self::optionalIdentifier($correlationId, 'Correlation-id');

        if ($timestamp < 0) {
            throw new \InvalidArgumentException('Audit-timestamp mag niet negatief zijn.');
        }

        $this->timestamp = $timestamp;
    }

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function getActorType(): string
    {
        return $this->actorType;
    }

    public function getActorId(): string
    {
        return $this->actorId;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId->toString(),
            'actor_type' => $this->actorType,
            'actor_id' => $this->actorId,
            'request_id' => $this->requestId,
            'correlation_id' => $this->correlationId,
            'occurred_at' => gmdate('Y-m-d\TH:i:s\Z', $this->timestamp),
        ];
    }

    private static function requiredIdentifier(string $value, string $label): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new \InvalidArgumentException($label . ' mag niet leeg zijn.');
        }

        if (strlen($value) > 128) {
            throw new \InvalidArgumentException($label . ' mag maximaal 128 tekens bevatten.');
        }

        return $value;
    }

    private static function optionalIdentifier(?string $value, string $label): ?string
    {
        if ($value === null) {
            return null;
        }

        return self::requiredIdentifier($value, $label);
    }
}
