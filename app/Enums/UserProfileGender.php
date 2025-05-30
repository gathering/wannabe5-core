<?php

namespace App\Enums;

enum UserProfileGender: string
{
    case UNDEFINED = 'undefined';
    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';
}
