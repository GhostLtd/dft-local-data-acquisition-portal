<?php

namespace App\Utility\SpreadsheetCreator\WorksheetHelper;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class AbstractStyledLocation
{
    public function __construct(
        protected Worksheet $worksheet,
    ) {}

    public function apply(StyleActionSet $actionSet): static
    {
        $actionSet->apply($this);
        return $this;
    }

    abstract public function getStyle(): Style;

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

    public function getBorders(): Borders
    {
        return $this->getStyle()->getBorders();
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

    public function setWrapText(bool $wrap): static
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

    public function setNumberFormatCode(string $string): static
    {
        $this->getStyle()->getNumberFormat()->setFormatCode($string);
        return $this;
    }

    public function setLeftBorder(Color $color, bool|string $borderStyle=Border::BORDER_THIN): static
    {
        $this->getBorders()->getLeft()->setColor($color)->setBorderStyle($borderStyle);
        return $this;
    }

    public function setRightBorder(Color $color, bool|string $borderStyle=Border::BORDER_THIN): static
    {
        $this->getBorders()->getRight()->setColor($color)->setBorderStyle($borderStyle);
        return $this;
    }

    public function setTopBorder(Color $color, bool|string $borderStyle=Border::BORDER_THIN): static
    {
        $this->getBorders()->getTop()->setColor($color)->setBorderStyle($borderStyle);
        return $this;
    }

    public function setBottomBorder(Color $color, bool|string $borderStyle=Border::BORDER_THIN): static
    {
        $this->getBorders()->getBottom()->setColor($color)->setBorderStyle($borderStyle);
        return $this;
    }

    public function setAllBorders(Color $color, bool|string $borderStyle=Border::BORDER_THIN): static
    {
        $this->getBorders()->getAllBorders()->setColor($color)->setBorderStyle($borderStyle);
        return $this;
    }
}
