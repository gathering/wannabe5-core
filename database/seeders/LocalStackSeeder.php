<?php

namespace Database\Seeders;

use App\Models\AccessAttribute;
use App\Models\AccessAttributeType;
use App\Models\ApplicationAnswer;
use App\Models\ApplicationComment;
use App\Models\ApplicationQuestion;
use App\Models\ApplicationRecord;
use App\Models\CommunicationList;
use App\Models\CommunicationListOwner;
use App\Models\CommunicationListRecipient;
use App\Models\CommunicationMessage;
use App\Models\CommunicationNotification;
use App\Models\CommunicationTemplate;
use App\Models\Crew;
use App\Models\CrewEvent;
use App\Models\CrewPage;
use App\Models\CrewRole;
use App\Models\CrewTeam;
use App\Models\CrewUser;
use App\Models\Task;
use App\Models\TaskQuestion;
use App\Models\TaskRecipient;
use App\Models\TaskResponse;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Domain fixtures so local frontend + testbruker/normalbruker are usable after login.
 */
class LocalStackSeeder extends Seeder
{
    private const ADMIN_ID = 'eaf9efc2-adbb-4b27-b5a9-f6c60197ab56';

    private const NORMAL_ID = 'd7a57211-2187-44d2-a9bd-e5deba8fe5ae';

    private const APPLICANT_ID = 'b8c3e041-6f2a-4d91-9c1e-0a7f5d2e8b14';

    /** @var list<array{id: string, username: string, firstname: string, lastname: string, nickname: string}> */
    private const MEMBER_USERS = [
        [
            'id' => 'c1a10001-0001-4000-8000-000000000001',
            'username' => 'crewchief@example.com',
            'firstname' => 'Carla',
            'lastname' => 'Chief',
            'nickname' => 'crewchief',
        ],
        [
            'id' => 'c1a10001-0001-4000-8000-000000000002',
            'username' => 'crewshift@example.com',
            'firstname' => 'Sam',
            'lastname' => 'Shift',
            'nickname' => 'crewshift',
        ],
        [
            'id' => 'c1a10001-0001-4000-8000-000000000003',
            'username' => 'crewnet@example.com',
            'firstname' => 'Nina',
            'lastname' => 'Network',
            'nickname' => 'crewnet',
        ],
        [
            'id' => 'c1a10001-0001-4000-8000-000000000004',
            'username' => 'crewinfo@example.com',
            'firstname' => 'Ivan',
            'lastname' => 'Info',
            'nickname' => 'crewinfo',
        ],
        [
            'id' => 'c1a10001-0001-4000-8000-000000000005',
            'username' => 'crewsec@example.com',
            'firstname' => 'Sara',
            'lastname' => 'Security',
            'nickname' => 'crewsec',
        ],
    ];

