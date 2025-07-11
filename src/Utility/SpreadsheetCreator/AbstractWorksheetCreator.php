<?php

namespace App\Utility\SpreadsheetCreator;

use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class AbstractWorksheetCreator
{
    const string NUMERIC_FORMAT_CODE = '#,##0.00;[Red]-#,##0.00';

    protected Worksheet $worksheet;

    protected int $offsetX = 0;
    protected int $offsetY = 0;

    protected Color $cellShadeEven;
    protected
    Color $cellShadeOdd;

    protected Color $black;
    protected Color $blue;
    protected Color $darkGray;
    protected Color $lightGray;
    protected Color $white;

    public function __construct()
    {
        $this->blue = new Color('ff2f5597');
        $this->black = new Color(Color::COLOR_BLACK);
        $this->darkGray = new Color('ff888888');
        $this->white = new Color(Color::COLOR_WHITE);
        $this->lightGray = new Color('ffcccccc');

        $this->cellShadeEven = new Color('ffeeeeee');
        $this->cellShadeOdd = new Color('ffdddddd');
    }

    protected function setBold(int $x, int $y): void
    {
        $this->worksheet->getStyle([$x, $y])->getFont()->setBold(true);
    }

    protected function setItalic(int $x, int $y): void
    {
        $this->worksheet->getStyle([$x, $y])->getFont()->setItalic(true);
    }

    protected function relX(int $x): int
    {
        return $x + $this->offsetX;
    }

    protected function relY(int $y): int
    {
        return $y + $this->offsetY;
    }

    protected function relXY(int $x, int $y): array
    {
        return [$this->relX($x), $this->relY($y)];
    }
    protected function relXYXY(int $x1, int $y1, int $x2, int $y2): array
    {
        return [$this->relX($x1), $this->relY($y1), $this->relX($x2), $this->relY($y2)];
    }
}
