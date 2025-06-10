<?php

namespace App\Utility\SpreadsheetCreator\WorksheetHelper;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\ColumnDimension;
use PhpOffice\PhpSpreadsheet\Worksheet\RowDimension;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CellLocation extends AbstractStyledLocation
{
    public function __construct(
        Worksheet     $worksheet,
        protected int $x,
        protected int $y
    ) {
        parent::__construct($worksheet);
    }

    public function getCell(): Cell
    {
        return $this->worksheet->getCell([$this->x, $this->y]);
    }

    public function getCoordinate(): string
    {
        return $this->getCell()->getCoordinate();
    }

    public function getStyle(): Style
    {
        return $this->worksheet->getStyle([$this->x, $this->y]);
    }

    public function setValue(mixed $value): static
    {
        $this->getCell()->setValue($value);
        return $this;
    }

    public function setValueExplicit(mixed $value, string $dataType = DataType::TYPE_STRING): static
    {
        $this->getCell()->setValueExplicit($value, $dataType);
        return $this;
    }

    public function getColumnDimension(): ColumnDimension
    {
        return $this->worksheet->getColumnDimension(Coordinate::stringFromColumnIndex($this->x));
    }

    public function setWidth(int $width): static
    {
        $this->getColumnDimension()->setWidth($width);
        return $this;
    }

    public function getRowDimension(): RowDimension
    {
        return $this->worksheet->getRowDimension($this->y);
    }

    public function setHeight(int $height): static
    {
        $this->getRowDimension()->setRowHeight($height);
        return $this;
    }

    public function freezePane(): static
    {
        $this->worksheet->freezePane([$this->x, $this->y]);
        return $this;
    }
}
