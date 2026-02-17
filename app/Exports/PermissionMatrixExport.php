<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PermissionMatrixExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        protected array $rows,
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Permission Matrix';
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        // Style the header row
        $headerStyle = [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1a365d'],
                ],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];

        // Color "Yes" cells green and "No" cells light red
        for ($row = 2; $row <= $lastRow; $row++) {
            for ($col = 'B'; $col <= $lastCol; $col++) {
                $value = $sheet->getCell($col . $row)->getValue();
                if ($value === 'Yes') {
                    $sheet->getStyle($col . $row)->applyFromArray([
                        'font' => ['color' => ['rgb' => '166534'], 'bold' => true],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'dcfce7'],
                        ],
                        'alignment' => ['horizontal' => 'center'],
                    ]);
                } elseif ($value === 'No') {
                    $sheet->getStyle($col . $row)->applyFromArray([
                        'font' => ['color' => ['rgb' => '991b1b']],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'fee2e2'],
                        ],
                        'alignment' => ['horizontal' => 'center'],
                    ]);
                }
            }

            // Bold the permission name column
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true],
            ]);
        }

        // Add borders
        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        // Freeze the header row and first column
        $sheet->freezePane('B2');

        return $headerStyle;
    }
}
