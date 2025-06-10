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
    protected Color $cellShadeOdd;

    protected Color $black;
    protected Color $lightBlue;
    protected Color $blue;
    protected Color $darkGray;
    protected Color $lightGray;
    protected Color $yellow;
    protected Color $white;

    public function __construct()
    {
        $this->black = new Color(Color::COLOR_BLACK);
        $this->blue = new Color('ff2f5597');
        $this->darkGray = new Color('ff888888');
        $this->lightBlue = new Color('ffb1c5e7');
        $this->lightGray = new Color('ffcccccc');
        $this->yellow = new Color(Color::COLOR_YELLOW);
        $this->white = new Color(Color::COLOR_WHITE);

        $this->cellShadeEven = new Color('ffffffff');
        $this->cellShadeOdd = new Color('ffeeeeee');
    }

    public function getTagColours(string $colour): array
    {
        return match($colour) {
            "blue" => [new Color('ff00c2d4a'), new Color('ffbbd4ea')],
            "green" => [new Color("ff005a30"), new Color('ffcce2d8')],
            "orange" => [new Color('ff6e3619'), new Color('fffcd6c3')],
            "pink" => [new Color('ff6b1c40'), new Color('fff9e1ec')],
            "red" => [new Color('ff2a0b06'), new Color('fff4cdc6')],
            "yellow" => [new Color('ff594d00'), new Color('fffff7bf')],
        };
    }
}
