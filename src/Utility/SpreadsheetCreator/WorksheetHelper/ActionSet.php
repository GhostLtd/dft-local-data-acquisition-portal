<?php

namespace App\Utility\SpreadsheetCreator\WorksheetHelper;

use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ActionSet
{
    protected array $actions = [];

    public function apply(Location $helper): Location
    {
        foreach($this->actions as ['function' => $function, 'arguments' => $arguments]) {
            $helper->$function(...$arguments);
        }
        return $helper;
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

    public function setTextWrap(bool $wrap): static
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
}
