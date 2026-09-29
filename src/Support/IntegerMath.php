<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Support;

final class IntegerMath
{
    private function __construct()
    {
    }

    public static function add(int $left, int $right): int
    {
        if ($right > 0 && $left > PHP_INT_MAX - $right) {
            throw new \OverflowException('Integeroptelling valt buiten het ondersteunde bereik.');
        }

        if ($right < 0 && $left < PHP_INT_MIN - $right) {
            throw new \OverflowException('Integeroptelling valt buiten het ondersteunde bereik.');
        }

        return $left + $right;
    }

    public static function subtract(int $left, int $right): int
    {
        if ($right === PHP_INT_MIN) {
            throw new \OverflowException('Integeraftrekking valt buiten het ondersteunde bereik.');
        }

        return self::add($left, -$right);
    }

    public static function multiplyAndDivideHalfUp(int $left, int $right, int $divisor): int
    {
        if ($divisor <= 0) {
            throw new \InvalidArgumentException('Deler moet groter dan nul zijn.');
        }

        if ($left === 0 || $right === 0) {
            return 0;
        }

        if ($left === PHP_INT_MIN || $right === PHP_INT_MIN) {
            throw new \OverflowException('Integervermenigvuldiging valt buiten het ondersteunde bereik.');
        }

        $negative = ($left < 0) !== ($right < 0);
        $left = abs($left);
        $right = abs($right);

        $value = max($left, $right);
        $multiplier = min($left, $right);
        $whole = intdiv($value, $divisor);
        $remainder = $value % $divisor;

        $wholeResult = self::multiply($whole, $multiplier);
        $remainderProduct = self::multiply($remainder, $multiplier);
        $fractionResult = intdiv($remainderProduct, $divisor);
        $fractionRemainder = $remainderProduct % $divisor;

        if ($fractionRemainder >= intdiv($divisor, 2) + ($divisor % 2)) {
            $fractionResult = self::add($fractionResult, 1);
        }

        $result = self::add($wholeResult, $fractionResult);

        return $negative ? -$result : $result;
    }

    private static function multiply(int $left, int $right): int
    {
        if ($left === 0 || $right === 0) {
            return 0;
        }

        if ($left > intdiv(PHP_INT_MAX, $right)) {
            throw new \OverflowException('Integervermenigvuldiging valt buiten het ondersteunde bereik.');
        }

        return $left * $right;
    }
}
