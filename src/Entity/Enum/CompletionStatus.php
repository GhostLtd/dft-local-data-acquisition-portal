<?php

namespace App\Entity\Enum;

enum CompletionStatus: string
{
    case NOT_REQUIRED = 'not_required';
    case NOT_STARTED = 'not_started';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
}
