<?php

namespace App\Domain\Shared\Services;

use App\Models\AccessAttribute;
use App\Models\Crew;
use App\Models\CrewUser;
use App\Models\UserProfile;
use Illuminate\Support\Arr;

class ServiceAccessAttributeRegistry
{
    /**
     * @return array{users: array<int, array{0:string,1:string,2:mixed}>, ownedAttributes: array<string, string>, default-service-user: array<string, mixed>, created: int}
     */
    public function forService(string $service): array
    {
        $owned = $this->ownedAttributes($service);
        $users = $this->userAttributes($service);
        $defaults = $this->defaultServiceUserAttributes($service);

        return [
            'users' => $users,
            'ownedAttributes' => $owned,
            'default-service-user' => $defaults,
            'created' => now()->timestamp,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function ownedAttributes(string $service): array
    {
        return match ($service) {
            'access' => [
                'access:reader' => 'Can read attribute',
                'access:owner' => 'Owner of attribute, full access',
            ],
            'application' => [
                'crew:applications:manager' => 'Can read applications for crew',
            ],
            'communication' => [
                'com:admin' => 'Can read and edit message, notifications, and templates',
                'com:manager' => 'Can manage messages, notifications, and templates. But not read message data',
                'com:client' => 'Can send message requests, and see relevant status data',
                'list:owner' => 'Can do anything related to a list',
                'list:manager' => 'Can manage list, but not see sensitive data',
                'list:reader' => 'Can access list and senstive data, but read-only',
            ],
            'crew' => [
                'event:member' => 'Member of an event',
                'event:manager' => 'Manager of an event, can do day to day tasks',
                'event:owner' => 'Owner of an event, full access',
                'crew:member' => 'Member of crew',
                'crew:manager' => 'Manager of crew, can do day to day tasks',
                'crew:owner' => 'Owner of crew, full access',
                'crew:team:member' => 'Member of team',
                'crew:team:owner' => 'Owner of team, full access',
            ],
            'profile' => [
                'profile:owner' => 'Owner of a profile (full access)',
                'profile:p:priv' => 'Can read private fields (full read access) on a profile',
                'profile:p:ntk' => 'Can read need to know fields (high read access) on a profile',
                'profile:p:crew' => 'Can read crew fields (low read access) on a profile',
                'profile:reader' => 'Can read public fields on a profile',
                'profile:image:manager' => 'Can manage profile images',
            ],
            'tasking' => [
                'task:reader' => 'Can read tasks',
                'task:approver' => 'Can approve tasks',
            ],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultServiceUserAttributes(string $service): array
    {
        return match ($service) {
            'access' => ['access:reader' => '*'],
            'communication' => [
                'com:client' => '*',
                'list:reader' => '*',
                'list:manager' => '*',
            ],
            'crew' => [
                'crew:reader' => '*',
                'event:reader' => '*',
            ],
            'profile' => [
                'profile:p:priv' => '*',
                'profile:reader' => '*',
            ],
            default => [],
        };
    }

    /**
     * @return array<int, array{0:string,1:string,2:mixed}>
     */
    public function userAttributes(string $service): array
    {
        return match ($service) {
            'access' => $this->accessStoredAttributes(),
            'crew' => $this->crewMembershipAttributes(),
            'profile' => $this->profileOwnershipAttributes(),
            default => [],
        };
    }

    /**
     * @return array<int, array{0:string,1:string,2:mixed}>
     */
    private function accessStoredAttributes(): array
    {
        $raw = [];
        AccessAttribute::query()->orderBy('id')->chunk(200, function ($rows) use (&$raw): void {
            foreach ($rows as $attribute) {
                $this->addUserAttribute($raw, (string) $attribute->user_id, (string) $attribute->key, $attribute->value ?? []);
            }
        });

        return $this->flattenAttributes($raw);
    }

    /**
     * @return array<int, array{0:string,1:string,2:mixed}>
     */
    private function profileOwnershipAttributes(): array
    {
        $raw = [];
        UserProfile::query()->orderBy('id')->chunk(200, function ($rows) use (&$raw): void {
            foreach ($rows as $profile) {
                $this->addUserAttribute($raw, (string) $profile->user_id, 'profile:owner', $profile->id);
            }
        });

        return $this->flattenAttributes($raw);
    }

    /**
     * @return array<int, array{0:string,1:string,2:mixed}>
     */
    private function crewMembershipAttributes(): array
    {
        $raw = [];
        $roleMap = [
            'member' => 'crew:member',
            'shiftleader' => 'crew:member',
            'chief' => 'crew:manager',
            'co-chief' => 'crew:manager',
            'organizer' => 'crew:owner',
            'leader' => 'crew:owner',
        ];

        CrewUser::query()->with(['role'])->orderBy('id')->chunk(200, function ($rows) use (&$raw, $roleMap): void {
            foreach ($rows as $crewUser) {
                $roleName = $crewUser->role?->name ?? 'member';
                $attribute = $roleMap[$roleName] ?? 'crew:member';
                $crew = Crew::query()->with('children')->find($crewUser->crew_id);
                if ($crew === null) {
                    continue;
                }

                $this->addUserAttribute($raw, (string) $crewUser->user_id, $attribute, $crew->id);
                $this->addUserAttribute($raw, (string) $crewUser->user_id, 'event:member', $crew->event_id);

                if (in_array($attribute, ['crew:owner', 'crew:manager'], true)) {
                    foreach ($crew->descendantIds() as $childCrewId) {
                        $this->addUserAttribute($raw, (string) $crewUser->user_id, $attribute, $childCrewId);
                    }
                }

                if ($crewUser->team_id !== null) {
                    $teamAttribute = str_contains($attribute, 'owner') || str_contains($attribute, 'manager')
                        ? 'crew:team:owner'
                        : 'crew:team:member';
                    $this->addUserAttribute($raw, (string) $crewUser->user_id, $teamAttribute, $crewUser->team_id);
                }
            }
        });

        return $this->flattenAttributes($raw);
    }

    private function addUserAttribute(array &$existing, string $userId, string $attribute, mixed $value): void
    {
        $path = "{$userId}.{$attribute}";
        Arr::set(
            $existing,
            $path,
            array_values(array_unique(array_merge(
                Arr::get($existing, $path, []),
                Arr::wrap($value),
            )))
        );
    }

    /**
     * @param  array<string, array<string, array<int, mixed>>>  $raw
     * @return array<int, array{0:string,1:string,2:mixed}>
     */
    private function flattenAttributes(array $raw): array
    {
        $attributes = [];
        foreach ($raw as $userId => $attrs) {
            foreach ($attrs as $key => $value) {
                $attributes[] = [(string) $userId, (string) $key, $value];
            }
        }

        return $attributes;
    }
}
