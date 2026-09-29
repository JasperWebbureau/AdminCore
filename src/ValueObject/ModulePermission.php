<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\ValueObject;

final class ModulePermission
{
    /** @var string */
    private $key;

    public function __construct(string $key)
    {
        $key = strtolower(trim($key));

        if (preg_match('/^[a-z][a-z0-9]*(?:[._-][a-z0-9]+)+$/D', $key) !== 1) {
            throw new \InvalidArgumentException('Permission key moet een stabiele module.actie-notatie gebruiken.');
        }

        if (strlen($key) > 128) {
            throw new \InvalidArgumentException('Permission key mag maximaal 128 tekens bevatten.');
        }

        $this->key = $key;
    }

    public function toString(): string
    {
        return $this->key;
    }

    public function equals(ModulePermission $other): bool
    {
        return $this->key === $other->key;
    }

    public function __toString(): string
    {
        return $this->key;
    }
}
