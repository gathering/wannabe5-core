<?php

use App\Domain\Shared\Services\BackendCompatClient;
use App\Http\Controllers\AccessAttributeController;
use App\Http\Controllers\AccessAttributeTypeController;
use App\Http\Controllers\AccessUserController;
use App\Http\Controllers\ApplicationAnswerController;
use App\Http\Controllers\ApplicationCommentServiceController;
use App\Http\Controllers\ApplicationQuestionController;
use App\Http\Controllers\ApplicationServiceController;
use App\Http\Controllers\AuthServiceController;
use App\Http\Controllers\CommunicationListController;
use App\Http\Controllers\CommunicationListOwnerController;
use App\Http\Controllers\CommunicationListRecipientController;
use App\Http\Controllers\CommunicationMessageController;
use App\Http\Controllers\CommunicationNotificationController;
use App\Http\Controllers\CommunicationTemplateController;
use App\Http\Controllers\CrewEventController;
use App\Http\Controllers\CrewPageServiceController;
use App\Http\Controllers\CrewServiceController;
use App\Http\Controllers\CrewTeamServiceController;
use App\Http\Controllers\CrewUserServiceController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\InternalAccessController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PageVersionController;
use App\Http\Controllers\ProfileServiceController;
use App\Http\Controllers\TaskingServiceController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', [IndexController::class, 'index']);

// Public crew frontpage (logged-out welcome page in wannabe5-frontend).
Route::get('/crew/pages/frontpage', [CrewPageServiceController::class, 'publicFrontpage']);
Route::get('/crew/liveness', [InternalAccessController::class, 'liveness']);
// Public event list/show (frontend /home SSR; mirrors legacy viewAnyPublic).
Route::get('/crew/events', [CrewEventController::class, 'index']);
Route::get('/crew/events/{event_id}', [CrewEventController::class, 'show']);

Route::prefix('auth')->group(function () {
    Route::get('/login', [AuthServiceController::class, 'login']);
    Route::get('/register', [AuthServiceController::class, 'register']);
    Route::get('/callback', [AuthServiceController::class, 'callback']);
    Route::get('/after-logout', [AuthServiceController::class, 'afterLogout']);
    Route::match(['GET', 'POST'], '/refresh-session', [AuthServiceController::class, 'refreshSession']);
    Route::get('/health', [AuthServiceController::class, 'health']);
    Route::post('/services/login', [AuthServiceController::class, 'serviceLogin']);
    Route::get('/liveness', [AuthServiceController::class, 'liveness']);
});

