<?php

namespace App\Enums;

enum UserProfilePrivacy: int
{
    case PUBLIC = 0;
    case CREW = 2;
    case NEEDTOKNOW = 3;
    case PRIVATE = 4;
}
