<?php

namespace App\Entity\Enum;

enum Permission: string
{
    // N.B. An authority's admin will also implicitly have this role for of that authority's FundAwards
    case SIGN_OFF = 'SIGN_OFF';   // Can sign off / submit a return
    case MARK_AS_READY = 'MARK_AS_READY'; // Can mark a scheme as ready for signoff
    case EDITOR = 'EDITOR';   // Can edit
    case VIEWER = 'VIEWER';   // Can view
}