// protected endpoints
Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/test', function () {
        return response()->json(Auth::user());
    });
    Route::get('/metrics', [MetricsController::class, 'index']);

    // Legacy profile microservice paths used by wannabe5-frontend (`service: "profile"`).
    // Registered before the core UserProfile resource so literal segments win over `{profile}`.
    $registerProfileMicroserviceRoutes = function (bool $includeRoot = true): void {
        if ($includeRoot) {
            Route::get('/', [ProfileServiceController::class, 'root']);
        }
        Route::get('/search', [ProfileServiceController::class, 'search']);
        Route::post('/profile', [ProfileServiceController::class, 'create']);
        Route::get('/profile/{id}', [ProfileServiceController::class, 'show']);
        Route::put('/profile/{id}', [ProfileServiceController::class, 'update']);
        Route::delete('/profile/{id}', [ProfileServiceController::class, 'delete']);
        Route::get('/profile/{id}/crew-history', [ProfileServiceController::class, 'showCrewHistory']);
        Route::get('/profile/{profileId}/profile-image', [ProfileServiceController::class, 'showImage']);
        Route::post('/profile/{profileId}/profile-image', [ProfileServiceController::class, 'createImage']);
        Route::put('/profile/{profileId}/profile-image', [ProfileServiceController::class, 'updateImage']);
        Route::delete('/profile/{profileId}/profile-image', [ProfileServiceController::class, 'deleteImage']);
        Route::get('/profile/{profileId}/profile-image/image', [ProfileServiceController::class, 'getImage']);
        Route::get('/profile/{profileId}/profile-image/image/{size}', [ProfileServiceController::class, 'getImage']);
        Route::post('/image/{profileId}/crop', [ProfileServiceController::class, 'crop']);
        Route::post('/profile/{profileId}/profile-image/approve', [ProfileServiceController::class, 'approveImage']);
        Route::post('/profile/{profileId}/profile-image/decline', [ProfileServiceController::class, 'declineImage']);
        Route::get('/image', [ProfileServiceController::class, 'indexImages']);
        Route::get('/image/{approval_state}', [ProfileServiceController::class, 'indexImages'])
            ->where('approval_state', 'new|approved|declined');
        Route::get('/updatefromauth', [ProfileServiceController::class, 'updateFromAuth']);
        Route::get('/_internal/access', fn () => app(InternalAccessController::class)->access('profile'));
        Route::get('/_internal/metrics', [InternalAccessController::class, 'metrics']);
        Route::get('/liveness', [ProfileServiceController::class, 'liveness']);
    };

    Route::prefix('profile')->group(fn () => $registerProfileMicroserviceRoutes(false));

    Route::apiResource('profile', UserProfileController::class)->only([
        'index',
        'show',
        'update',
    ])->where(['profile' => '[0-9]+|[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}']);

    Route::apiResource('page', PageController::class);

    // Explicit nested version routes avoid implicit model-binding mismatches.
    Route::get('/page/{page}/versions', [PageVersionController::class, 'index']);
    Route::get('/page/{page}/versions/{version_number}', [PageVersionController::class, 'show']);

    Route::prefix('access')->group(function () {
        Route::get('/', fn () => response()->json(['version' => app()->version()]));
        Route::prefix('users')->group(function () {
            Route::get('/current', [AccessUserController::class, 'currentUserAttributesCheck']);
            Route::get('/{user_id}', [AccessUserController::class, 'userAttributesCheck']);
            Route::get('/{user_id}/attributes', [AccessUserController::class, 'userAttributes']);
            Route::get('/current/has/{attribute}/{value}', [AccessUserController::class, 'currentUserHasAttribute']);
            Route::get('/{user_id}/has/{attribute}/{value}', [AccessUserController::class, 'hasAttribute']);
        });

        Route::get('/attributes', [AccessAttributeController::class, 'index']);
        Route::post('/attributes', [AccessAttributeController::class, 'create']);
        Route::get('/attributes/{attribute_id}', [AccessAttributeController::class, 'show']);
        Route::put('/attributes/{attribute_id}', [AccessAttributeController::class, 'update']);
        Route::delete('/attributes/{attribute_id}', [AccessAttributeController::class, 'destroy']);

        Route::get('/attribute-types', [AccessAttributeTypeController::class, 'index']);
        Route::post('/attribute-types', [AccessAttributeTypeController::class, 'create']);
        Route::get('/attribute-types/{attribute_type_id}', [AccessAttributeTypeController::class, 'show']);
        Route::put('/attribute-types/{attribute_type_id}', [AccessAttributeTypeController::class, 'update']);
        Route::delete('/attribute-types/{attribute_type_id}', [AccessAttributeTypeController::class, 'destroy']);
        Route::get('/attribute-types/{attribute_type_id}/attributes', [AccessAttributeTypeController::class, 'attributes']);
        Route::get('/_internal/access', fn () => app(InternalAccessController::class)->access('access'));
        Route::get('/liveness', [InternalAccessController::class, 'liveness']);
    });

    Route::prefix('auth')->group(function () {
        Route::get('/logout', [AuthServiceController::class, 'logout']);
        Route::get('/after-login', [AuthServiceController::class, 'afterLogin']);
        Route::get('/profile', [AuthServiceController::class, 'profile']);
    });

    Route::prefix('application')->group(function () {
        Route::get('/{crew}/applications', [ApplicationServiceController::class, 'index']);
        Route::get('/application/{application}', [ApplicationServiceController::class, 'show']);
        Route::get('/user/{user}/applications', [ApplicationServiceController::class, 'allByUserId']);
        Route::post('/', [ApplicationServiceController::class, 'store']);
        Route::put('/{application}', [ApplicationServiceController::class, 'update']);
        Route::delete('/{application}', [ApplicationServiceController::class, 'delete']);
        Route::put('/{applicationId}/{status}', [ApplicationServiceController::class, 'updateStatus'])
            ->where('status', 'received|reviewable|approved|waiting_list|declined');

        Route::get('/questions', [ApplicationQuestionController::class, 'index']);
        Route::post('/questions', [ApplicationQuestionController::class, 'store']);
        Route::get('/questions/{id}', [ApplicationQuestionController::class, 'show']);
        Route::put('/questions/{id}', [ApplicationQuestionController::class, 'update']);
        Route::delete('/questions/{id}', [ApplicationQuestionController::class, 'destroy']);

        Route::get('/answers', [ApplicationAnswerController::class, 'index']);
        Route::post('/answers', [ApplicationAnswerController::class, 'store']);

        Route::get('/{application_id}/comments', [ApplicationCommentServiceController::class, 'index']);
        Route::post('/{application_id}/comments', [ApplicationCommentServiceController::class, 'create']);
        Route::put('/{application_id}/comments/{comment_id}', [ApplicationCommentServiceController::class, 'update']);
        Route::delete('/{application_id}/comments/{comment_id}', [ApplicationCommentServiceController::class, 'delete']);

        Route::get('/answer/{question}', [ApplicationAnswerController::class, 'index']);
        Route::put('/answer/{answer}', [ApplicationAnswerController::class, 'update']);
        Route::delete('/answer/{answer}', [ApplicationAnswerController::class, 'delete']);
        Route::post('/answer/question/{question}', [ApplicationAnswerController::class, 'updateEventQuestion']);
        Route::get('/_internal/access', fn () => app(InternalAccessController::class)->access('application'));
        Route::get('/_internal/metrics', [InternalAccessController::class, 'metrics']);
        Route::get('/liveness', [InternalAccessController::class, 'liveness']);
    });

    Route::prefix('communication')->group(function () {
        Route::get('/messages', [CommunicationMessageController::class, 'index']);
        Route::post('/messages', [CommunicationMessageController::class, 'create']);
        Route::post('/messages/batch-send', [CommunicationMessageController::class, 'batchCreateAndSend']);
        Route::get('/messages/{id}/preview', [CommunicationMessageController::class, 'preview']);
        Route::get('/messages/{id}/retry', [CommunicationMessageController::class, 'retry']);
        Route::get('/messages/{id}/schema', [CommunicationMessageController::class, 'schema']);
        Route::get('/messages/{id}', [CommunicationMessageController::class, 'show'])->whereNumber('id');
        Route::get('/messages/{ids}', [CommunicationMessageController::class, 'showMultiple']);
        Route::put('/messages/{id}', [CommunicationMessageController::class, 'update'])->whereNumber('id');
        Route::delete('/messages/{id}', [CommunicationMessageController::class, 'destroy'])->whereNumber('id');

        Route::get('/templates', [CommunicationTemplateController::class, 'index']);
        Route::post('/templates', [CommunicationTemplateController::class, 'create']);
        Route::get('/templates/{id}/schema', [CommunicationTemplateController::class, 'schema']);
        Route::get('/templates/{id}/preview', [CommunicationTemplateController::class, 'preview']);
        Route::get('/templates/{id}', [CommunicationTemplateController::class, 'show']);
        Route::put('/templates/{id}', [CommunicationTemplateController::class, 'update']);
        Route::delete('/templates/{id}', [CommunicationTemplateController::class, 'destroy']);

        Route::get('/notifications', [CommunicationNotificationController::class, 'index']);
        Route::post('/notifications', [CommunicationNotificationController::class, 'create']);
        Route::get('/notifications/{id}/schema', [CommunicationNotificationController::class, 'schema']);
        Route::get('/notifications/{id}', [CommunicationNotificationController::class, 'show']);
        Route::put('/notifications/{id}', [CommunicationNotificationController::class, 'update']);
        Route::delete('/notifications/{id}', [CommunicationNotificationController::class, 'destroy']);

        Route::get('/lists', [CommunicationListController::class, 'index']);
        Route::post('/lists', [CommunicationListController::class, 'create']);
        Route::get('/lists/{list_id}/recipients', [CommunicationListController::class, 'showRecipients']);
        Route::get('/lists/{list_id}/owners', [CommunicationListController::class, 'showOwners']);
        Route::get('/lists/{list_id}', [CommunicationListController::class, 'show']);
        Route::put('/lists/{list_id}', [CommunicationListController::class, 'update']);
        Route::delete('/lists/{list_id}', [CommunicationListController::class, 'destroy']);

        Route::get('/list-owners', [CommunicationListOwnerController::class, 'index']);
        Route::post('/list-owners', [CommunicationListOwnerController::class, 'create']);
        Route::get('/list-owners/{list_owner_id}', [CommunicationListOwnerController::class, 'show']);
        Route::put('/list-owners/{list_owner_id}', [CommunicationListOwnerController::class, 'update']);
        Route::delete('/list-owners/{list_owner_id}', [CommunicationListOwnerController::class, 'destroy']);

        Route::get('/list-recipients', [CommunicationListRecipientController::class, 'index']);
        Route::post('/list-recipients', [CommunicationListRecipientController::class, 'create']);
        Route::get('/list-recipients/{list_recipient_id}', [CommunicationListRecipientController::class, 'show']);
        Route::put('/list-recipients/{list_recipient_id}', [CommunicationListRecipientController::class, 'update']);
        Route::delete('/list-recipients/{list_recipient_id}', [CommunicationListRecipientController::class, 'destroy']);

        Route::get('/_internal/access', fn () => app(InternalAccessController::class)->access('communication'));
        Route::get('/liveness', [InternalAccessController::class, 'liveness']);
    });

    Route::prefix('crew')->group(function () {
        // GET /crew/events and GET /crew/events/{event_id} are public (above).
        Route::post('/events', [CrewEventController::class, 'create']);
        Route::put('/events/{event_id}', [CrewEventController::class, 'update']);
        Route::delete('/events/{event_id}', [CrewEventController::class, 'delete']);

        Route::get('/events/{event_id}/crews', [CrewServiceController::class, 'index']);
        Route::post('/events/{event_id}/crews', [CrewServiceController::class, 'create']);
        Route::get('/events/{event_id}/crews/{crew_id}', [CrewServiceController::class, 'show']);
        Route::put('/events/{event_id}/crews/{crew_id}', [CrewServiceController::class, 'update']);
        Route::delete('/events/{event_id}/crews/{crew_id}', [CrewServiceController::class, 'delete']);

        Route::get('/events/{event_id}/users', [CrewUserServiceController::class, 'getUsersByEvent']);
        Route::get('/events/{event_id}/users/{user_id}', [CrewUserServiceController::class, 'showUserCrew']);
        Route::post('/events/{event_id}/crews/{crew_id}/users', [CrewUserServiceController::class, 'createCrewUser']);
        Route::get('/events/{event_id}/crews/{crew_id}/users/{user_id}', [CrewUserServiceController::class, 'showCrewUser']);
        Route::put('/events/{event_id}/crews/{crew_id}/users/{user_id}', [CrewUserServiceController::class, 'updateCrewUser']);
        Route::delete('/events/{event_id}/crews/{crew_id}/users/{user_id}', [CrewUserServiceController::class, 'deleteCrewUser']);
        Route::get('/events/{event_id}/crews/{crew_id}/chief', [CrewUserServiceController::class, 'showCrewChief']);
        Route::get('/events/{event_id}/crews/{crew_id}/organizer', [CrewUserServiceController::class, 'showCrewOrganizer']);

        Route::post('/events/{event_id}/crews/{crew_id}/teams', [CrewTeamServiceController::class, 'createCrewTeam']);
        Route::put('/events/{event_id}/crews/{crew_id}/teams/{team_id}', [CrewTeamServiceController::class, 'updateCrewTeam']);
        Route::delete('/events/{event_id}/crews/{crew_id}/teams/{team_id}', [CrewTeamServiceController::class, 'deleteCrewTeam']);

        Route::get('/events/{event_id}/pages', [CrewPageServiceController::class, 'index']);
        Route::get('/events/{event_id}/pages/{page_id}', [CrewPageServiceController::class, 'show'])->whereNumber('page_id');
        Route::put('/events/{event_id}/pages/{page_id}', [CrewPageServiceController::class, 'update'])->whereNumber('page_id');
        Route::get('/events/{event_id}/pages/{page_type}', [CrewPageServiceController::class, 'eventPage']);
        Route::get('/_internal/access', fn () => app(InternalAccessController::class)->access('crew'));
        Route::get('/_internal/metrics', [InternalAccessController::class, 'metrics']);
        // /crew/liveness and /crew/pages/frontpage are registered publicly above.
    });

    Route::prefix('tasking')->group(function () {
        Route::get('/events/{event_id}', [TaskingServiceController::class, 'index']);
        Route::get('/events/{event_id}/user/{user_id}', [TaskingServiceController::class, 'getTasksForUser']);
        Route::get('/events/{event_id}/user/{user_id}/task/{task}', [TaskingServiceController::class, 'showTaskForUser']);
        Route::post('/events/{event_id}/user/{user_id}/task/{task}', [TaskingServiceController::class, 'storeTaskResponse']);
        Route::post('/events/{event_id}/user/{user_id}/task/{task}/approve', [TaskingServiceController::class, 'approveTaskResponse']);
        Route::get('/events/{event_id}/task/{task}', [TaskingServiceController::class, 'getTask']);
        Route::get('/events/{event_id}/task/{task}/responses', [TaskingServiceController::class, 'getTaskResponses']);
        Route::get('/events/{event_id}/crew/{crew_id}', [TaskingServiceController::class, 'getTasksForCrew']);
        Route::get('/events/{event_id}/crew/{crew_id}/task/{task}', [TaskingServiceController::class, 'getTaskForCrew']);
        Route::get('/events/{event_id}/crew/{crew_id}/task/{task}/responses', [TaskingServiceController::class, 'getTaskResponsesForCrew']);
        Route::get('/_internal/access', fn () => app(InternalAccessController::class)->access('tasking'));
        Route::get('/liveness', [InternalAccessController::class, 'liveness']);
    });

    // Keep /api/profileservice/* as an explicit alias for native docs/tests.
    Route::prefix('profileservice')->group(fn () => $registerProfileMicroserviceRoutes(true));
});

// Backend microservice parity adapter endpoints declared per service route surface.
foreach (config('backend_compat_routes', []) as $service => $routes) {
    foreach ($routes as $routeDef) {
        $route = Route::match($routeDef['methods'], $routeDef['uri'], function (Request $request, BackendCompatClient $client) use ($service, $routeDef) {
            $path = $routeDef['upstream'];
            foreach ($request->route()?->parameters() ?? [] as $name => $value) {
                if (! is_scalar($value)) {
                    continue;
                }
                $path = str_replace('{'.$name.'}', (string) $value, $path);
            }

            return $client->forward($service, $path, $request);
        });

        foreach (($routeDef['where'] ?? []) as $parameter => $pattern) {
            $route->where($parameter, $pattern);
        }
    }
}
