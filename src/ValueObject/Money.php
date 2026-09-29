<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\ValueObject;

use Flexgrid\Modules\AdminCore\Support\IntegerMath;

final class Money
{
    /** @var int */
    private $minorUnits;

    /** @var Currency */
    private $currency;

    public function __construct(int $minorUnits, Currency $currency)
    {
        $this->minorUnits = $minorUnits;
        $this->currency = $currency;
    }

    public static function fromDecimal(string $amount, Currency $currency): Money
    {
        $amount = trim($amount);

        if (preg_match('/^([+-]?)([0-9]+)(?:[,.]([0-9]+))?$/D', $amount, $matches) !== 1) {
            throw new \InvalidArgumentException('Bedrag heeft geen geldige decimale notatie.');
        }

        $fraction = isset($matches[3]) ? $matches[3] : '';
        $minorDigits = $currency->getMinorDigits();

        if (strlen($fraction) > $minorDigits) {
            throw new \InvalidArgumentException('Bedrag bevat meer decimalen dan de valuta ondersteunt.');
        }

        $minorString = ltrim($matches[2] . str_pad($fraction, $minorDigits, '0'), '0');
        $minorString = $minorString === '' ? '0' : $minorString;

        if (strlen($minorString) > strlen((string)PHP_INT_MAX)
            || (strlen($minorString) === strlen((string)PHP_INT_MAX) && strcmp($minorString, (string)PHP_INT_MAX) > 0)
        ) {
            throw new \OverflowException('Bedrag valt buiten het ondersteunde integerbereik.');
        }

        $minorUnits = (int)$minorString;
        if ($matches[1] === '-' && $minorUnits !== 0) {
            $minorUnits = -$minorUnits;
        }

        return new self($minorUnits, $currency);
    }

    public static function zero(Currency $currency): Money
    {
        return new self(0, $currency);
    }

    public function getMinorUnits(): int
    {
        return $this->minorUnits;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function add(Money $other): Money
    {
        $this->assertSameCurrency($other);

        return new self(IntegerMath::add($this->minorUnits, $other->minorUnits), $this->currency);
    }

    public function subtract(Money $other): Money
    {
        $this->assertSameCurrency($other);

        return new self(IntegerMath::subtract($this->minorUnits, $other->minorUnits), $this->currency);
    }

    public function multiplyScaled(int $quantity, int $scale = 4): Money
    {
        if ($scale < 0 || $scale > 8) {
            throw new \InvalidArgumentException('Schaal moet tussen 0 en 8 liggen.');
        }

        $divisor = 1;
        for ($position = 0; $position < $scale; $position++) {
            $divisor *= 10;
        }

        return new self(
            IntegerMath::multiplyAndDivideHalfUp($this->minorUnits, $quantity, $divisor),
            $this->currency
        );
    }

    public function negate(): Money
    {
        if ($this->minorUnits === PHP_INT_MIN) {
            throw new \OverflowException('Bedrag kan niet veilig negatief worden gemaakt.');
        }

        return new self(-$this->minorUnits, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->minorUnits === 0;
    }

    public function equals(Money $other): bool
    {
        return $this->minorUnits === $other->minorUnits
            && $this->currency->equals($other->currency);
    }

    public function format(string $decimalSeparator = ',', string $thousandsSeparator = '.'): string
    {
        $negative = $this->minorUnits < 0;
        if ($this->minorUnits === PHP_INT_MIN) {
            throw new \OverflowException('Bedrag kan niet veilig worden geformatteerd.');
        }

        $absolute = (string)abs($this->minorUnits);
        $minorDigits = $this->currency->getMinorDigits();

        if ($minorDigits > 0) {
            $absolute = str_pad($absolute, $minorDigits + 1, '0', STR_PAD_LEFT);
            $whole = substr($absolute, 0, -$minorDigits);
            $fraction = substr($absolute, -$minorDigits);
        } else {
            $whole = $absolute;
            $fraction = '';
        }

        $groups = [];
        while (strlen($whole) > 3) {
            array_unshift($groups, substr($whole, -3));
            $whole = substr($whole, 0, -3);
        }
        array_unshift($groups, $whole);

        $formatted = implode($thousandsSeparator, $groups);
        if ($minorDigits > 0) {
            $formatted .= $decimalSeparator . $fraction;
        }

        return ($negative ? '-' : '') . $formatted;
    }

    private function assertSameCurrency(Money $other): void
    {
        if (!$this->currency->equals($other->currency)) {
            throw new \InvalidArgumentException('Geldbedragen met verschillende valuta kunnen niet worden gecombineerd.');
        }
    }
}
