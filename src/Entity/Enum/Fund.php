<?php

namespace App\Entity\Enum;

enum Fund: string
{
    case BSIP = "BSIP"; // Not currently used. Just for testing
    case CRSTS1 = "CRSTS1";
    case CRSTS2 = "CRSTS2";

    /**
     * @return array<int, Fund>
     */
    public static function enabledCases(): array {
        return [Fund::CRSTS1];
    }
}
