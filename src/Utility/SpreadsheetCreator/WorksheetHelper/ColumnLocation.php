<?php

namespace App\Utility\SpreadsheetCreator\WorksheetHelper;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\ColumnDimension;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ColumnLocation extends AbstractStyledLocation
{
    public function __construct(
        Worksheet     $worksheet,
        protected int $x
    ) {
        parent::__construct($worksheet);
    }

    public function getStyle(): Style
    {
        $letter = $this->getCoordinate();
        return $this->worksheet->getStyle("$letter:$letter");
    }

    public function getColumnDimension(): ColumnDimension
    {
        return $this->worksheet->getColumnDimension($this->getCoordinate());
    }

    public function setWidth(int $width): static
    {
        $this->getColumnDimension()->setWidth($width);
        return $this;
    }

    public function getCoordinate(): string
    {
        return Coordinate::stringFromColumnIndex($this->x);
    }
}
