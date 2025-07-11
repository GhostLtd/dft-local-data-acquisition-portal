<?php

namespace App\Utility\SpreadsheetCreator\WorksheetHelper;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorksheetHelper
{
    public function __construct(
        protected Worksheet $worksheet,
        protected int       $x = 0,
        protected int       $y = 0
    ) {}

    public function at(int $x, int $y): Location
    {
        return new Location($this->worksheet, $x + $this->x, $y + $this->y);
    }

    public function mergeCells(int $x1, int $y1, int $x2, int $y2): static
    {
        $this->worksheet->mergeCells([$x1 + $this->x, $y1 + $this->y, $x2 + $this->x, $y2 + $this->y]);
        return $this;
    }
}
