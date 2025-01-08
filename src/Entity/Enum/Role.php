<?php

namespace App\Entity\Enum;

class Role
{
    // Why is this not an enum?
    // See: https://github.com/symfony/symfony/discussions/46006
    const string CAN_SET_LEAD_CONTACT = 'CAN_SET_LEAD_CONTACT';
    const string CAN_SUBMIT = 'CAN_SUBMIT';
    const string CAN_COMPLETE = 'CAN_COMPLETE';
    const string CAN_EDIT = 'CAN_EDIT';
    const string CAN_VIEW = 'CAN_VIEW';
}
