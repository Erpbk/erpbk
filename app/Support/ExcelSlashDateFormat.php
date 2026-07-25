<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Detects whether slash/dash dates in an Excel sheet are d/m/Y or m/d/Y
 * by scanning values for a part greater than 12, then parses with that order.
 */
class ExcelSlashDateFormat
{
    public const ORDER_DMY = 'dmy';
    public const ORDER_MDY = 'mdy';

    /**
     * Detect date order from sample cell values.
     * - first part > 12  → day/month/year (e.g. 23/12/2026)
     * - second part > 12 → month/day/year (e.g. 12/23/2026)
     * Ambiguous samples (both ≤ 12) are ignored for voting.
     * Defaults to dmy when no decisive sample (UAE / Salik).
     */
    public static function detectOrder(iterable $samples): string
    {
        $dmyVotes = 0;
        $mdyVotes = 0;

        foreach ($samples as $sample) {
            $parts = self::extractDayMonthParts($sample);
            if ($parts === null) {
                continue;
            }

            [$first, $second] = $parts;

            if ($first > 12 && $second >= 1 && $second <= 12) {
                $dmyVotes++;
            } elseif ($second > 12 && $first >= 1 && $first <= 12) {
                $mdyVotes++;
            }
        }

        if ($mdyVotes > $dmyVotes) {
            return self::ORDER_MDY;
        }

        return self::ORDER_DMY;
    }

    /**
     * Parse a slash/dash date string using the detected order.
     */
    public static function parse(string $value, string $order = self::ORDER_DMY): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formats = $order === self::ORDER_MDY
            ? self::monthFirstFormats()
            : self::dayFirstFormats();

        foreach ($formats as $format) {
            $parsed = self::tryParseWithFormat($value, $format);
            if ($parsed) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * @return array{0:int,1:int}|null [first, second] numeric parts before year
     */
    public static function extractDayMonthParts($value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        // Skip Excel serials — those are unambiguous DateTime values elsewhere
        if (is_numeric($value) && !is_string($value)) {
            return null;
        }

        $value = trim((string) $value);
        if (!preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/', $value, $m)) {
            return null;
        }

        return [(int) $m[1], (int) $m[2]];
    }

    private static function dayFirstFormats(): array
    {
        return [
            'd/m/Y h:i:s a',
            'd/m/Y h:i:s A',
            'd/m/Y g:i:s a',
            'd/m/Y g:i:s A',
            'd/m/Y h:i a',
            'd/m/Y h:i A',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
            'd-m-Y h:i:s a',
            'd-m-Y h:i:s A',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'd-m-Y',
            'j/n/Y h:i:s a',
            'j/n/Y h:i:s A',
            'j/n/Y H:i:s',
            'j/n/Y H:i',
            'j/n/Y',
            'd/m/y h:i:s a',
            'd/m/y h:i:s A',
            'd/m/y H:i:s',
            'd/m/y H:i',
            'd/m/y',
        ];
    }

    private static function monthFirstFormats(): array
    {
        return [
            'm/d/Y h:i:s a',
            'm/d/Y h:i:s A',
            'm/d/Y g:i:s a',
            'm/d/Y g:i:s A',
            'm/d/Y h:i a',
            'm/d/Y h:i A',
            'm/d/Y H:i:s',
            'm/d/Y H:i',
            'm/d/Y',
            'm-d-Y h:i:s a',
            'm-d-Y h:i:s A',
            'm-d-Y H:i:s',
            'm-d-Y H:i',
            'm-d-Y',
            'n/j/Y h:i:s a',
            'n/j/Y h:i:s A',
            'n/j/Y H:i:s',
            'n/j/Y H:i',
            'n/j/Y',
            'm/d/y h:i:s a',
            'm/d/y h:i:s A',
            'm/d/y H:i:s',
            'm/d/y H:i',
            'm/d/y',
        ];
    }

    private static function tryParseWithFormat(string $value, string $format): ?Carbon
    {
        $dateTime = \DateTime::createFromFormat('!' . $format, $value);
        if ($dateTime === false) {
            return null;
        }

        $errors = \DateTime::getLastErrors();
        if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }

        return Carbon::instance($dateTime);
    }
}
