<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\ValueObject;

final class TenantId
{
    /** @var string */
    private $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new \InvalidArgumentException('Tenant-id mag niet leeg zijn.');
        }

        if (strlen($value) > 64) {
            throw new \InvalidArgumentException('Tenant-id mag maximaal 64 tekens bevatten.');
        }

        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/D', $value) !== 1) {
            throw new \InvalidArgumentException('Tenant-id bevat ongeldige tekens.');
        }

        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(TenantId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
