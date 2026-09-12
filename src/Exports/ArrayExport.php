<?php

namespace ArtflowStudio\Table\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Minimal maatwebsite/excel export for an already-flattened table.
 *
 * Deliberately implements the interfaces by name rather than importing them, so
 * this file is loadable even when maatwebsite/excel is not installed — the
 * package only `suggest`s it. AftableComponent::availableExportFormats() gates
 * every use of this class behind a class_exists() check.
 */
class ArrayExport implements FromArray, WithHeadings
{
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
