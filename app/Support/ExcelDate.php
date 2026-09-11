<?php

namespace App\Support;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as PhpSpreadsheetDate;

/**
 * Parse Excel cell values into Carbon dates consistently across imports.
 *
 * Handles Excel serial numbers, DateTime/Carbon cells, slash/dash dates
 * (via ExcelSlashDateFormat), and common string formats.
 */
class ExcelDate
{
    /**
     * Parse any Excel date/time cell value into Carbon.
     * Slash/dash dates use the given d/m/Y or m/d/Y order (default: dmy / UAE).
     */
    public static function parse($value, string $slashOrder = ExcelSlashDateFormat::ORDER_DMY): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof Carbon) {
                return $value->copy();
            }

            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value);
            }

            if (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    return null;
                }
            }

            // Excel serial number (real date cell, or numeric string serial)
            if (is_numeric($value)) {
                return Carbon::instance(
                    PhpSpreadsheetDate::excelToDateTimeObject((float) $value)
                );
            }

            if (! is_string($value)) {
                return null;
            }

            // Slash/dash numeric dates — use detected sheet order (dmy or mdy)
            if (preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', $value)) {
                $parsed = ExcelSlashDateFormat::parse($value, $slashOrder);
                if ($parsed) {
                    return $parsed;
                }

                return null;
            }

            foreach (self::stringDateFormats() as $format) {
                $parsed = self::tryParseWithFormat($value, $format);
                if ($parsed) {
                    return $parsed;
                }
            }

            // Avoid Carbon::parse() on slash dates — it defaults to m/d/Y
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Parse a billing-month cell to the first day of that month.
     * Also accepts month-only strings such as "2025-01", "Jan 2025", "F Y".
     */
    public static function parseBillingMonth($value, string $slashOrder = ExcelSlashDateFormat::ORDER_DMY): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Prefer month-only formats before full-date parse (avoids "Jan 2025" → M d Y).
        if (is_string($value) || is_numeric($value)) {
            $raw = trim((string) $value);
            if ($raw !== '' && ! is_numeric($raw)) {
                foreach (['Y-m', 'Y/m', 'm/Y', 'M Y', 'F Y'] as $format) {
                    $date = self::tryParseWithFormat($raw, $format);
                    if ($date) {
                        return $date->startOfMonth();
                    }
                }
            }
        }

        $parsed = self::parse($value, $slashOrder);
        if ($parsed) {
            return $parsed->copy()->startOfMonth();
        }

        return null;
    }

    /**
     * Parse and format a date cell, or null if unparseable / blank.
     */
    public static function format($value, string $format = 'Y-m-d', string $slashOrder = ExcelSlashDateFormat::ORDER_DMY): ?string
    {
        $parsed = self::parse($value, $slashOrder);

        return $parsed ? $parsed->format($format) : null;
    }

    /**
     * Parse and format a billing-month cell as Y-m-01 (or custom format).
     */
    public static function formatBillingMonth($value, string $format = 'Y-m-01', string $slashOrder = ExcelSlashDateFormat::ORDER_DMY): ?string
    {
        $parsed = self::parseBillingMonth($value, $slashOrder);

        return $parsed ? $parsed->format($format) : null;
    }

    /**
     * Normalize trip/time cells from Excel serials, time-only strings, or datetimes.
     */
    public static function formatTime($value, string $format = 'h:i:s A', string $slashOrder = ExcelSlashDateFormat::ORDER_DMY): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof Carbon) {
                return $value->format($format);
            }

            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value)->format($format);
            }

            if (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    return null;
                }
            }

            if (is_numeric($value)) {
                $dateTime = PhpSpreadsheetDate::excelToDateTimeObject((float) $value);

                return Carbon::instance($dateTime)->format($format);
            }

            if (is_string($value)) {
                foreach (['H:i:s', 'H:i', 'h:i:s A', 'h:i A', 'g:i A', 'g:i:s A'] as $timeFormat) {
                    $parsed = self::tryParseWithFormat($value, $timeFormat);
                    if ($parsed) {
                        return $parsed->format($format);
                    }
                }

                $parsedDate = self::parse($value, $slashOrder);
                if ($parsedDate) {
                    return $parsedDate->format($format);
                }
            }

            return Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    private static function stringDateFormats(): array
    {
        return [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd M Y H:i:s',
            'd M Y',
            'd M y',
            'j M Y',
            'j M y',
            'd-M-Y',
            'd-M-y',
            'M d, Y',
            'M d Y',
            'Y/m/d',
        ];
    }

    private static function tryParseWithFormat(string $value, string $format): ?Carbon
    {
        $dateTime = \DateTime::createFromFormat('!'.$format, $value);
        if ($dateTime === false) {
            return null;
        }

        $errors = \DateTime::getLastErrors();
        if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }

        // Reject partial matches (e.g. "M d Y" reading "Jan 2025" as Jan 20, 0025)
        if (self::normalizeDateString($dateTime->format($format)) !== self::normalizeDateString($value)) {
            return null;
        }

        return Carbon::instance($dateTime);
    }

    /**
     * Compare formatted dates without leading-zero / whitespace noise.
     */
    private static function normalizeDateString(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';

        return preg_replace_callback('/\d+/', static function (array $m): string {
            return (string) ((int) $m[0]);
        }, $value) ?? $value;
    }
}
