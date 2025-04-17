<?php

namespace App\Entity\Enum;

use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;

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

    /**
     * @return class-string<FundReturn>
     */
    public function getFundReturnClass(): string
    {
        return match($this) {
            self::CRSTS1 => CrstsFundReturn::class,
            default => throw new \RuntimeException("Fund {$this->name} not supported by getFundReturnClass()"),
        };
    }
}
