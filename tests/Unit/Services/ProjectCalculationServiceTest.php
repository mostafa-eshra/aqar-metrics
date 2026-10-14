<?php

declare(strict_types=1);

use App\Enums\CalculationVersion;
use App\Models\Project;
use App\Services\DTO\ProjectMetricsDTO;
use App\Services\ProjectCalculationService;
use App\Services\ProjectCollectionScheduleService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Tests\TestCase;

uses(TestCase::class);

it('matches all 66 calculated cells in the eight-year source workbook', function () {
    $project = makeCalculationProject([
        'calculation_version' => CalculationVersion::EXCEL_SOURCE_V1,
    ]);

    $metrics = ProjectCalculationService::calculate($project);
    $actualSourceCells = calculationSourceCells($metrics);
    $expectedSourceCells = json_decode(
        (string) file_get_contents(base_path('tests/Fixtures/calculation-source-results.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($actualSourceCells)
        ->toHaveCount(66)
        ->toBe($expectedSourceCells)
        ->and($metrics->indicators['profit_margin_percent'])->toBe('34.88')
        ->and($metrics->indicators['return_on_cost_percent'])->toBe('53.57')
        ->and($metrics->revenueContributionPercent)->toBe([
            'residential_phase_1' => '27.12',
            'residential_phase_2' => '32.20',
            'residential_phase_3' => '40.68',
            'commercial_group_1' => '0.00',
            'commercial_group_2' => '0.00',
        ])
        ->and($metrics->annualCollectionTotal)->toBe('2442600000.00')
        ->and($metrics->calculationNotes)->toContain('excel_source_percentage_expense_bases')
        ->and($metrics->unavailableReasons)->toBe([]);
});

it('keeps the calculation fixture synchronized with the original workbook', function () {
    $expectedSourceCells = json_decode(
        (string) file_get_contents(base_path('tests/Fixtures/calculation-source-results.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect(calculationWorkbookSourceCells($expectedSourceCells))
        ->toHaveCount(66)
        ->toBe($expectedSourceCells);
});

it('documents the land cost difference between corrected and source-compatible versions', function () {
    $correctedProject = makeCalculationProject([
        'land_cost' => '1000000',
        'calculation_version' => CalculationVersion::CORRECTED_V1,
    ]);
    $sourceCompatibleProject = makeCalculationProject([
        'land_cost' => '1000000',
        'calculation_version' => CalculationVersion::EXCEL_SOURCE_V1,
    ]);

    $correctedMetrics = ProjectCalculationService::calculate($correctedProject);
    $sourceCompatibleMetrics = ProjectCalculationService::calculate($sourceCompatibleProject);

    expect($correctedMetrics->totals)
        ->toBe([
            'total_cost' => '1621603000.00',
            'estimated_profit' => '820997000.00',
        ])
        ->and($correctedMetrics->landCostOccurrences)->toBe(1)
        ->and($sourceCompatibleMetrics->totals)
        ->toBe([
            'total_cost' => '1592588000.00',
            'estimated_profit' => '850012000.00',
        ])
        ->and($sourceCompatibleMetrics->landCostOccurrences)->toBe(2);
});

it('keeps the two commercial groups mapped to their own prices and collection schedules', function () {
    $project = makeCalculationProject([
        'commercial_sales_1_area' => '100',
        'commercial_sales_1_price_per_sqm' => '150000',
        'commercial_sales_2_area' => '200',
        'commercial_sales_2_price_per_sqm' => '200000',
    ]);

    $metrics = ProjectCalculationService::calculate($project);

    expect($metrics->revenues['commercial_group_1'])->toBe('15000000.00')
        ->and($metrics->revenues['commercial_group_2'])->toBe('40000000.00')
        ->and(array_column($metrics->annualCollections, 'total'))->toBe([
            '165600000.00',
            '299010000.00',
            '476000000.00',
            '440880000.00',
            '420180000.00',
            '402300000.00',
            '188270000.00',
            '105360000.00',
        ])
        ->and($metrics->annualCollectionTotal)->toBe('2497600000.00');
});

it('updates the related revenue collections expenses and profit when one residential price changes', function () {
    $project = makeCalculationProject([
        'residential_phase_1_price_per_sqm' => '90000',
    ]);

    $metrics = ProjectCalculationService::calculate($project);

    expect($metrics->revenues['residential_phase_1'])->toBe('745200000.00')
        ->and($metrics->revenues['residential_and_commercial_total'])->toBe('2525400000.00')
        ->and($metrics->annualCollections[0]['total'])->toBe('186300000.00')
        ->and($metrics->expenses['marketing'])->toBe('101016000.00')
        ->and($metrics->expenses['sales'])->toBe('126270000.00')
        ->and($metrics->expenses['administration'])->toBe('101016000.00')
        ->and($metrics->expenses['design_supervision'])->toBe('63135000.00')
        ->and($metrics->constructionCosts['total'])->toBe('1242000000.00')
        ->and($metrics->totals['estimated_profit'])->toBe('891963000.00');
});

it('updates land-dependent areas and costs while keeping infrastructure area independent', function () {
    $project = makeCalculationProject([
        'land_area' => '60000',
    ]);

    $metrics = ProjectCalculationService::calculate($project);

    expect($metrics->areas)->toBe([
        'maximum_buildable_area' => '36000.0000',
        'developer_saleable_area' => '21600.0000',
        'residential_phase_1_area' => '7200.0000',
        'residential_phase_2_area' => '7200.0000',
        'residential_phase_3_area' => '7200.0000',
    ])
        ->and($metrics->revenues['residential_total'])->toBe('2124000000.00')
        ->and($metrics->constructionCosts['residential'])->toBe('900000000.00')
        ->and($metrics->constructionCosts['infrastructure_landscape'])->toBe('207000000.00')
        ->and($metrics->constructionCosts['total'])->toBe('1107000000.00')
        ->and($metrics->expenses['design_supervision'])->toBe('53100000.00')
        ->and($metrics->totals['estimated_profit'])->toBe('687780000.00');
});

it('uses total revenue including hotel for every percentage expense', function () {
    $project = makeCalculationProject([
        'has_hotel' => true,
        'hotel_revenue' => '50000000',
        'hotel_construction_area' => '0',
    ]);

    $metrics = ProjectCalculationService::calculate($project);

    expect($metrics->revenues['hotel'])->toBe('50000000.00')
        ->and($metrics->revenues['total_with_hotel'])->toBe('2492600000.00')
        ->and($metrics->expenses['marketing'])->toBe('99704000.00')
        ->and($metrics->expenses['sales'])->toBe('124630000.00')
        ->and($metrics->expenses['administration'])->toBe('99704000.00')
        ->and($metrics->expenses['design_supervision'])->toBe('62315000.00')
        ->and($metrics->totals['estimated_profit'])->toBe('864247000.00')
        ->and($metrics->annualCollectionTotal)->toBe('2442600000.00')
        ->and($metrics->calculationNotes)->toContain('percentage_expense_base_is_total_revenue');
});

it('calculates every saved year and preserves the source totals when zero years extend the duration', function () {
    $project = makeCalculationProject([
        'project_duration_years' => 10,
        ...ProjectCollectionScheduleService::defaults(10),
    ]);

    $metrics = ProjectCalculationService::calculate($project);

    expect($metrics->annualCollections)->toHaveCount(10)
        ->and($metrics->annualCollections[8]['year'])->toBe(9)
        ->and($metrics->annualCollections[8]['total'])->toBe('0.00')
        ->and($metrics->annualCollections[9]['year'])->toBe(10)
        ->and($metrics->annualCollections[9]['total'])->toBe('0.00')
        ->and($metrics->annualCollectionTotal)->toBe('2442600000.00')
        ->and($metrics->unavailableReasons)->toBe([]);
});

it('keeps revenue cost and profit unchanged when only the valid collection duration changes', function (int $durationYears) {
    $project = makeCalculationProject([
        'project_duration_years' => $durationYears,
        ...calculationScheduleForDuration($durationYears),
    ]);

    $metrics = ProjectCalculationService::calculate($project);

    expect($metrics->annualCollections)->toHaveCount($durationYears)
        ->and($metrics->annualCollectionTotal)->toBe('2442600000.00')
        ->and($metrics->revenues['total_with_hotel'])->toBe('2442600000.00')
        ->and($metrics->totals['total_cost'])->toBe('1620603000.00')
        ->and($metrics->totals['estimated_profit'])->toBe('821997000.00')
        ->and($metrics->unavailableReasons)->toBe([]);
})->with([
    'one year' => 1,
    'five years' => 5,
    'source eight years' => 8,
    'ten years' => 10,
]);

it('returns precise dependency reasons for missing inputs and invalid collection schedules', function () {
    $residentialSchedule = makeCalculationProject()->residential_collection_schedule;
    unset($residentialSchedule['residential_phase_1'][8]);
    $project = makeCalculationProject([
        'land_area' => null,
        'residential_collection_schedule' => $residentialSchedule,
    ]);

    $metrics = ProjectCalculationService::calculate($project);

    expect($metrics->areas['maximum_buildable_area'])->toBeNull()
        ->and($metrics->constructionCosts['infrastructure_landscape'])->toBe('207000000.00')
        ->and($metrics->totals['estimated_profit'])->toBeNull()
        ->and($metrics->annualCollections[0]['residential_phase_1'])->toBeNull()
        ->and($metrics->annualCollections[0]['commercial_group_1'])->toBe('0.00')
        ->and($metrics->unavailableReasons['areas.maximum_buildable_area'])
        ->toBe(['missing_input.land_area'])
        ->and($metrics->unavailableReasons['annual_collections.1.residential_phase_1'])
        ->toBe(['missing_schedule_year.residential_phase_1.8']);
});

it('marks ratios unavailable when their denominator is zero', function () {
    $project = makeCalculationProject([
        'land_area' => '0',
        'land_cost' => '0',
        'commercial_sales_1_area' => '0',
        'commercial_sales_2_area' => '0',
        'hotel_construction_area' => '0',
        'commercial_construction_area' => '0',
        'garage_construction_area' => '0',
        'infrastructure_landscape_area' => '0',
    ]);

    $metrics = ProjectCalculationService::calculate($project);

    expect($metrics->revenues['total_with_hotel'])->toBe('0.00')
        ->and($metrics->totals['total_cost'])->toBe('0.00')
        ->and($metrics->totals['estimated_profit'])->toBe('0.00')
        ->and($metrics->indicators['profit_margin_percent'])->toBeNull()
        ->and($metrics->indicators['return_on_cost_percent'])->toBeNull()
        ->and($metrics->revenueContributionPercent['residential_phase_1'])->toBeNull()
        ->and($metrics->unavailableReasons['indicators.profit_margin_percent'])
        ->toBe(['zero_denominator.total_revenue_with_hotel'])
        ->and($metrics->unavailableReasons['indicators.return_on_cost_percent'])
        ->toBe(['zero_denominator.total_cost'])
        ->and($metrics->unavailableReasons['revenue_contribution_percent.residential_phase_1'])
        ->toBe(['zero_denominator.residential_and_commercial_revenue_total']);
});

/** @return array<string, string> */
function calculationSourceCells(ProjectMetricsDTO $metrics): array
{
    $years = array_column($metrics->annualCollections, null, 'year');

    return [
        'H4' => $metrics->areas['residential_phase_3_area'],
        'I4' => $metrics->areas['residential_phase_2_area'],
        'J4' => $metrics->areas['residential_phase_1_area'],
        'E6' => $metrics->areas['maximum_buildable_area'],
        'E7' => $metrics->areas['developer_saleable_area'],
        'B10' => $metrics->revenues['residential_phase_1'],
        'B11' => $metrics->revenues['residential_phase_2'],
        'B12' => $metrics->revenues['residential_phase_3'],
        'B13' => $metrics->revenues['commercial_group_1'],
        'B14' => $metrics->revenues['commercial_group_2'],
        'B15' => $metrics->revenues['residential_and_commercial_total'],
        'E10' => $years[6]['residential_phase_1'],
        'F10' => $years[5]['residential_phase_1'],
        'G10' => $years[4]['residential_phase_1'],
        'H10' => $years[3]['residential_phase_1'],
        'I10' => $years[2]['residential_phase_1'],
        'J10' => $years[1]['residential_phase_1'],
        'D11' => $years[7]['residential_phase_2'],
        'E11' => $years[6]['residential_phase_2'],
        'F11' => $years[5]['residential_phase_2'],
        'G11' => $years[4]['residential_phase_2'],
        'H11' => $years[3]['residential_phase_2'],
        'I11' => $years[2]['residential_phase_2'],
        'C12' => $years[8]['residential_phase_3'],
        'D12' => $years[7]['residential_phase_3'],
        'E12' => $years[6]['residential_phase_3'],
        'F12' => $years[5]['residential_phase_3'],
        'G12' => $years[4]['residential_phase_3'],
        'H12' => $years[3]['residential_phase_3'],
        'D13' => $years[7]['commercial_group_1'],
        'E13' => $years[6]['commercial_group_1'],
        'F13' => $years[5]['commercial_group_1'],
        'G13' => $years[4]['commercial_group_1'],
        'H13' => $years[3]['commercial_group_1'],
        'I13' => $years[2]['commercial_group_1'],
        'C14' => $years[8]['commercial_group_2'],
        'D14' => $years[7]['commercial_group_2'],
        'E14' => $years[6]['commercial_group_2'],
        'F14' => $years[5]['commercial_group_2'],
        'G14' => $years[4]['commercial_group_2'],
        'H14' => $years[3]['commercial_group_2'],
        'C15' => $years[8]['total'],
        'D15' => $years[7]['total'],
        'E15' => $years[6]['total'],
        'F15' => $years[5]['total'],
        'G15' => $years[4]['total'],
        'H15' => $years[3]['total'],
        'I15' => $years[2]['total'],
        'J15' => $years[1]['total'],
        'H21' => $metrics->constructionCosts['hotel'],
        'H22' => $metrics->constructionCosts['residential'],
        'H23' => $metrics->constructionCosts['commercial'],
        'H24' => $metrics->constructionCosts['garage'],
        'H25' => $metrics->constructionCosts['infrastructure_landscape'],
        'H26' => $metrics->constructionCosts['total'],
        'J22' => $metrics->areas['maximum_buildable_area'],
        'I29' => $metrics->expenses['marketing'],
        'I30' => $metrics->expenses['sales'],
        'I31' => $metrics->expenses['administration'],
        'I32' => $metrics->expenses['design_supervision'],
        'I33' => $metrics->expenses['land'],
        'I34' => $metrics->expenses['total_including_land'],
        'J36' => $metrics->totals['total_cost'],
        'J37' => $metrics->revenues['residential_total'],
        'J38' => $metrics->revenues['commercial_total'],
        'J40' => $metrics->totals['estimated_profit'],
    ];
}

/**
 * @param  array<string, string>  $expectedSourceCells
 * @return array<string, string>
 */
function calculationWorkbookSourceCells(array $expectedSourceCells): array
{
    $archive = new ZipArchive;
    $openResult = $archive->open(base_path('calculation.xlsx'));

    expect($openResult)->toBeTrue();

    $worksheetXml = $archive->getFromName('xl/worksheets/sheet1.xml');
    $archive->close();

    expect($worksheetXml)->toBeString();

    $worksheet = simplexml_load_string($worksheetXml);

    expect($worksheet)->not->toBeFalse();

    $spreadsheetNamespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $worksheet->registerXPathNamespace('spreadsheet', $spreadsheetNamespace);
    $cells = [];

    foreach ($worksheet->xpath('//spreadsheet:sheetData/spreadsheet:row/spreadsheet:c') ?: [] as $cell) {
        $address = (string) $cell['r'];

        if (! array_key_exists($address, $expectedSourceCells)) {
            continue;
        }

        $cellContents = $cell->children($spreadsheetNamespace);
        $rawValue = isset($cellContents->v) ? (string) $cellContents->v : null;

        expect($rawValue)->not->toBeNull();

        $expectedValue = $expectedSourceCells[$address];
        $decimalPlaces = str_contains($expectedValue, '.')
            ? strlen(substr(strrchr($expectedValue, '.'), 1))
            : 0;
        $cells[$address] = (string) BigDecimal::of($rawValue)->toScale(
            $decimalPlaces,
            RoundingMode::HalfUp,
        );
    }

    $orderedCells = [];

    foreach (array_keys($expectedSourceCells) as $address) {
        if (array_key_exists($address, $cells)) {
            $orderedCells[$address] = $cells[$address];
        }
    }

    return $orderedCells;
}

/** @param array<string, mixed> $overrides */
function makeCalculationProject(array $overrides = []): Project
{
    $project = new Project;

    $project->forceFill([
        'name' => 'مشروع حساب مرجعي',
        'currency_code' => 'EGP',
        'project_duration_years' => 8,
        'status' => 'completed',
        'has_hotel' => false,
        'building_coverage_percent' => '60',
        'calculation_version' => CalculationVersion::CORRECTED_V1,
        'developer_share_percent' => '60',
        'land_area' => '69000',
        'land_cost' => '0',
        'residential_phase_1_price_per_sqm' => '80000',
        'residential_phase_2_price_per_sqm' => '95000',
        'residential_phase_3_price_per_sqm' => '120000',
        'commercial_sales_1_area' => '0',
        'commercial_sales_1_price_per_sqm' => '150000',
        'commercial_sales_2_area' => '0',
        'commercial_sales_2_price_per_sqm' => '200000',
        'hotel_construction_area' => '0',
        'hotel_construction_cost_per_sqm' => '120000',
        'residential_construction_cost_per_sqm' => '25000',
        'commercial_construction_area' => '0',
        'commercial_construction_cost_per_sqm' => '30000',
        'garage_construction_area' => '0',
        'garage_construction_cost_per_sqm' => '15000',
        'infrastructure_landscape_area' => '41400',
        'infrastructure_landscape_cost_per_sqm' => '5000',
        'marketing_percent' => '4',
        'sales_percent' => '5',
        'administration_percent' => '4',
        'design_supervision_percent' => '2.5',
        'hotel_revenue' => '0',
        ...ProjectCollectionScheduleService::defaults(8),
        ...$overrides,
    ]);

    return $project;
}

/** @return array<string, array<string, array<int, string>>> */
function calculationScheduleForDuration(int $durationYears): array
{
    $percentages = [];

    for ($year = 1; $year <= $durationYears; $year++) {
        $percentages[$year] = $year === $durationYears ? '100' : '0';
    }

    return [
        'residential_collection_schedule' => [
            'residential_phase_1' => $percentages,
            'residential_phase_2' => $percentages,
            'residential_phase_3' => $percentages,
        ],
        'commercial_collection_schedule' => [
            'commercial_group_1' => $percentages,
            'commercial_group_2' => $percentages,
        ],
    ];
}
