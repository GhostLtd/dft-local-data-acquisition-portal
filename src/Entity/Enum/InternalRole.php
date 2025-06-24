<?php

namespace App\Entity\Enum;

class InternalRole
{
    // Why is this not an enum?
    // See: https://github.com/symfony/symfony/discussions/46006

    const string HAS_VALID_MANAGE_SCHEME_PERMISSION = 'INTERNAL_HAS_MANAGE_SCHEME';
    const string HAS_VALID_SIGN_OFF_PERMISSION = 'INTERNAL_HAS_SIGN_OFF';
    const string HAS_VALID_MARK_AS_READY_PERMISSION = 'INTERNAL_HAS_MARK_AS_READY';
    const string HAS_VALID_EDIT_PERMISSION = 'INTERNAL_HAS_EDIT';
    const string HAS_VALID_VIEW_PERMISSION = 'INTERNAL_HAS_VIEW';
}
