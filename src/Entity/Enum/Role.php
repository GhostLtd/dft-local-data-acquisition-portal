<?php

namespace App\Entity\Enum;

class Role
{
    // Why is this not an enum?
    // See: https://github.com/symfony/symfony/discussions/46006
    const string CAN_CHANGE_AUTHORITY = 'CAN_CHANGE_AUTHORITY';
    const string CAN_EDIT = 'CAN_EDIT';
    const string CAN_EDIT_USER = 'CAN_EDIT_USER';
    const string CAN_MANAGE_SCHEMES = 'CAN_MANAGE_SCHEMES';
    const string CAN_MANAGE_USERS = 'CAN_MANAGE_USERS';
    const string CAN_RELEASE_RETURNS = 'CAN_RELEASE_RETURNS';
    const string CAN_VIEW = 'CAN_VIEW';

    // Return specific
    const string CAN_EDIT_BASELINES = 'CAN_EDIT_BASELINES';
    const string CAN_SIGN_OFF_RETURN = 'CAN_SIGN_OFF_RETURN'; // Is the user hypothetically allowed to perform this action?
    const string CAN_RETURN_BE_SIGNED_OFF = 'CAN_RETURN_BE_SIGNED_OFF'; // Is the return valid for sign-off?
    const string CAN_REOPEN_RETURN = 'CAN_REOPEN_RETURN';

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
