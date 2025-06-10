<?php

namespace App\Utility\SpreadsheetCreator\WorksheetHelper;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorksheetHelper
{
    public function __construct(
        protected Worksheet $worksheet,
        protected int       $offsetX = 0,
        protected int       $offsetY = 0
    ) {}

    public function column(int $x): ColumnLocation
    {
        return new ColumnLocation($this->worksheet, $x + $this->offsetX);
    }

    public function cell(int $x, int $y): CellLocation
    {
        return new CellLocation($this->worksheet, $x + $this->offsetX, $y + $this->offsetY);
    }

    public function range(int $x1, int $y1, int $x2, int $y2): RangeLocation
    {
        return new RangeLocation($this->worksheet, $x1 + $this->offsetX, $y1 + $this->offsetY, $x2 + $this->offsetX, $y2 + $this->offsetY);
    }
}
