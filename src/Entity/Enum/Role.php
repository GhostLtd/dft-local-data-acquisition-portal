<?php

namespace App\Entity\Enum;

class Role
{
    // Why is this not an enum?
    // See: https://github.com/symfony/symfony/discussions/46006
    const string CAN_CHANGE_AUTHORITY = 'CAN_CHANGE_AUTHORITY';
    const string CAN_EDIT = 'CAN_EDIT';
    const string CAN_EDIT_SCHEME_EXPENSES = 'CAN_EDIT_SCHEME_EXPENSES';
    const string CAN_MANAGE_USERS = 'CAN_MANAGE_USERS';
    const string CAN_MARK_AS_READY = 'CAN_MARK_AS_READY';
    const string CAN_MARK_AS_NOT_READY = 'CAN_MARK_AS_NOT_READY';
    const string CAN_SIGN_OFF_RETURN = 'CAN_SIGN_OFF_RETURN';
    const string CAN_VIEW = 'CAN_VIEW';
    const string CAN_VIEW_SCHEME_EXPENSES = 'CAN_VIEW_SCHEME_EXPENSES';
}
