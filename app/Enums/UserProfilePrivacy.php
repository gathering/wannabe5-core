<?php

namespace App\Enums;

enum UserProfilePrivacy: int
{
    case CREW = 2; // Everyone with access to events you are part of
    case NEEDTOKNOW = 3; // Chiefs and Organizers of events you are part of
    case PRIVATE = 4; // Only you
}
