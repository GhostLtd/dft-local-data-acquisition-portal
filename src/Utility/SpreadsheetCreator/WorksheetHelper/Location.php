<?php

namespace App\Utility\SpreadsheetCreator\WorksheetHelper;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\ColumnDimension;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Location
{
    public function __construct(
        protected Worksheet $worksheet,
        protected int       $x = 0,
        protected int       $y = 0
    ) {}

    public function at(int $x, int $y): static
    {
        return new self($this->worksheet, $x + $this->x, $y + $this->y);
    }

    public function apply(ActionSet $actionSet): static
    {
        $actionSet->apply($this);
        return $this;
    }

    public function getCell(): Cell
    {
        return $this->worksheet->getCell([$this->x, $this->y]);
    }

    public function getStyle(): Style
    {
        return $this->getCell()->getStyle();
    }

    public function getFont(): Font
    {
        return $this->getStyle()->getFont();
    }

    public function getFill(): Fill
    {
        return $this->getStyle()->getFill();
    }

    public function getAlignment(): Alignment
    {
        return $this->getStyle()->getAlignment();
    }

    public function getColumnDimension(): ColumnDimension
    {
        return $this->worksheet->getColumnDimension(Coordinate::stringFromColumnIndex($this->x));
    }

    public function setValue(mixed $value): static
    {
        $this->getCell()->setValue($value);
        return $this;
    }

    public function setBold(int $bold): static
    {
        $this->getFont()->setBold($bold);
        return $this;
    }

    public function setItalic(int $italic): static
    {
        $this->getFont()->setItalic($italic);
        return $this;
    }

    public function setColor(Color $colour): static
    {
        $this->getFont()->setColor($colour);
        return $this;
    }

    public function setWidth(int $width): static
    {
        $this->getColumnDimension()->setWidth($width);
        return $this;
    }

    public function setFill(Color $color, string $fillType=Fill::FILL_SOLID): static
    {
        return
            $this
                ->setFillType($fillType)
                ->setFillStartColor($color);
    }

    public function setFillType(string $fillType): static
    {
        $this->getFill()->setFillType($fillType);
        return $this;
    }

    public function setFillStartColor(Color $color): static
    {
        $this->getFill()->setStartColor($color);
        return $this;
    }

    public function setTextWrap(bool $wrap): static
    {
        $this->getAlignment()->setWrapText($wrap);
        return $this;
    }

    public function setVerticalAlignment(string $alignment): static
    {
        $this->getAlignment()->setVertical($alignment);
        return $this;
    }

    public function setHorizontalAlignment(string $alignment): static
    {
        $this->getAlignment()->setHorizontal($alignment);
        return $this;
    }
}
