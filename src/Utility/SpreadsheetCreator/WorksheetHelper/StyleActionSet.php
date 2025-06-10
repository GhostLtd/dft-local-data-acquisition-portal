<?php

namespace App\Utility\SpreadsheetCreator\WorksheetHelper;

use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StyleActionSet
{
    protected array $actions = [];

    public function apply(AbstractStyledLocation $helper): void
    {
        foreach($this->actions as ['function' => $function, 'arguments' => $arguments]) {
            $helper->$function(...$arguments);
        }
    }

    // -----

    public function setBold(int $bold): static
    {
        $this->actions[] = ['function' => __FUNCTION__, 'arguments' => func_get_args()];
        return $this;
    }

    public function setItalic(int $italic): static
    {
        $this->actions[] = ['function' => __FUNCTION__, 'arguments' => func_get_args()];
        return $this;
    }

    public function setColor(Color $colour): static
    {
        $this->actions[] = ['function' => __FUNCTION__, 'arguments' => func_get_args()];
        return $this;
    }

    public function setWidth(int $width): static
    {
        $this->actions[] = ['function' => __FUNCTION__, 'arguments' => func_get_args()];
        return $this;
    }

    public function setFill(Color $color, string $fillType=Fill::FILL_SOLID): static
    {
        $this->actions[] = ['function' => __FUNCTION__, 'arguments' => func_get_args()];
        return $this;
    }

    public function setFillType(string $fillType): static
    {
        $this->actions[] = ['function' => __FUNCTION__, 'arguments' => func_get_args()];
        return $this;
    }

    public function setFillStartColor(Color $color): static
    {
        $this->actions[] = ['function' => __FUNCTION__, 'arguments' => func_get_args()];
        return $this;
    }

    public function setWrapText(bool $wrap): static
    {
        $this->actions[] = ['function' => __FUNCTION__, 'arguments' => func_get_args()];
        return $this;
    }

    public function setVerticalAlignment(string $alignment): static
    {
        $this->actions[] = ['function' => __FUNCTION__, 'arguments' => func_get_args()];
        return $this;
    }

    public function setHorizontalAlignment(string $alignment): static
    {
        $this->actions[] = ['function' => __FUNCTION__, 'arguments' => func_get_args()];
        return $this;
    }

    public function setNumberFormatCode(string $string): static
    {
        $this->actions[] = ['function' => __FUNCTION__, 'arguments' => func_get_args()];
        return $this;
    }

    public function setLeftBorder(Color $color, bool|string $borderStyle=Border::BORDER_THIN): static
    {
        $this->actions[] = ['function' => __FUNCTION__, 'arguments' => func_get_args()];
        return $this;
    }

    public function setRightBorder(Color $color, bool|string $borderStyle=Border::BORDER_THIN): static
    {
        $this->actions[] = ['function' => __FUNCTION__, 'arguments' => func_get_args()];
        return $this;
    }

    public function setTopBorder(Color $color, bool|string $borderStyle=Border::BORDER_THIN): static
    {
        $this->actions[] = ['function' => __FUNCTION__, 'arguments' => func_get_args()];
        return $this;
    }

    public function setBottomBorder(Color $color, bool|string $borderStyle=Border::BORDER_THIN): static
    {
        $this->actions[] = ['function' => __FUNCTION__, 'arguments' => func_get_args()];
        return $this;
    }

    public function setAllBorders(Color $color, bool|string $borderStyle=Border::BORDER_THIN): static
    {
        $this->actions[] = ['function' => __FUNCTION__, 'arguments' => func_get_args()];
        return $this;
    }
}
