<?php

namespace App\Utility\SpreadsheetCreator\WorksheetHelper;

use App\Config\Table\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RangeLocation extends AbstractStyledLocation
{
    public function __construct(
        Worksheet     $worksheet,
        protected int $x1,
        protected int $y1,
        protected int $x2,
        protected int $y2,
    ) {
        parent::__construct($worksheet);
    }

    public function getStyle(): Style
    {
        return $this->worksheet->getStyle([$this->x1, $this->y1, $this->x2, $this->y2]);
    }

    public function getCoordinate(): string
    {
        $cell1 = Coordinate::stringFromColumnIndex($this->x1).$this->y1;
        $cell2 = Coordinate::stringFromColumnIndex($this->x2).$this->y2;
        return "$cell1:$cell2";
    }

    public function mergeCells(): CellLocation
    {
        $this->worksheet->mergeCells([$this->x1, $this->y1, $this->x2, $this->y2]);
        return new CellLocation($this->worksheet, $this->x1, $this->y1);
    }
}
