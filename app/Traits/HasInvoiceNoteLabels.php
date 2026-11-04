<?php

namespace App\Traits;

trait HasInvoiceNoteLabels
{
    public const DEFAULT_INVOICE_NOTE_LABEL = 'Invoice Note';

    public const DEFAULT_TERMS_AND_CONDITIONS_LABEL = 'Terms & Conditions';

    public function resolvedInvoiceNoteLabel(): string
    {
        $label = trim((string) ($this->invoice_note_label ?? ''));

        return $label !== '' ? $label : static::DEFAULT_INVOICE_NOTE_LABEL;
    }

    public function resolvedTermsAndConditionsLabel(): string
    {
        $label = trim((string) ($this->terms_and_conditions_label ?? ''));

        return $label !== '' ? $label : static::DEFAULT_TERMS_AND_CONDITIONS_LABEL;
    }

    /**
     * @return list<string>
     */
    public static function invoiceNoteFillable(): array
    {
        return [
            'invoice_note',
            'invoice_note_label',
            'terms_and_conditions',
            'terms_and_conditions_label',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function invoiceNoteRules(): array
    {
        return [
            'invoice_note' => 'nullable|string',
            'invoice_note_label' => 'nullable|string|max:100',
            'terms_and_conditions' => 'nullable|string',
            'terms_and_conditions_label' => 'nullable|string|max:100',
        ];
    }
}
