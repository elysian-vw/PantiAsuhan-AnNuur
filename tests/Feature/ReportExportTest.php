<?php

namespace Tests\Feature;

use App\Exports\ReportExport;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    private function workbook(ReportExport $export): Spreadsheet
    {
        $path = tempnam(sys_get_temp_dir(), 'report-test-');
        try {
            file_put_contents($path, Excel::raw($export, ExcelFormat::XLSX));

            return IOFactory::load($path);
        } finally {
            unlink($path);
        }
    }

    public function test_donation_export_preserves_values_and_separates_summary_from_filter(): void
    {
        $book = $this->workbook(new ReportExport(
            [
                ['000123', '=1+1', '2026-10-02 09:30:00', 'Berhasil', '25000.50', '0', '25000.50', 'Sistem'],
                ['000124', 'Donatur', null, 'Menunggu pembayaran', '10000', 'Belum tersedia', 'Belum dihitung', '—'],
            ],
            ['Kode', 'Nama', 'Tanggal acuan', 'Status', 'Bruto', 'Biaya', 'Bersih', 'Sumber biaya'],
            'donasi', ['Jumlah catatan' => 2, 'Total bersih tercatat (Rp)' => 25000.50], 'dibayar_pada', ['status' => 'Berhasil'],
        ));
        $sheet = $book->getActiveSheet();
        $this->assertSame('000123', $sheet->getCell('A8')->getValue());
        $this->assertSame('=1+1', $sheet->getCell('B8')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B8')->getDataType());
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('C8')->getDataType());
        $this->assertSame(25000.5, $sheet->getCell('E8')->getValue());
        $this->assertEquals(0, $sheet->getCell('F8')->getValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('F8')->getDataType());
        $this->assertSame('Belum dihitung', $sheet->getCell('G9')->getValue());
        $this->assertSame('A7:H9', $sheet->getAutoFilter()->getRange());
        $this->assertSame('C8', $sheet->getFreezePane());
        $this->assertSame('Ringkasan sesuai filter', $sheet->getCell('A11')->getValue());
        $this->assertSame(25000.5, $sheet->getCell('D13')->getValue());
        $this->assertStringContainsString('Rp', $sheet->getStyle('D13')->getNumberFormat()->getFormatCode());
        $book->disconnectWorksheets();
    }

    public function test_empty_reports_keep_headings_and_zero_summary(): void
    {
        foreach (['donasi' => 8, 'bantuan' => 5] as $kind => $columns) {
            $book = $this->workbook(new ReportExport([], array_fill(0, $columns, 'Kolom'), $kind, ['Jumlah catatan' => 0], 'created_at', []));
            $sheet = $book->getActiveSheet();
            $this->assertSame('Laporan '.ucfirst($kind), $sheet->getTitle());
            $this->assertSame('Tidak ada data sesuai filter.', $sheet->getCell('A8')->getValue());
            $this->assertSame('Ringkasan sesuai filter', $sheet->getCell('A10')->getValue());
            $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('D11')->getDataType());
            $this->assertEquals(0, $sheet->getCell('D11')->getValue());
            $this->assertSame('', $sheet->getAutoFilter()->getRange());
            $book->disconnectWorksheets();
        }
    }
}
