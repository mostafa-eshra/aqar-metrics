<?php

it('renders the Arabic RTL phase one foundation page', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertSee('lang="ar"', false)
        ->assertSee('dir="rtl"', false)
        ->assertSee('أساس موحّد لدراسات الجدوى العقارية')
        ->assertSee('حقول الدراسة التي يدخلها المستخدم')
        ->assertSee('flexible-phases-v2')
        ->assertSee('excel-source-v1');
});

it('defines the manual study fields and a complete default collection template', function () {
    $inputFields = config('aqar_metrics.input_fields');
    $collectionSchedule = config('aqar_metrics.collection_schedule');

    expect($inputFields)->toHaveCount(24)
        ->and($inputFields['commercial_sales_1_area']['unit'])->toBe('sqm')
        ->and($inputFields['commercial_sales_2_price_per_sqm']['unit'])->toBe('money_per_sqm');

    foreach ($collectionSchedule as $groupSchedule) {
        expect(array_sum($groupSchedule))->toBe(100);
    }
});
