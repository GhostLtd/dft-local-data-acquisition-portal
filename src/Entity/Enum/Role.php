<?php

namespace App\Entity\Enum;

class Role
{
    // Why is this not an enum?
    // See: https://github.com/symfony/symfony/discussions/46006
    const string CAN_CHANGE_AUTHORITY = 'CAN_CHANGE_AUTHORITY';
    const string CAN_EDIT = 'CAN_EDIT';
    const string CAN_MANAGE_SCHEMES = 'CAN_MANAGE_SCHEMES';
    const string CAN_MANAGE_USERS = 'CAN_MANAGE_USERS';
    const string CAN_SIGN_OFF_RETURN = 'CAN_SIGN_OFF_RETURN';
    const string CAN_VIEW = 'CAN_VIEW';

    // Scheme specific
    const string CAN_DELETE_SCHEME = 'CAN_DELETE_SCHEME';
    const string CAN_EDIT_CRITICAL_SCHEME_FIELDS = 'CAN_EDIT_CRITICAL_SCHEME_FIELDS'; // e.g. Scheme ID
    const string CAN_EDIT_CRITICAL_CRSTS_SCHEME_FIELDS = 'CAN_EDIT_CRITICAL_CRSTS_SCHEME_FIELDS'; // e.g. isRetained
    const string CAN_REMOVE_CRSTS_FUND_FROM_SCHEME = 'CAN_REMOVE_CRSTS_FUND_FROM_SCHEME';

    // Scheme-return specific
    const string CAN_MARK_SCHEME_RETURN_AS_READY = 'CAN_MARK_SCHEME_RETURN_AS_READY';
    const string CAN_MARK_SCHEME_RETURN_AS_NOT_READY = 'CAN_MARK_SCHEME_RETURN_AS_NOT_READY';
    const string CAN_EDIT_SCHEME_RETURN_EXPENSES = 'CAN_EDIT_SCHEME_RETURN_EXPENSES';
    const string CAN_VIEW_SCHEME_RETURN_EXPENSES = 'CAN_VIEW_SCHEME_RETURN_EXPENSES';
}
