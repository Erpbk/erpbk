<?php

namespace Tests\Unit;

use App\Support\ExcelDate;
use PHPUnit\Framework\TestCase;
use PhpOffice\PhpSpreadsheet\Shared\Date as PhpSpreadsheetDate;

class ExcelDateTest extends TestCase
{
    public function test_compact_yyyymmdd_integer_parses_to_iso_date(): void
    {
        $this->assertSame('2026-09-17', ExcelDate::format(20260917, 'Y-m-d'));
    }

    public function test_compact_yyyymmdd_string_parses_to_iso_date(): void
    {
        $this->assertSame('2026-09-17', ExcelDate::format('20260917', 'Y-m-d'));
    }

    public function test_compact_yyyymmdd_does_not_become_excel_serial_corruption(): void
    {
        // Regression: previously treated 20260917 as Excel serial → Carbon mangled to 2002-07-02
        $this->assertNotSame('2002-07-02', ExcelDate::format(20260917, 'Y-m-d'));
        $this->assertSame('2026-09-17', ExcelDate::format(20260917, 'Y-m-d'));
    }

    public function test_excel_serial_still_parses(): void
    {
        $serial = PhpSpreadsheetDate::PHPToExcel(new \DateTimeImmutable('2026-09-17'));

        $this->assertSame('2026-09-17', ExcelDate::format($serial, 'Y-m-d'));
        $this->assertSame('2026-09-17', ExcelDate::format(46282, 'Y-m-d'));
    }

    public function test_excel_serial_with_time_fraction_still_parses_date(): void
    {
        $this->assertSame('2026-09-17', ExcelDate::format(46282.75, 'Y-m-d'));
    }

    public function test_invalid_compact_ymd_returns_null(): void
    {
        $this->assertNull(ExcelDate::format(20261340, 'Y-m-d')); // invalid month/day
        $this->assertNull(ExcelDate::format('20260230', 'Y-m-d')); // Feb 30
    }

    public function test_slash_dates_still_parse_dmy_default(): void
    {
        $this->assertSame('2026-09-17', ExcelDate::format('17/09/2026', 'Y-m-d'));
    }
}
