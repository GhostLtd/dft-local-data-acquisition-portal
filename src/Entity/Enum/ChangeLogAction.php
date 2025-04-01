<?php

namespace App\Entity\Enum;

enum ChangeLogAction : string
{
    case DELETE = 'delete';
    case INSERT = 'insert';
    case UPDATE = 'update';
}
