<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\FuelCards;
use App\Models\Riders;
use App\Models\Sims;
use Carbon\Carbon;

class AssignmentClipboardNote
{
    public static function forSim(Sims $sim, Employee|Riders $person, $assignDate, ?string $userNote = null): string
    {
        $emi = trim((string) ($sim->emi ?? ''));
        if (str_starts_with($emi, '="') && str_ends_with($emi, '"')) {
            $emi = substr($emi, 2, -1);
        }

        $lines = [
            '*SIM* 📱',
            '────────────────',
            '*Number:* ' . ($sim->number ?: '—'),
        ];
        if ($emi !== '') {
            $lines[] = '*IMEI / ICCID:* ' . $emi;
        }
        $carrier = $sim->telecomCompany?->name;
        if ($carrier) {
            $lines[] = '*SIM Company:* ' . $carrier;
        }

        if ($person instanceof Employee) {
            $lines[] = '*ID:* ' . ($person->employee_id ?: '—');
            $lines[] = '*Name:* ' . ($person->name ?: '—');
            $lines[] = '*Type:* Employee';
        } else {
            $lines[] = '*ID:* ' . ($person->rider_id ?: '—');
            $lines[] = '*Name:* ' . ($person->name ?: '—');
            $lines[] = '*Type:* Rider';
            $project = $person->customer?->name;
            if ($project) {
                $lines[] = '*Project:* ' . $project;
            }
        }

        $lines[] = '*Assign Date:* ' . self::formatDate($assignDate);
        $lines[] = '*Time:* ' . self::dubaiTime();
        self::pushUserNote($lines, $userNote);

        return implode("\n", $lines) . "\n";
    }

    public static function forFuelCard(FuelCards $card, Riders $rider, $assignDate, ?string $userNote = null): string
    {
        $lines = [
            '*Fuel Card* ⛽',
            '────────────────',
            '*Card No:* ' . ($card->card_number ?: '—'),
        ];
        $company = $card->fuelCompany?->name;
        if ($company) {
            $lines[] = '*Fuel Company:* ' . $company;
        }
        $bikeNo = $card->bike_no ?: ($rider->bikes?->plate ?? null);
        if ($bikeNo) {
            $lines[] = '*Bike No:* ' . $bikeNo;
        }
        $lines[] = '*ID:* ' . ($rider->rider_id ?: '—');
        $lines[] = '*Name:* ' . ($rider->name ?: '—');
        $project = $rider->customer?->name;
        if ($project) {
            $lines[] = '*Project:* ' . $project;
        }
        $lines[] = '*Assign Date:* ' . self::formatDate($assignDate);
        $lines[] = '*Time:* ' . self::dubaiTime();
        self::pushUserNote($lines, $userNote);

        return implode("\n", $lines) . "\n";
    }

    public static function withReturn(?string $existing, $returnDate, ?string $userNote = null): string
    {
        $existing = trim((string) $existing);
        $block = '*Return Date:* ' . self::formatDate($returnDate) . "\n"
            . '*Time:* ' . self::dubaiTime();
        $userNote = trim((string) $userNote);
        if ($userNote !== '') {
            $block .= "\n*Notes:* " . $userNote;
        }
        $block .= "\n";

        if ($existing === '') {
            return $block;
        }

        return rtrim($existing) . "\n" . $block;
    }

    public static function isStructured(?string $notes): bool
    {
        $notes = ltrim((string) $notes);

        return str_starts_with($notes, '*SIM*') || str_starts_with($notes, '*Fuel Card*');
    }

    public static function displayNote(?string $notes): ?string
    {
        $notes = (string) $notes;
        if (trim($notes) === '') {
            return null;
        }
        if (! self::isStructured($notes)) {
            return trim($notes);
        }

        $parts = [];
        if (preg_match_all('/^\*Notes:\*\s*(.+)$/m', $notes, $matches)) {
            foreach ($matches[1] as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $parts[] = $line;
                }
            }
        }

        return $parts !== [] ? implode(' | ', $parts) : null;
    }

    private static function pushUserNote(array &$lines, ?string $userNote): void
    {
        $userNote = trim((string) $userNote);
        if ($userNote !== '') {
            $lines[] = '*Notes:* ' . $userNote;
        }
    }

    private static function formatDate($date): string
    {
        if (!$date) {
            return now()->setTimezone('Asia/Dubai')->format('d-m-Y');
        }

        return Carbon::parse($date)->format('d-m-Y');
    }

    private static function dubaiTime(): string
    {
        return now()->setTimezone('Asia/Dubai')->format('h:i a');
    }
}
