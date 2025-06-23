<?php

namespace App\Services;

use App\Models\AccessToken;
use App\Models\User;
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
        $user_uuid = $this->request->getUser();
        if ($user_uuid === null or Uuid::isValid($user_uuid) === false) {
            return false;
        }
        $user = User::findOrFail($user_uuid);

        $accessToken = null;
        $user->accessTokens->each(function (AccessToken $token) use (&$accessToken) {
            if ($token->token == $this->request->getPassword()) {
                $accessToken = $token;

                return;
            }
        });

        if ($accessToken === null or $accessToken->user_id !== $user->id) {
            return false;
        }

        if ($accessToken->expires_at !== null and $accessToken->expires_at->isPast()) {
            return false;
        }

        // Set current user to token user
        $this->setUser($user);

        // Update last_used_at without updating last_updated
        $accessToken->timestamps = false;
        $accessToken->last_used_at = now();
        $accessToken->save();

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
     * @return string|null
     */
    public function id()
    {
        if ($user = $this->user()) {
            return $this->user()->id;
        }

        return null;
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
