<?php

namespace App\Entity\Enum;

enum Rating: string
{
    case GREEN = "green";
    case GREEN_AMBER = "green_amber";
    case AMBER = "amber";
    case AMBER_RED = "amber_red";
    case RED = "red";

    public function getTagClass(): string
    {
        return "govuk-tag--{$this->getTagColour()}";
    }

    public function getTagColour(): string
    {
        return match ($this) {
            self::RED => 'red',
            self::AMBER_RED => 'pink',
            self::AMBER => 'orange',
            self::GREEN_AMBER => 'yellow',
            self::GREEN => 'green',
        };
    }
}
