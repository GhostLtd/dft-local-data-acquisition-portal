<?php

namespace App\Entity\Enum;

enum JobState: string
{
    case NEW = 'new';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
