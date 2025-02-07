<?php

namespace App\Entity\Enum;

class InternalRole
{
    // Why is this not an enum?
    // See: https://github.com/symfony/symfony/discussions/46006
    const string HAS_VALID_SIGN_OFF_PERMISSION = 'HAS_VALID_SIGN_OFF_PERMISSION';
    const string HAS_VALID_MARK_AS_READY_PERMISSION = 'HAS_VALID_MARK_AS_READY_PERMISSION';
    const string HAS_VALID_EDIT_PERMISSION = 'HAS_VALID_EDIT_PERMISSION';
    const string HAS_VALID_VIEW_PERMISSION = 'HAS_VALID_VIEW_PERMISSION';
}
