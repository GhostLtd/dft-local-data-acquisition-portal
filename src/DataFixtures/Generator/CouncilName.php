<?php

namespace App\DataFixtures\Generator;

use Faker\Provider\Base;

class CouncilName extends Base
{
    public function council_name(): string
    {
        return $this->generator->randomElement(self::COUNCIL_NAMES);
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