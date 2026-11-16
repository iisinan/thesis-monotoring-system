<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Generates a lightweight, styled OpenXML (.xlsx) spreadsheet without external dependencies.
 */
class StyledXlsxBuilder
{
    /**
     * Build a binary .xlsx workbook with a two-line centered report banner,
     * dark navy header bar, thin grid borders, alternating row shading, and column widths.
     *
     * @param  string  $sheetName
     * @param  string  $institutionTitle
     * @param  string  $reportSubtitle
     * @param  array<int, array{label: string, width: int|float, align?: 'left'|'center'|'right', type?: 'string'|'int'}>  $columns
     * @param  array<int, array<int, string|int|float|null>>  $rows
     */
    public static function buildReport(
        string $sheetName,
        string $institutionTitle,
        string $reportSubtitle,
        array $columns,
        array $rows
    ): string {
        $colCount = count($columns);
        if ($colCount < 1) {
            throw new RuntimeException('XLSX report requires at least one column.');
        }

        $lastColLetter = self::columnLetter($colCount);
        $safeSheetName = self::sanitizeSheetName($sheetName);

        $tmpPath = tempnam(sys_get_temp_dir(), 'acetel_xlsx_');
        if ($tmpPath === false) {
            throw new RuntimeException('Unable to allocate temporary file for XLSX generation.');
        }

        try {
            $zip = new ZipArchive();
            if ($zip->open($tmpPath, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
                throw new RuntimeException('Unable to open ZipArchive for XLSX generation.');
            }

            $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
            $zip->addFromString('_rels/.rels', self::rootRelsXml());
            $zip->addFromString('xl/workbook.xml', self::workbookXml($safeSheetName));
            $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
            $zip->addFromString('xl/styles.xml', self::stylesXml());
            $zip->addFromString(
                'xl/worksheets/sheet1.xml',
                self::worksheetXml($institutionTitle, $reportSubtitle, $columns, $rows, $lastColLetter)
            );
            $zip->close();

            $binary = file_get_contents($tmpPath);
            if ($binary === false) {
                throw new RuntimeException('Failed to read generated XLSX binary.');
            }

            return $binary;
        } finally {
            @unlink($tmpPath);
        }
    }

    private static function worksheetXml(
        string $institutionTitle,
        string $reportSubtitle,
        array $columns,
        array $rows,
        string $lastColLetter
    ): string {
        $colsXml = "<cols>\n";
        foreach ($columns as $idx => $col) {
            $colNum = $idx + 1;
            $width = max(8, (float) ($col['width'] ?? 18));
            $colsXml .= "  <col min=\"{$colNum}\" max=\"{$colNum}\" width=\"{$width}\" customWidth=\"1\"/>\n";
        }
        $colsXml .= "</cols>\n";

        $sheetData = "<sheetData>\n";

        // Row 1: Top whitespace spacer
        $sheetData .= "  <row r=\"1\" ht=\"10\" customHeight=\"1\"/>\n";

        // Row 2: Institution Header (Merged A2:{lastCol}2)
        $sheetData .= "  <row r=\"2\" ht=\"24\" customHeight=\"1\">\n";
        $sheetData .= self::inlineStringCell('A2', $institutionTitle, 1);
        $sheetData .= "  </row>\n";

        // Row 3: Report Subtitle (Merged A3:{lastCol}3)
        $sheetData .= "  <row r=\"3\" ht=\"22\" customHeight=\"1\">\n";
        $sheetData .= self::inlineStringCell('A3', $reportSubtitle, 2);
        $sheetData .= "  </row>\n";

        // Row 4: Spacer before table header
        $sheetData .= "  <row r=\"4\" ht=\"10\" customHeight=\"1\"/>\n";

        // Row 5: Navy Blue Table Header
        $sheetData .= "  <row r=\"5\" ht=\"24\" customHeight=\"1\">\n";
        foreach ($columns as $idx => $col) {
            $ref = self::columnLetter($idx + 1) . '5';
            $align = $col['align'] ?? 'left';
            $style = match ($align) {
                'center' => 4,
                'right' => 5,
                default => 3,
            };
            $sheetData .= self::inlineStringCell($ref, (string) $col['label'], $style);
        }
        $sheetData .= "  </row>\n";

        // Data rows starting at row 6
        $excelRow = 6;
        foreach ($rows as $rowIndex => $rowValues) {
            $isEven = ($rowIndex % 2) === 1;
            $sheetData .= "  <row r=\"{$excelRow}\" ht=\"20\" customHeight=\"1\">\n";

            foreach ($columns as $colIndex => $col) {
                $ref = self::columnLetter($colIndex + 1) . $excelRow;
                $value = $rowValues[$colIndex] ?? '';
                $align = $col['align'] ?? 'left';
                $type = $col['type'] ?? 'string';

                if ($type === 'int' && is_numeric($value)) {
                    $intVal = (int) round((float) $value);
                    $style = match ($align) {
                        'center' => $isEven ? 13 : 12,
                        'right' => $isEven ? 11 : 10,
                        default => $isEven ? 7 : 6,
                    };
                    $sheetData .= "    <c r=\"{$ref}\" s=\"{$style}\"><v>{$intVal}</v></c>\n";
                } else {
                    $style = match ($align) {
                        'center' => $isEven ? 9 : 8,
                        'right' => $isEven ? 11 : 10,
                        default => $isEven ? 7 : 6,
                    };
                    $sheetData .= self::inlineStringCell($ref, (string) $value, $style);
                }
            }

            $sheetData .= "  </row>\n";
            $excelRow++;
        }

        $sheetData .= "</sheetData>\n";

        $mergeXml = "<mergeCells count=\"2\">\n"
            . "  <mergeCell ref=\"A2:{$lastColLetter}2\"/>\n"
            . "  <mergeCell ref=\"A3:{$lastColLetter}3\"/>\n"
            . "</mergeCells>\n";

        $dimensionEndRow = max(5, $excelRow - 1);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n"
            . "<sheetPr><pageSetUpPr fitToPage=\"1\"/></sheetPr>\n"
            . "<dimension ref=\"A1:{$lastColLetter}{$dimensionEndRow}\"/>\n"
            . "<sheetViews><sheetView workbookViewId=\"0\"><pane ySplit=\"5\" topLeftCell=\"A6\" activePane=\"bottomLeft\" state=\"frozen\"/></sheetView></sheetViews>\n"
            . "<sheetFormatPr defaultRowHeight=\"20\"/>\n"
            . $colsXml
            . $sheetData
            . $mergeXml
            . "<pageMargins left=\"0.4\" right=\"0.4\" top=\"0.5\" bottom=\"0.5\" header=\"0.2\" footer=\"0.2\"/>\n"
            . "<pageSetup orientation=\"landscape\" fitToWidth=\"1\" fitToHeight=\"0\"/>\n"
            . "</worksheet>";
    }

    private static function inlineStringCell(string $ref, string $value, int $styleIndex): string
    {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? $value;
        $escaped = htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return "    <c r=\"{$ref}\" t=\"inlineStr\" s=\"{$styleIndex}\"><is><t xml:space=\"preserve\">{$escaped}</t></is></c>\n";
    }

    private static function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }

