<?php

namespace App\Entity\Config\Table;

class Header extends AbstractCell
{
    public function getType(): string
    {
        return 'header';
    }
}
