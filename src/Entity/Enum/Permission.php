<?php

namespace App\Entity\Enum;

enum Permission: string
{
    // N.B. A recipient's owner will also implicitly have this role for of that recipient's FundAwards
    case SUBMITTER = 'SUBMITTER';   // Can sign off on FundReturns
    case CHECKER = 'CHECKER'; // Can change the status of sections
    case EDITOR = 'EDITOR';   // Can edit sections/funds
    case VIEWER = 'VIEWER';   // Can view funds
}
