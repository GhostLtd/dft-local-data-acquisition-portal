<?php

namespace App\DataFixtures\Generator;

use Faker\Provider\Base;

class SchemeName extends Base
{
    protected const array TEMPLATES = [
        '{LOCATION} bridge',
        '{LOCATION} bus priority signing and lining',
        '{LOCATION} bus stop enhancement programme',
        '{LOCATION} carriageway',
        '{LOCATION} economic corridor improvement',
        '{LOCATION} infrastructure',
        '{LOCATION} interchange',
        '{LOCATION} new stops',
        '{LOCATION} minor works',
        '{LOCATION} park and ride',
        '{LOCATION} public transport',
        '{LOCATION} town centre development',
        '{LOCATION} station improvements',
        '{LOCATION} viaduct',

        'City centre radials: {ROAD_NAME}',
        'Deliver {LOCATION} station',
        'Design and development work on {LOCATION} station',
        '{LOCATION} development of new stations',
        '{LOCATION} development of long-term rapid transit options',
        '{LOCATION} depot charging infrastructure',
        'Initial phased delivery of {LOCATION} corridor',
        'Integrated ticketing and information',

        '{PERIOD} bus transit corridor {LOCATION}',
        '{PERIOD} vehicles',
    ];

    public function scheme_name(): string
    {
        $name = $this->generator->randomElement(self::TEMPLATES);

        if (str_contains($name, '{LOCATION}')) {
            $name = str_replace('{LOCATION}', $this->generator->city(), $name);
        }

        if (str_contains($name, '{PERIOD}')) {
            $period = $this->generator->randomElement(['Future', 'Next-generation']);
            $name = str_replace('{PERIOD}', $period, $name);
        }

        if (str_contains($name, '{ROAD_NAME}')) {
            $roadName = $this->generator->randomElement(['A', 'B', 'M']).$this->generator->numberBetween(1, 9001);
            $name = str_replace('{ROAD_NAME}', $roadName, $name);
        }

        return $name;
    }
}
