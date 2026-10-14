<?php

declare(strict_types=1);

namespace App\Enums;

enum CalculationVersion: string
{
    case CORRECTED_V1 = 'corrected-v1';
    case EXCEL_SOURCE_V1 = 'excel-source-v1';
    case FLEXIBLE_PHASES_V2 = 'flexible-phases-v2';
}
