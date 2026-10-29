<?php

namespace App\Http\Concerns;

trait CommunicationAccess
{
    protected function communicationAllowed(string $id = '*', bool $write = false): bool
    {
        $user = auth()->user();
        $keys = $write ? ['com:admin', 'com:manager'] : ['com:admin', 'com:manager', 'com:client'];
        foreach ($keys as $key) {
            if ($user->hasAccessAttribute($key, $id)) {
                return true;
            }
        }

        return ! $write && ($user->hasAccessAttribute('event:owner') || $user->hasAccessAttribute('event:manager'));
    }

    protected function authorizeCommunication(string $id = '*', bool $write = false): void
    {
        abort_unless($this->communicationAllowed($id, $write), 403);
    }

    protected function listAllowed(string $id = '*', bool $write = false): bool
    {
        $user = auth()->user();
        foreach ($write ? ['list:owner', 'list:manager'] : ['list:owner', 'list:manager', 'list:reader'] as $key) {
            if ($user->hasAccessAttribute($key, $id)) {
                return true;
            }
        }

        return $user->hasAccessAttribute('event:owner') || (! $write && $user->hasAccessAttribute('event:manager'));
    }

    protected function authorizeList(string $id = '*', bool $write = false): void
    {
        abort_unless($this->listAllowed($id, $write), 403);
    }
}
