<?php

namespace App\Services;

use App\Models\AccessToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class TokenGuard implements Guard
{
    protected $user;

    protected Request $request;

    public function __construct(Request $request)
    {
        $this->user = null;
        $this->request = $request;

        $this->authenticate();
    }

    /**
     * Validate and authenticate user
     *
     * @return bool
     */
    protected function authenticate()
    {
        $user = $this->request->getUser();
        if (Uuid::isValid($user) === false) {
            return false;
        }
        $token = AccessToken::where([['user_id', $user], ['token', $this->request->getPassword()]])->first();
        if ($token === null) {
            return false;
        }
        $user = User::find($token->user_id);
        $this->setUser($user);

        $token->last_used_at = Carbon::now();
        $token->save();

        return true;
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return ! is_null($this->user());
    }

    /**
     * Determine if the guard has a user instance.
     *
     * @return bool
     */
    public function hasUser()
    {
        return ! is_null($this->user());
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return ! $this->check();
    }

    /**
     * Set the current user.
     *
     * @return void
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (is_null($this->user)) {
            return null;
        }

        return $this->user;
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|null
     */
    public function id()
    {
        if ($user = $this->user()) {
            return $this->user()->id;
        }
    }

    /**
     * Validate a user's credentials.
     *
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return false;
    }
}
