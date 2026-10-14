<?php

declare(strict_types=1);

namespace App\Services\DTO;

final readonly class ProjectCashFlowDTO
{
    /**
     * @param  list<array{
     *     year: int,
     *     collections: string,
     *     other_revenue: string,
     *     project_cost: string,
     *     financing_inflow: string,
     *     financing_cost: string,
     *     taxes: string,
     *     net_cash_flow: string,
     *     cumulative_cash_flow: string
     * }>  $annualRows
     * @param  list<string>  $unavailableReasons
     */
    public function __construct(
        public array $annualRows,
        public ?string $netPresentValue,
        public ?string $internalRateOfReturnPercent,
        public ?string $paybackPeriodYears,
        public array $unavailableReasons,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'annual_rows' => $this->annualRows,
            'net_present_value' => $this->netPresentValue,
            'internal_rate_of_return_percent' => $this->internalRateOfReturnPercent,
            'payback_period_years' => $this->paybackPeriodYears,
            'unavailable_reasons' => $this->unavailableReasons,
        ];
    }
}
