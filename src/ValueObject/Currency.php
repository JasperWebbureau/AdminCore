<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\ValueObject;

final class Currency
{
    /** @var string */
    private $code;

    /** @var int */
    private $minorDigits;

    public function __construct(string $code, int $minorDigits = 2)
    {
        $code = strtoupper(trim($code));

        if (preg_match('/^[A-Z]{3}$/D', $code) !== 1) {
            throw new \InvalidArgumentException('Valutacode moet uit drie letters bestaan.');
        }

        if ($minorDigits < 0 || $minorDigits > 4) {
            throw new \InvalidArgumentException('Aantal decimalen moet tussen 0 en 4 liggen.');
        }

        $this->code = $code;
        $this->minorDigits = $minorDigits;
    }

    public static function euro(): Currency
    {
        return new self('EUR', 2);
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getMinorDigits(): int
    {
        return $this->minorDigits;
    }

    public function equals(Currency $other): bool
    {
        return $this->code === $other->code
            && $this->minorDigits === $other->minorDigits;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
