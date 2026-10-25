<?php

namespace Database\Seeders;

use App\Models\AccessAttribute;
use App\Models\AccessAttributeType;
use App\Models\CommunicationList;
use App\Models\CommunicationTemplate;
use App\Models\Crew;
use App\Models\CrewEvent;
use App\Models\CrewPage;
use App\Models\CrewRole;
use App\Models\Task;
use App\Models\TaskRecipient;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Minimal domain data so local frontend + testbruker are usable after login.
 */
class LocalStackSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $emptyDoc = ['type' => 'doc', 'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Local stack welcome page (seeded).'],
                ],
            ],
        ]];

        $events = [
            [
                'slug' => 'tg26',
                'name' => 'The Gathering 2026',
                'visible' => true,
                'active' => true,
                'applications_open' => true,
            ],
            [
                'slug' => 'tg25',
                'name' => 'The Gathering 2025',
                'visible' => true,
                'active' => false,
                'applications_open' => false,
            ],
            [
                'slug' => 'tg24',
                'name' => 'The Gathering 2024',
                'visible' => false,
                'active' => true,
                'applications_open' => false,
            ],
        ];

        foreach ($events as $eventData) {
            $event = CrewEvent::query()->updateOrCreate(
                ['slug' => $eventData['slug']],
                [
                    'name' => $eventData['name'],
                    'location' => 'Vikingskipet, Hamar',
                    'start_date' => '2026-04-01',
                    'end_date' => '2026-04-05',
                    'visible' => $eventData['visible'],
                    'active' => $eventData['active'],
                    'applications_open' => $eventData['applications_open'],
                ]
            );

            foreach (['event_frontpage', 'event_member_frontpage', 'public_frontpage'] as $type) {
                CrewPage::query()->updateOrCreate(
                    [
                        'event_id' => $event->id,
                        'type' => $type,
                    ],
                    ['content' => $emptyDoc]
                );
            }

            CrewRole::query()->firstOrCreate(
                [
                    'event_id' => $event->id,
                    'name' => 'chief',
                ],
                [
                    'title' => 'Chief',
                    'description' => 'Crew chief',
                ]
            );

            $crew = Crew::query()->updateOrCreate(
                [
                    'event_id' => $event->id,
                    'name' => 'Infrastructure',
                ],
                [
                    'hidden' => false,
                    'can_apply' => true,
                    'short_description' => 'Local stack crew — apply here',
                    'long_description' => 'Seeded crew for local development.',
                    'sorted_weight' => 1,
                ]
            );

            CrewPage::query()->updateOrCreate(
                [
                    'event_id' => $event->id,
                    'type' => 'event_member_page',
                ],
                ['content' => $emptyDoc]
            );

            $task = Task::query()->updateOrCreate(
                [
                    'event_id' => $event->id,
                    'name' => 'Local stack task',
                ],
                [
                    'type' => 'generic',
                    'enabled' => true,
                    'private' => false,
                    'short_description' => 'Seeded task',
                    'long_description' => $emptyDoc,
                    'icon' => 'task',
                    'skippable' => true,
                ]
            );

            TaskRecipient::query()->updateOrCreate(
                [
                    'task_id' => $task->id,
                    'recipient_type' => 'event',
                    'recipient_id' => (string) $event->id,
                ],
                [
                    'mandatory' => true,
                    'approval_needed' => false,
                    'filter' => [],
                ]
            );

            CommunicationList::query()->updateOrCreate(
                [
                    'event_id' => $event->id,
                    'identifier' => 'local-stack-'.$event->slug,
                ],
                [
                    'type' => 'mail',
                    'summary' => 'Seeded communication list',
                ]
            );
        }

        CommunicationTemplate::query()->updateOrCreate(
            ['name' => 'local-stack-welcome'],
            [
                'title' => 'Local stack welcome',
                'content' => 'Hello {{name}}',
                'schema' => ['type' => 'object'],
                'source_type' => 'manual',
            ]
        );

        // Global public frontpage fallback (event_id=0).
        CrewPage::query()->updateOrCreate(
            [
                'event_id' => 0,
                'type' => 'public_frontpage',
            ],
            ['content' => $emptyDoc]
        );

        // Keycloak testbruker UUID — create if missing so attribute seed is not skipped
        // when make e2e only runs LocalStackSeeder (not DatabaseSeeder).
        $userId = 'eaf9efc2-adbb-4b27-b5a9-f6c60197ab56';
        $user = User::query()->firstOrCreate(
            ['id' => $userId],
            [
                'username' => 'testbruker@example.com',
                'type' => 'user',
            ]
        );

        $user->userProfile?->update([
            'firstname' => 'Test',
            'lastname' => 'Brukersen',
            'nickname' => 'testbruker',
            'email' => $user->username,
        ]);

        // Mirrors wannabe5-backend access AttributeSeeder for testbruker (admin-ish *).
        $attributeKeys = [
            'access:owner',
            'access:reader',
            'com:admin',
            'com:client',
            'com:manager',
            'crew:applications:owner',
            'crew:applications:manager',
            'crew:manager',
            'crew:member',
            'crew:owner',
            'crew:team:member',
            'crew:team:owner',
            'event:member',
            'event:owner',
            'event:reader',
            'list:manager',
            'list:owner',
            'list:reader',
            'profile:image:manager',
            'profile:owner',
            'user:owner',
        ];

        foreach ($attributeKeys as $key) {
            AccessAttributeType::query()->firstOrCreate(
                ['key' => $key],
                [
                    'source' => 'local-seed',
                    'summary' => $key,
                    'description' => 'Local stack seed',
                ]
            );

            AccessAttribute::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'key' => $key,
                    'source' => 'local-seed',
                ],
                [
                    'value' => $key === 'user:owner' ? [$userId] : ['*'],
                ]
            );
        }
    }
}
