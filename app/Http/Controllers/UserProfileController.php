<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;

#[Group('User Profile')]
class UserProfileController extends Controller
{
    public function index()
    {
        return UserResource::collection(User::all());
    }
}
