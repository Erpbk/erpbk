<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

trait BindsTextColumns
{
    /**
     * @var array<int, string>|null
     */
    private $resolvedTextColumns;

    public function bindValue(Cell $cell, $value)
    {
        $this->resolvedTextColumns ??= $this->textColumns();

        if (in_array($cell->getColumn(), $this->resolvedTextColumns, true)) {
            $cell->setValueExplicit($value === null ? '' : (string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    /**
     * Excel column letters that must be stored as text (e.g. ['B', 'E']).
     *
     * @return array<int, string>
     */
    abstract protected function textColumns(): array;

    /**
     * Resolve Excel letters for named columns in a dynamic/customizable export.
     *
     * @param  array<int, string>  $columnOrder
     * @param  array<int, string>  $visibleColumns
     * @param  array<int, string>  $textKeys
     * @return array<int, string>
     */
    protected function columnLettersForKeys(array $columnOrder, array $visibleColumns, array $textKeys): array
    {
        $letters = [];
        $index = 0;

        foreach ($columnOrder as $key) {
            if (!in_array($key, $visibleColumns)) {
                continue;
            }

            $index++;

            if (in_array($key, $textKeys, true)) {
                $letters[] = Coordinate::stringFromColumnIndex($index);
            }
        }

        return $letters;
    }
}
