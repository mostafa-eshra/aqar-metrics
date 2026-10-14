<?php

declare(strict_types=1);

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class DecimalMath
{
    public static function add(string $augend, string $addend, int $scale = 2): string
    {
        return (string) BigDecimal::of($augend)
            ->plus($addend)
            ->toScale($scale, RoundingMode::HalfUp);
    }

    public static function subtract(string $minuend, string $subtrahend, int $scale = 2): string
    {
        return (string) BigDecimal::of($minuend)
            ->minus($subtrahend)
            ->toScale($scale, RoundingMode::HalfUp);
    }

    public static function multiply(string $multiplicand, string $multiplier, int $scale = 2): string
    {
        return (string) BigDecimal::of($multiplicand)
            ->multipliedBy($multiplier)
            ->toScale($scale, RoundingMode::HalfUp);
    }

    public static function divide(string $dividend, string $divisor, int $scale = 2): string
    {
        return (string) BigDecimal::of($dividend)
            ->dividedBy($divisor, $scale, RoundingMode::HalfUp);
    }

    public static function percentage(string $amount, string $percent, int $scale = 2): string
    {
        return (string) BigDecimal::of($amount)
            ->multipliedBy($percent)
            ->dividedBy('100', $scale, RoundingMode::HalfUp);
    }

    public static function ratioPercentage(string $numerator, string $denominator, int $scale = 2): string
    {
        return (string) BigDecimal::of($numerator)
            ->multipliedBy('100')
            ->dividedBy($denominator, $scale, RoundingMode::HalfUp);
    }

    public static function compare(string $left, string $right): int
    {
        return BigDecimal::of($left)->compareTo($right);
    }

    public static function isZero(string $value): bool
    {
        return BigDecimal::of($value)->isZero();
    }

    /** @param list<string> $values */
    public static function sum(array $values, int $scale = 2): string
    {
        $total = BigDecimal::zero();

        foreach ($values as $value) {
            $total = $total->plus($value);
        }

        return (string) $total->toScale($scale, RoundingMode::HalfUp);
    }
}