    /** @var list<array{name: string, title: string, description: string}> */
    private const ROLES = [
        ['name' => 'member', 'title' => 'Member', 'description' => 'Crew member'],
        ['name' => 'shiftleader', 'title' => 'Shift leader', 'description' => 'Shift leader'],
        ['name' => 'chief', 'title' => 'Chief', 'description' => 'Crew chief'],
        ['name' => 'co-chief', 'title' => 'Co-chief', 'description' => 'Crew co-chief'],
        ['name' => 'organizer', 'title' => 'Organizer', 'description' => 'Event organizer'],
    ];

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
                'start_date' => '2026-04-01',
                'end_date' => '2026-04-05',
                'visible' => true,
                'active' => true,
                'applications_open' => true,
            ],
            [
                'slug' => 'tg25',
                'name' => 'The Gathering 2025',
                'start_date' => '2025-04-09',
                'end_date' => '2025-04-13',
                'visible' => true,
                'active' => false,
                'applications_open' => false,
            ],
            [
                'slug' => 'tg24',
                'name' => 'The Gathering 2024',
                'start_date' => '2024-03-27',
                'end_date' => '2024-03-31',
                'visible' => false,
                'active' => true,
                'applications_open' => false,
            ],
        ];

        $this->seedUsers();

        foreach ($events as $eventData) {
            $event = CrewEvent::query()->updateOrCreate(
                ['slug' => $eventData['slug']],
                [
                    'name' => $eventData['name'],
                    'location' => 'Vikingskipet, Hamar',
                    'start_date' => $eventData['start_date'],
                    'end_date' => $eventData['end_date'],
                    'visible' => $eventData['visible'],
                    'active' => $eventData['active'],
                    'applications_open' => $eventData['applications_open'],
                ]
            );

            foreach (['event_frontpage', 'event_member_frontpage', 'public_frontpage', 'event_member_page'] as $type) {
                CrewPage::query()->updateOrCreate(
                    [
                        'event_id' => $event->id,
                        'type' => $type,
                    ],
                    ['content' => $emptyDoc]
                );
            }

            $roles = $this->seedRoles($event);
            $crews = $this->seedCrews($event);
            $teams = $this->seedTeams($crews['infrastructure']);
            $this->seedCrewMemberships($event, $roles, $crews, $teams);
            $this->seedTasks($event, $crews, $emptyDoc);
            $this->seedCommunicationList($event);

            if ($event->slug === 'tg26') {
                $this->seedApplications($event, $crews);
            }
        }

        $template = CommunicationTemplate::query()->updateOrCreate(
            ['name' => 'local-stack-welcome'],
            [
                'title' => 'Local stack welcome',
                'content' => 'Hello {{name}}',
                'schema' => ['type' => 'object'],
                'source_type' => 'manual',
            ]
        );

        CommunicationNotification::query()->updateOrCreate(
            ['name' => 'local-stack-welcome'],
            [
                'mail_template_id' => $template->id,
                'sms_template_id' => $template->id,
                'slack_template_id' => $template->id,
            ]
        );

        CommunicationMessage::query()->updateOrCreate(
            [
                'channel' => 'mail',
                'notification_name' => 'local-stack-welcome',
                'state' => 'raw',
            ],
            [
                'recipient' => ['mail' => 'testbruker@example.com'],
                'data' => ['name' => 'Test'],
                'source' => ['seed' => 'local-stack'],
                'tries' => 0,
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
    }

    private function seedUsers(): void
    {
        $this->seedUserWithAttributes(
            self::ADMIN_ID,
            'testbruker@example.com',
            [
                'firstname' => 'Test',
                'lastname' => 'Brukersen',
                'nickname' => 'testbruker',
            ],
            [
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
            ],
            adminWildcard: true
        );

        // Non-admin for Playwright; intentionally not a crew member on tg26.
        $this->seedUserWithAttributes(
            self::NORMAL_ID,
            'normalbruker@example.com',
            [
                'firstname' => 'Normal',
                'lastname' => 'Bruker',
                'nickname' => 'normalbruker',
            ],
            [
                'profile:owner',
                'user:owner',
            ],
            adminWildcard: false
        );

        $this->seedUserWithAttributes(
            self::APPLICANT_ID,
            'applicant@example.com',
            [
                'firstname' => 'Ada',
                'lastname' => 'Applicant',
                'nickname' => 'applicant',
            ],
            [
                'profile:owner',
                'user:owner',
            ],
            adminWildcard: false
        );

        foreach (self::MEMBER_USERS as $member) {
            $this->seedUserWithAttributes(
                $member['id'],
                $member['username'],
                [
                    'firstname' => $member['firstname'],
                    'lastname' => $member['lastname'],
                    'nickname' => $member['nickname'],
                ],
                [
                    'profile:owner',
                    'user:owner',
                ],
                adminWildcard: false
            );
        }
    }

    /**
     * @return array<string, CrewRole>
     */
    private function seedRoles(CrewEvent $event): array
    {
        $roles = [];
        foreach (self::ROLES as $roleData) {
            $roles[$roleData['name']] = CrewRole::query()->updateOrCreate(
                [
                    'event_id' => $event->id,
                    'name' => $roleData['name'],
                ],
                [
                    'title' => $roleData['title'],
                    'description' => $roleData['description'],
                ]
            );
        }

        return $roles;
    }

    /**
     * @return array{infrastructure: Crew, network: Crew, info: Crew, security: Crew}
     */
    private function seedCrews(CrewEvent $event): array
    {
        $infrastructure = Crew::query()->updateOrCreate(
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
                'parent_crew' => null,
            ]
        );

        $network = Crew::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'name' => 'Network',
            ],
            [
                'hidden' => false,
                'can_apply' => true,
                'short_description' => 'Network team under Infrastructure',
                'long_description' => 'Child crew for nested membership tests.',
                'sorted_weight' => 2,
                'parent_crew' => $infrastructure->id,
            ]
        );

        $info = Crew::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'name' => 'Info',
            ],
            [
                'hidden' => false,
                'can_apply' => true,
                'short_description' => 'Info desk',
                'long_description' => 'Seeded info crew.',
                'sorted_weight' => 3,
                'parent_crew' => null,
            ]
        );

        $security = Crew::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'name' => 'Security',
            ],
            [
                'hidden' => false,
                'can_apply' => true,
                'short_description' => 'Security crew',
                'long_description' => 'Seeded security crew.',
                'sorted_weight' => 4,
                'parent_crew' => null,
            ]
        );

        return [
            'infrastructure' => $infrastructure,
            'network' => $network,
            'info' => $info,
            'security' => $security,
        ];
    }

    /**
     * @return array{core: CrewTeam, volunteers: CrewTeam}
     */
    private function seedTeams(Crew $infrastructure): array
    {
        return [
            'core' => CrewTeam::query()->updateOrCreate(
                [
                    'crew_id' => $infrastructure->id,
                    'name' => 'Core',
                ]
            ),
            'volunteers' => CrewTeam::query()->updateOrCreate(
                [
                    'crew_id' => $infrastructure->id,
                    'name' => 'Volunteers',
                ]
            ),
        ];
    }

    /**
     * @param  array<string, CrewRole>  $roles
     * @param  array{infrastructure: Crew, network: Crew, info: Crew, security: Crew}  $crews
     * @param  array{core: CrewTeam, volunteers: CrewTeam}  $teams
     */
    private function seedCrewMemberships(CrewEvent $event, array $roles, array $crews, array $teams): void
    {
        $assignments = [
            [
                'user_id' => self::ADMIN_ID,
                'crew' => $crews['infrastructure'],
                'role' => $roles['organizer'],
                'team_id' => $teams['core']->id,
                'custom_title' => 'Local stack organizer',
            ],
            [
                'user_id' => self::ADMIN_ID,
                'crew' => $crews['info'],
                'role' => $roles['organizer'],
                'team_id' => null,
                'custom_title' => null,
            ],
            [
                'user_id' => self::MEMBER_USERS[0]['id'],
                'crew' => $crews['infrastructure'],
                'role' => $roles['chief'],
                'team_id' => $teams['core']->id,
                'custom_title' => 'Infra chief',
            ],
            [
                'user_id' => self::MEMBER_USERS[1]['id'],
                'crew' => $crews['infrastructure'],
                'role' => $roles['shiftleader'],
                'team_id' => $teams['volunteers']->id,
                'custom_title' => null,
            ],
            [
                'user_id' => self::MEMBER_USERS[2]['id'],
                'crew' => $crews['network'],
                'role' => $roles['member'],
                'team_id' => null,
                'custom_title' => null,
            ],
            [
                'user_id' => self::MEMBER_USERS[3]['id'],
                'crew' => $crews['info'],
                'role' => $roles['chief'],
                'team_id' => null,
                'custom_title' => 'Info chief',
            ],
            [
                'user_id' => self::MEMBER_USERS[4]['id'],
                'crew' => $crews['security'],
                'role' => $roles['member'],
                'team_id' => null,
                'custom_title' => null,
            ],
        ];

        // Attach leftover factory users as plain members when DatabaseSeeder ran first.
        $extraUsers = User::query()
            ->whereNotIn('id', [
                self::ADMIN_ID,
                self::NORMAL_ID,
                self::APPLICANT_ID,
                ...array_column(self::MEMBER_USERS, 'id'),
            ])
            ->orderBy('id')
            ->limit(6)
            ->get();

        $memberCrewCycle = [
            $crews['infrastructure'],
            $crews['network'],
            $crews['info'],
            $crews['security'],
        ];

        foreach ($extraUsers as $index => $user) {
            $assignments[] = [
                'user_id' => $user->id,
                'crew' => $memberCrewCycle[$index % count($memberCrewCycle)],
                'role' => $roles['member'],
                'team_id' => $index % 2 === 0 ? $teams['volunteers']->id : null,
                'custom_title' => null,
            ];
        }

        foreach ($assignments as $assignment) {
            // Volunteers team only belongs to Infrastructure.
            $teamId = $assignment['team_id'];
            if ($teamId !== null && (int) $assignment['crew']->id !== (int) $crews['infrastructure']->id) {
                $teamId = null;
            }

            $this->assignCrewMember(
                (string) $assignment['user_id'],
                $assignment['crew'],
                $assignment['role'],
                $teamId,
                $assignment['custom_title']
            );
        }
    }

    private function assignCrewMember(
        string $userId,
        Crew $crew,
        CrewRole $role,
        ?int $teamId,
        ?string $customTitle
    ): void {
        CrewUser::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'crew_id' => $crew->id,
            ],
            [
                'role_id' => $role->id,
                'team_id' => $teamId,
                'custom_title' => $customTitle,
            ]
        );

        $crewAttribute = match ($role->name) {
            'chief', 'co-chief' => 'crew:manager',
            'organizer', 'leader' => 'crew:owner',
            default => 'crew:member',
        };

        $this->grantAttribute($userId, $crewAttribute, (string) $crew->id);
        $this->grantAttribute($userId, 'event:member', (string) $crew->event_id);

        if ($teamId !== null) {
            $teamAttribute = in_array($crewAttribute, ['crew:owner', 'crew:manager'], true)
                ? 'crew:team:owner'
                : 'crew:team:member';
            $this->grantAttribute($userId, $teamAttribute, (string) $teamId);
        }
    }

    /**
     * @param  array{infrastructure: Crew, network: Crew, info: Crew, security: Crew}  $crews
     * @param  array<string, mixed>  $emptyDoc
     */
    private function seedTasks(CrewEvent $event, array $crews, array $emptyDoc): void
    {
        $showup = Task::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'name' => 'Showup time',
            ],
            [
                'type' => 'forms',
                'enabled' => true,
                'private' => false,
                'short_description' => 'Set your personal showup time.',
                'long_description' => $emptyDoc,
                'icon' => 'schedule',
                'skippable' => false,
            ]
        );

        TaskQuestion::query()->updateOrCreate(
            [
                'task_id' => $showup->id,
                'name' => 'Arrival time',
            ],
            [
                'optional' => false,
                'hidden' => false,
                'description' => 'Ordinary arrival time is Wednesday 09:00.',
                'type' => 'textfield',
                'options' => null,
            ]
        );

        TaskQuestion::query()->updateOrCreate(
            [
                'task_id' => $showup->id,
                'name' => 'Comment',
            ],
            [
                'optional' => true,
                'hidden' => false,
                'description' => 'Justify arrivals outside the ordinary window.',
                'type' => 'textfield-multiline',
                'options' => null,
            ]
        );

        TaskRecipient::query()->updateOrCreate(
            [
                'task_id' => $showup->id,
                'recipient_type' => 'event',
                'recipient_id' => (string) $event->id,
            ],
            [
                'mandatory' => true,
                'approval_needed' => true,
                'filter' => [],
            ]
        );

        $allergies = Task::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'name' => 'Nutritional Needs',
            ],
            [
                'type' => 'forms',
                'enabled' => true,
                'private' => false,
                'short_description' => 'Do you have any allergies?',
                'long_description' => $emptyDoc,
                'icon' => 'Restaurant',
                'skippable' => true,
            ]
        );

        TaskQuestion::query()->updateOrCreate(
            [
                'task_id' => $allergies->id,
                'name' => 'Do you have any allergies?',
            ],
            [
                'optional' => true,
                'hidden' => false,
                'description' => 'Please select any allergies you have',
                'type' => 'checkbox-multi',
                'options' => ['Fish', 'Nuts', 'Eggs', 'Shellfish'],
            ]
        );

        TaskRecipient::query()->updateOrCreate(
            [
                'task_id' => $allergies->id,
                'recipient_type' => 'crew',
                'recipient_id' => (string) $crews['infrastructure']->id,
            ],
            [
                'mandatory' => false,
                'approval_needed' => false,
                'filter' => [],
            ]
        );

        $generic = Task::query()->updateOrCreate(
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
                'task_id' => $generic->id,
                'recipient_type' => 'event',
                'recipient_id' => (string) $event->id,
            ],
            [
                'mandatory' => true,
                'approval_needed' => false,
                'filter' => [],
            ]
        );

        foreach ([self::ADMIN_ID, self::MEMBER_USERS[0]['id'], self::MEMBER_USERS[1]['id']] as $userId) {
            TaskResponse::query()->updateOrCreate(
                [
                    'task_id' => $showup->id,
                    'user_id' => $userId,
                ],
                [
                    'value' => ['Arrival time' => 'Wednesday 09:00'],
                    'approved' => true,
                    'completed' => true,
                ]
            );
        }
    }

    private function seedCommunicationList(CrewEvent $event): void
    {
        $list = CommunicationList::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'identifier' => 'local-stack-'.$event->slug,
            ],
            [
                'type' => 'mail',
                'summary' => 'Seeded communication list',
            ]
        );

        CommunicationListOwner::query()->updateOrCreate(
            [
                'list_id' => $list->id,
                'owner_id' => self::ADMIN_ID,
                'owner_type' => 'user',
            ]
        );

        CommunicationListRecipient::query()->updateOrCreate(
            [
                'list_id' => $list->id,
                'recipient_id' => self::ADMIN_ID,
                'recipient_type' => 'user',
            ]
        );

        CommunicationListRecipient::query()->updateOrCreate(
            [
                'list_id' => $list->id,
                'recipient_id' => (string) $event->id,
                'recipient_type' => 'event',
            ]
        );
    }

    /**
     * @param  array{infrastructure: Crew, network: Crew, info: Crew, security: Crew}  $crews
     */
    private function seedApplications(CrewEvent $event, array $crews): void
    {
        $eventQuestions = [
            ['title' => 'Hvem er du?', 'description' => 'Kort intro'],
            ['title' => 'Hvorfor søker du?', 'description' => 'Motivasjon'],
            ['title' => 'Hvor tilgjengelig er du?', 'description' => 'Tidspunkt'],
        ];

        /** @var Collection<int, ApplicationQuestion> $questions */
        $questions = collect();
        foreach ($eventQuestions as $question) {
            $questions->push(ApplicationQuestion::query()->updateOrCreate(
                [
                    'event_id' => $event->id,
                    'crew_id' => null,
                    'title' => $question['title'],
                ],
                [
                    'type' => 'textfield',
                    'description' => $question['description'],
                    'required' => true,
                    'options' => null,
                ]
            ));
        }

        $crewQuestion = ApplicationQuestion::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'crew_id' => $crews['infrastructure']->id,
                'title' => 'Relevant experience?',
            ],
            [
                'type' => 'textfield',
                'description' => 'Tell us about prior crew work.',
                'required' => false,
                'options' => null,
            ]
        );
        $questions->push($crewQuestion);

        $applications = [
            [
                'user_id' => self::NORMAL_ID,
                'crew_id' => $crews['infrastructure']->id,
                'status' => 'reviewable',
                'message' => 'I would like to join Infrastructure.',
            ],
            [
                'user_id' => self::APPLICANT_ID,
                'crew_id' => $crews['info']->id,
                'status' => 'received',
                'message' => 'Fresh application for Info.',
            ],
            [
                'user_id' => self::APPLICANT_ID,
                'crew_id' => $crews['security']->id,
                'status' => 'waiting_list',
                'message' => 'Happy to help Security if needed.',
            ],
            [
                'user_id' => self::MEMBER_USERS[2]['id'],
                'crew_id' => $crews['infrastructure']->id,
                'status' => 'approved',
                'message' => 'Moved into Network after approval.',
            ],
        ];

        foreach ($applications as $applicationData) {
            $application = ApplicationRecord::query()->updateOrCreate(
                [
                    'user_id' => $applicationData['user_id'],
                    'crew_id' => $applicationData['crew_id'],
                ],
                [
                    'event_id' => $event->id,
                    'status' => $applicationData['status'],
                    'message' => $applicationData['message'],
                    'reviewable_at' => $applicationData['status'] === 'reviewable' ? now()->subHours(2) : null,
                ]
            );

            foreach ($questions as $question) {
                if ($question->crew_id !== null && (int) $question->crew_id !== (int) $application->crew_id) {
                    continue;
                }

                ApplicationAnswer::query()->updateOrCreate(
                    [
                        'application_id' => $application->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'user_id' => $application->user_id,
                        'event_id' => $event->id,
                        'content' => 'Seeded answer for '.$question->title,
                    ]
                );
            }

            ApplicationComment::query()->updateOrCreate(
                [
                    'application_id' => $application->id,
                    'user_id' => self::ADMIN_ID,
                ],
                [
                    'comment' => 'Seeded reviewer comment',
                ]
            );
        }
    }

    /**
     * @param  list<string>  $attributeKeys
     * @param  array{firstname: string, lastname: string, nickname: string}  $profile
     */
    private function seedUserWithAttributes(
        string $userId,
        string $username,
        array $profile,
        array $attributeKeys,
        bool $adminWildcard
    ): void {
        $user = User::query()->firstOrCreate(
            ['id' => $userId],
            [
                'username' => $username,
                'type' => 'user',
            ]
        );

        $user->userProfile?->update([
            'firstname' => $profile['firstname'],
            'lastname' => $profile['lastname'],
            'nickname' => $profile['nickname'],
            'email' => $user->username,
        ]);

        foreach ($attributeKeys as $key) {
            AccessAttributeType::query()->firstOrCreate(
                ['key' => $key],
                [
                    'source' => 'local-seed',
                    'summary' => $key,
                    'description' => 'Local stack seed',
                ]
            );

            $value = match (true) {
                $key === 'user:owner' => [$userId],
                $key === 'profile:owner' && ! $adminWildcard => ['*'],
                $adminWildcard => ['*'],
                default => ['*'],
            };

            AccessAttribute::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'key' => $key,
                    'source' => 'local-seed',
                ],
                [
                    'value' => $value,
                ]
            );
        }
    }

    private function grantAttribute(string $userId, string $key, string $value): void
    {
        AccessAttributeType::query()->firstOrCreate(
            ['key' => $key],
            [
                'source' => 'local-seed',
                'summary' => $key,
                'description' => 'Local stack seed',
            ]
        );

        $attribute = AccessAttribute::query()->firstOrNew([
            'user_id' => $userId,
            'key' => $key,
            'source' => 'local-seed-membership',
        ]);

        /** @var list<string|int> $existing */
        $existing = $attribute->value ?? [];
        if (! in_array($value, $existing, true) && ! in_array('*', $existing, true)) {
            $existing[] = $value;
        }

        $attribute->value = array_values(array_unique($existing));
        $attribute->save();
    }
}
