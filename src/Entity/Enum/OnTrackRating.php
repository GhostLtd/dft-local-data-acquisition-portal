<?php

namespace App\Entity\Enum;

enum OnTrackRating: string
{
    case GREEN = "green";
    case AMBER = "amber";
    case RED = "red";
    case SCHEME_COMPLETED = "scheme_completed";
    case SCHEME_CANCELLED = "scheme_cancelled";
    case SCHEME_SPLIT = "scheme_split";
    case SCHEME_MERGED = "scheme_merged";

    public function getTagClass(): string
    {
        return "govuk-tag--{$this->getTagColour()}";
    }

    public function getTagColour(): string
    {
        return match ($this) {
            self::RED => 'red',
            self::AMBER => 'orange',
            self::GREEN => 'green',

            self::SCHEME_COMPLETED,
            self::SCHEME_CANCELLED,
            self::SCHEME_MERGED,
            self::SCHEME_SPLIT => 'blue',
        };
    }

    public function shouldBePropagatedToFutureReturns(): bool
    {
        return in_array($this, [
            OnTrackRating::SCHEME_CANCELLED,
            OnTrackRating::SCHEME_COMPLETED,
            OnTrackRating::SCHEME_MERGED,
            OnTrackRating::SCHEME_SPLIT,
        ]);
    }

    public function shouldSchemeBeEditableInTheFuture(): bool
    {
        // If this state is set in one return, should the next return be editable?
        // e.g. If a scheme completed in Q3 2024 then it should not be editable in Q4 2024
        return !in_array($this, self::getFutureNonEditableStates());
    }

    /**
     * @return array<int, OnTrackRating>
     */
    public static function getFutureNonEditableStates(): array
    {
        // N.B. "scheme_cancelled" is not on the list because this state translates as being "Cancelled / on hold"
        //      As such, such a scheme could be put back into active development in the future
        return [
            OnTrackRating::SCHEME_COMPLETED,
            OnTrackRating::SCHEME_MERGED,
            OnTrackRating::SCHEME_SPLIT,
        ];
    }
}
