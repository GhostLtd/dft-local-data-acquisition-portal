<?php

namespace App\Entity\Enum;

enum SectionDisplayGroup: string
{
    case DETAILS = 'details';
    case EXPENSES = 'expenses';
    case MILESTONES = 'milestones';
}
