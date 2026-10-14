<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Services\DTO\ProjectCashFlowDTO;
use App\Services\DTO\ProjectMetricsDTO;
use RuntimeException;
use ZipArchive;

final class ProjectExcelExportService
{
    public static function create(
        Project $project,
        ProjectMetricsDTO $metrics,
        ProjectCashFlowDTO $cashFlow,
    ): string {
        $path = tempnam(sys_get_temp_dir(), 'aqar-xlsx-');

        if ($path === false) {
            throw new RuntimeException('Unable to create the Excel export file.');
        }

        $archive = new ZipArchive;

        if ($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to open the Excel export file.');
        }

        $sheets = [
            'المدخلات' => self::inputRows($project),
            'النتائج' => self::resultRows($metrics),
            'التدفقات النقدية' => self::cashFlowRows($cashFlow),
        ];

        $archive->addFromString('[Content_Types].xml', self::contentTypes(count($sheets)));
        $archive->addFromString('_rels/.rels', self::rootRelationships());
        $archive->addFromString('xl/workbook.xml', self::workbook(array_keys($sheets)));
        $archive->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelationships(count($sheets)));
        $archive->addFromString('xl/styles.xml', self::styles());

        $index = 1;

        foreach ($sheets as $rows) {
            $archive->addFromString("xl/worksheets/sheet{$index}.xml", self::worksheet($rows));
            $index++;
        }

        $archive->close();

        return $path;
    }

    /** @return list<list<string|int|float|null>> */
    private static function inputRows(Project $project): array
    {
        $rows = [
            ['المدخل', 'القيمة'],
            ['اسم المشروع', $project->name],
            ['السيناريو', $project->scenario_name],
            ['مالك المشروع', $project->owner?->name],
            ['العملة', $project->currency_code],
            ['عمر المشروع', $project->project_duration_years],
            [
                'إصدار الحساب',
                trans("app.projects.results.calculation_versions.{$project->calculation_version->value}"),
            ],
            ['معدل الخصم %', $project->discount_rate_percent],
        ];

        foreach (config('aqar_metrics.input_fields', []) as $field => $definition) {
            $rows[] = [trans("app.projects.attributes.{$field}"), $project->getAttribute($field)];
        }

        foreach (($project->residential_phase_distribution ?? []) as $phase => $percent) {
            $rows[] = [trans("app.phase_nine.phases.{$phase}"), $percent];
        }

        $rows[] = ['صلاحية المالك', $project->owner?->email];
        $rows[] = ['صلاحية المشاهدة', $project->viewers->pluck('email')->implode('، ')];

        return $rows;
    }

    /** @return list<list<string|int|float|null>> */
    private static function resultRows(ProjectMetricsDTO $metrics): array
    {
        $rows = [['النتيجة', 'القيمة']];
        $results = $metrics->toArray();
        $results['calculation_version'] = trans(
            "app.projects.results.calculation_versions.{$metrics->calculationVersion->value}",
        );

        foreach (self::flatten($results) as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $rows[] = [$key, $value];
            }
        }

        return $rows;
    }

    /** @return list<list<string|int|float|null>> */
    private static function cashFlowRows(ProjectCashFlowDTO $cashFlow): array
    {
        $rows = [[
            'السنة',
            'التحصيل',
            'إيرادات أخرى',
            'تكلفة المشروع',
            'تدفق تمويلي',
            'تكلفة التمويل',
            'الضرائب',
            'صافي التدفق',
            'التراكمي',
        ]];

        foreach ($cashFlow->annualRows as $row) {
            $rows[] = array_values($row);
        }

        $rows[] = [];
        $rows[] = ['القيمة الحالية', $cashFlow->netPresentValue];
        $rows[] = ['معدل العائد الداخلي %', $cashFlow->internalRateOfReturnPercent];
        $rows[] = ['فترة الاسترداد بالسنوات', $cashFlow->paybackPeriodYears];

        return $rows;
    }

    /** @return array<string, mixed> */
    private static function flatten(array $values, string $prefix = ''): array
    {
        $flat = [];

        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $flat += self::flatten($value, $path);
            } else {
                $flat[$path] = $value;
            }
        }

        return $flat;
    }

    /** @param list<list<string|int|float|null>> $rows */
    private static function worksheet(array $rows): string
    {
        $rowXml = '';

        foreach ($rows as $rowIndex => $row) {
            $cells = '';

            foreach ($row as $columnIndex => $value) {
                $address = self::columnName($columnIndex + 1).($rowIndex + 1);
                $style = $rowIndex === 0 ? ' s="1"' : '';

                if ($value !== null && is_numeric($value)) {
                    $cells .= '<c r="'.$address.'"'.$style.'><v>'.self::xml((string) $value).'</v></c>';
                } else {
                    $cells .= '<c r="'.$address.'" t="inlineStr"'.$style.'><is><t>'.self::xml((string) ($value ?? '')).'</t></is></c>';
                }
            }

            $rowXml .= '<row r="'.($rowIndex + 1).'">'.$cells.'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0" rightToLeft="1"/></sheetViews>'
            .'<cols><col min="1" max="1" width="36" customWidth="1"/><col min="2" max="20" width="20" customWidth="1"/></cols>'
            .'<sheetData>'.$rowXml.'</sheetData></worksheet>';
    }

    /** @param list<string> $sheetNames */
    private static function workbook(array $sheetNames): string
    {
        $sheets = '';

        foreach ($sheetNames as $index => $name) {
            $id = $index + 1;
            $sheets .= '<sheet name="'.self::xml($name).'" sheetId="'.$id.'" r:id="rId'.$id.'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheets.'</sheets></workbook>';
    }

    private static function contentTypes(int $sheetCount): string
    {
        $overrides = '';

        for ($index = 1; $index <= $sheetCount; $index++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$overrides.'</Types>';
    }

    private static function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbookRelationships(int $sheetCount): string
    {
        $relationships = '';

        for ($index = 1; $index <= $sheetCount; $index++) {
            $relationships .= '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>';
        }

        $styleId = $sheetCount + 1;

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$relationships
            .'<Relationship Id="rId'.$styleId.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Arial"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Arial"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF174F47"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
            .'</styleSheet>';
    }

    private static function columnName(int $number): string
    {
        $name = '';

        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private static function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
