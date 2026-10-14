<?php

use App\Support\DecimalMath;

it('calculates source values with exact decimal arithmetic', function () {
    expect(DecimalMath::multiply('8280', '80000'))->toBe('662400000.00')
        ->and(DecimalMath::percentage('2442600000', '2.5'))->toBe('61065000.00')
        ->and(DecimalMath::divide('24840', '3', 4))->toBe('8280.0000');
});

it('rounds monetary results half up at the configured scale', function () {
    expect(DecimalMath::divide('1', '8', 2))->toBe('0.13')
        ->and(DecimalMath::sum(['0.10', '0.20'], 2))->toBe('0.30');
});

it('supports exact totals differences comparisons and ratios', function () {
    expect(DecimalMath::add('1.10', '2.20'))->toBe('3.30')
        ->and(DecimalMath::subtract('3.30', '1.10'))->toBe('2.20')
        ->and(DecimalMath::ratioPercentage('1', '8'))->toBe('12.50')
        ->and(DecimalMath::compare('1.00', '1'))->toBe(0)
        ->and(DecimalMath::isZero('0.000'))->toBeTrue();
});
