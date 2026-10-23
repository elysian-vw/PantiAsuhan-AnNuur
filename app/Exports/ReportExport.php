<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ReportExport extends StringValueBinder implements FromArray, WithCustomValueBinder, WithEvents, WithStrictNullComparison, WithTitle
{
    private const MONEY_FORMAT = '"Rp" #,##0.00';

    public function __construct(
        private array $rows,
        private array $headers,
        private string $kind,
        private array $summary,
        private string $dateColumn,
        private array $filters,
    ) {
        // Identifiers and user-entered strings stay literal; typed numbers remain numeric.
        $this->setNumericConversion(false);
    }

    public function title(): string
    {
        return 'Laporan '.ucfirst($this->kind);
    }

    public function array(): array
    {
        $filters = $this->filters;
        $dateLabel = match ($this->dateColumn) {
            'dibayar_pada' => 'Tanggal pembayaran',
            'tanggal_diterima' => 'Tanggal penerimaan',
            'tanggal' => 'Tanggal pengeluaran',
            'periode' => 'Periode arus kas',
            default => 'Tanggal acuan',
        };
        $result = [
            ['Panti Asuhan NU An-Nuur 2 Kota Kediri'],
            [$this->title()],
            ['Periode: '.(! empty($filters['awal']) ? Carbon::parse($filters['awal'])->format('d/m/Y') : 'Semua').' s.d. '.(! empty($filters['akhir']) ? Carbon::parse($filters['akhir'])->format('d/m/Y') : 'Semua')],
            ['Bulan: '.($filters['bulan'] ?? 'Semua').' | Tahun: '.($filters['tahun'] ?? 'Semua').' | Status: '.($filters['status'] ?? 'Semua')],
            ['Tanggal acuan: '.$dateLabel.' | Dibuat: '.now('Asia/Jakarta')->format('d/m/Y H:i').' WIB'],
            [null],
            $this->headers,
        ];
        foreach ($this->rows as $row) {
            if ($this->kind === 'donasi' && ! empty($row[2])) {
                $row[2] = Date::dateTimeToExcel(Carbon::parse($row[2]));
            } elseif ($this->kind === 'pengeluaran' && ! empty($row[1])) {
                $row[1] = Date::dateTimeToExcel(Carbon::parse($row[1]));
            }
            foreach (match ($this->kind) {
                'donasi' => [4, 5, 6],
                'pengeluaran' => [6],
                'arus-kas' => [1],
                default => [],
            } as $index) {
                if (isset($row[$index]) && is_numeric($row[$index])) {
                    $row[$index] = (float) $row[$index];
                }
            }
            $result[] = $row;
        }
        if ($this->rows === []) {
            $result[] = ['Tidak ada data sesuai filter.'];
        }
        $result[] = [null];
        $result[] = ['Ringkasan sesuai filter'];
        foreach ($this->summary as $label => $value) {
            $valueCol = count($this->headers) <= 3 ? (count($this->headers) - 1) : 3;
            $rowArr = array_fill(0, count($this->headers), null);
            $rowArr[0] = $label;
            $rowArr[$valueCol] = is_numeric($value) ? (float) $value : $value;
            $result[] = $rowArr;
        }

        return $result;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $sheet = $event->sheet->getDelegate();
            $lastColumn = Coordinate::stringFromColumnIndex(count($this->headers));
            $lastDataRow = 7 + max(1, count($this->rows));
            $summaryRow = $lastDataRow + 2;
            $lastRow = $summaryRow + count($this->summary);
            $sheet->setShowGridlines(false);
            $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
                'font' => ['name' => 'Calibri', 'size' => 11, 'color' => ['rgb' => '23392E']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $sheet->getDefaultRowDimension()->setRowHeight(30);
            foreach (range(1, 5) as $row) {
                $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
                $sheet->getRowDimension($row)->setRowHeight($row <= 2 ? 30 : 24);
            }
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A3:A5')->getFont()->setSize(10)->getColor()->setRGB('627268');
            $sheet->getRowDimension(6)->setRowHeight(12);
            $sheet->getRowDimension($lastDataRow + 1)->setRowHeight(12);
            $widths = match ($this->kind) {
                'donasi' => [27, 30, 23, 25, 24, 24, 24, 24],
                'bantuan' => [27, 30, 23, 25, 65],
                'pengeluaran' => [25, 18, 25, 35, 25, 18, 22, 18],
                'arus-kas' => [38, 25, 45],
                default => [27, 30, 23, 20, 14, 25],
            };
            foreach ($widths as $index => $width) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))->setWidth($width);
            }
            $sheet->getStyle('A7:'.$lastColumn.'7')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '285943']],
            ]);
            $sheet->getRowDimension(7)->setRowHeight(32);
            for ($row = 8; $row <= $lastDataRow; $row++) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $row % 2 === 0 ? 'F0F5F1' : 'FFFFFF']],
                    'borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'DCE5DF']]],
                ]);
                $lines = 1;
                foreach ($this->rows[$row - 8] ?? [] as $index => $value) {
                    $cellLines = 0;
                    $w = $widths[$index] ?? 20;
                    foreach (explode("\n", (string) $value) as $line) {
                        $cellLines += max(1, (int) ceil(mb_strwidth($line) / max(1, $w - 4)));
                    }
                    $lines = max($lines, $cellLines);
                }
                $sheet->getRowDimension($row)->setRowHeight(max(30, $lines * 16 + 12));
            }
            if ($this->rows === []) {
                $sheet->mergeCells("A8:{$lastColumn}8");
            } else {
                $sheet->setAutoFilter("A7:{$lastColumn}{$lastDataRow}");
                if (in_array($this->kind, ['donasi', 'bantuan'])) {
                    $sheet->getStyle("C8:C{$lastDataRow}")->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
                    $sheet->getStyle("C8:C{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                } elseif ($this->kind === 'pengeluaran') {
                    $sheet->getStyle("B8:B{$lastDataRow}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                    $sheet->getStyle("B8:B{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                $numberRange = match ($this->kind) {
                    'donasi' => "E8:G{$lastDataRow}",
                    'pengeluaran' => "G8:G{$lastDataRow}",
                    'arus-kas' => "B8:B{$lastDataRow}",
                    default => null,
                };
                if ($numberRange !== null) {
                    $sheet->getStyle($numberRange)->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
                    $sheet->getStyle($numberRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            }
            $sheet->mergeCells("A{$summaryRow}:{$lastColumn}{$summaryRow}");
            $sheet->getStyle("A{$summaryRow}:{$lastColumn}{$summaryRow}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCEBDD']],
            ]);
            foreach (array_keys($this->summary) as $index => $label) {
                $row = $summaryRow + $index + 1;
                if (count($this->headers) <= 3) {
                    $sheet->mergeCells("A{$row}:B{$row}");
                    $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode(str_contains($label, '(Rp)') ? self::MONEY_FORMAT : '#,##0.##');
                    $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("C{$row}")->getFont()->setBold(true);
                } else {
                    $sheet->mergeCells("A{$row}:C{$row}");
                    $sheet->mergeCells("D{$row}:{$lastColumn}{$row}");
                    $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode(str_contains($label, '(Rp)') ? self::MONEY_FORMAT : '#,##0.##');
                    $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("D{$row}")->getFont()->setBold(true);
                }
                $sheet->getRowDimension($row)->setRowHeight(max(30, (int) ceil(mb_strwidth($label) / 72) * 16 + 12));
            }
            $sheet->freezePane(match ($this->kind) {
                'pengeluaran' => 'C8',
                'arus-kas' => 'A8',
                default => 'C8',
            });
            $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                ->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0)
                ->setRowsToRepeatAtTopByStartAndEnd(7, 7)->setPrintArea("A1:{$lastColumn}{$lastRow}");
            $sheet->getHeaderFooter()->setOddFooter('&L'.$this->title().'&RHalaman &P dari &N');
        }];
    }
}
