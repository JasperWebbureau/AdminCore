<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\ValueObject;

use Flexgrid\Modules\AdminCore\Support\IntegerMath;

final class TaxRate
{
    /** @var int */
    private $basisPoints;

    public function __construct(int $basisPoints)
    {
        if ($basisPoints < 0 || $basisPoints > 10000) {
            throw new \InvalidArgumentException('Btw-tarief moet tussen 0 en 10000 basispunten liggen.');
        }

        $this->basisPoints = $basisPoints;
    }

    public static function fromPercentage(string $percentage): TaxRate
    {
        $percentage = trim($percentage);

        if (preg_match('/^([0-9]{1,3})(?:[,.]([0-9]{1,2}))?$/D', $percentage, $matches) !== 1) {
            throw new \InvalidArgumentException('Btw-percentage heeft geen geldige notatie.');
        }

        $fraction = isset($matches[2]) ? str_pad($matches[2], 2, '0') : '00';
        $basisPoints = ((int)$matches[1] * 100) + (int)$fraction;

        return new self($basisPoints);
    }

    public function getBasisPoints(): int
    {
        return $this->basisPoints;
    }

    public function calculateTax(Money $net): Money
    {
        return new Money(
            IntegerMath::multiplyAndDivideHalfUp($net->getMinorUnits(), $this->basisPoints, 10000),
            $net->getCurrency()
        );
    }

    public function calculateGross(Money $net): Money
    {
        return $net->add($this->calculateTax($net));
    }

    public function equals(TaxRate $other): bool
    {
        return $this->basisPoints === $other->basisPoints;
    }
}