    private static function sanitizeSheetName(string $name): string
    {
        $clean = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $name) ?? 'Results';
        $clean = trim($clean) ?: 'Results';

        return mb_substr($clean, 0, 31);
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private static function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbookXml(string $sheetName): string
    {
        $escapedName = htmlspecialchars($sheetName, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>'
            . "<sheet name=\"{$escapedName}\" sheetId=\"1\" r:id=\"rId1\"/>"
            . '</sheets>'
            . '</workbook>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4">'
            . '  <font><sz val="11"/><color rgb="FF0F172A"/><name val="Calibri"/></font>'
            . '  <font><b/><sz val="14"/><color rgb="FF000000"/><name val="Calibri"/></font>'
            . '  <font><b/><sz val="13"/><color rgb="FF1F4E78"/><name val="Calibri"/></font>'
            . '  <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="5">'
            . '  <fill><patternFill patternType="none"/></fill>'
            . '  <fill><patternFill patternType="gray125"/></fill>'
            . '  <fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/></patternFill></fill>'
            . '  <fill><patternFill patternType="solid"><fgColor rgb="FFF2F5F9"/></patternFill></fill>'
            . '  <fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '  <border><left/><right/><top/><bottom/><diagonal/></border>'
            . '  <border>'
            . '    <left style="thin"><color rgb="FFD0D7DE"/></left>'
            . '    <right style="thin"><color rgb="FFD0D7DE"/></right>'
            . '    <top style="thin"><color rgb="FFD0D7DE"/></top>'
            . '    <bottom style="thin"><color rgb="FFD0D7DE"/></bottom>'
            . '    <diagonal/>'
            . '  </border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="14">'
            . '  <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '  <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '  <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '  <xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '  <xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '  <xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '  <xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '  <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '  <xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '  <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '  <xf numFmtId="1" fontId="0" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '  <xf numFmtId="1" fontId="0" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '  <xf numFmtId="1" fontId="0" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '  <xf numFmtId="1" fontId="0" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}
