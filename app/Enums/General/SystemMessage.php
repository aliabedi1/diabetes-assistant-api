<?php

namespace App\Enums\General;

use App\Traits\EnumTools;

enum SystemMessage: int
{
    use EnumTools;

    case FAIL           = 0;
    case SUCCESS        = 1;
    case INTERNAL_ERROR = 10;
    case DATA_NOT_FOUND = 11;
    case PAGE_NOT_FOUND = 12;
    case BAD_DATA       = 13;

    // Domain exception codes. starting from 100
    case DATA_EXIST      = 100;
    case USER_NOT_FOUND  = 101;
    case USER_IS_BLOCKED = 102;
    case ACCESS_DENIED   = 103;
}
