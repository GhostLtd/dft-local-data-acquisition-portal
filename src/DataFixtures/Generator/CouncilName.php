<?php

namespace App\DataFixtures\Generator;

class CouncilName
{
    public static function generate(): string
    {
        return self::COUNCIL_NAMES[mt_rand(0, count(self::COUNCIL_NAMES) - 1)];
    }

    public const array COUNCIL_NAMES = [
        "Cambridgeshire and Peterborough Combined Authority",
        "Greater Manchester Combined Authority",
        "North East Combined Authority",
        "North of Tyne Combined Authority",
        "Tees Valley Combined Authority",
        "West Midlands Combined Authority",
        "West Yorkshire Combined Authority",
        "West of England Combined Authority",
    ];
}