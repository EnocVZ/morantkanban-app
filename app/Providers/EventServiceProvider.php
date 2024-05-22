<?php

namespace App\Providers;

use App\Events\AssignedUser;
use App\Events\AssignedUserToTask;
use App\Events\CommentAdded;
use App\Events\ForgotPassword;
use App\Events\SendMail;
use App\Events\TaskUpdated;
use App\Events\UserAddedToWorkspace;
use App\Events\UserCreated;
use App\Listeners\CommentAddedNotification;
use App\Listeners\SendAssignedUserNotification;
use App\Listeners\SendForgotPasswordNotification;
use App\Listeners\SendMailNotification;
use App\Listeners\TaskAssignUserNotification;
use App\Listeners\TaskUpdateNotification;
use App\Listeners\UserCreatedNotification;
use App\Listeners\WorkspaceUserAddNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        'App\Events\NewChatMessage' => [
            'App\Listeners\SendChatMessageNotification'
        ],
        UserCreated::class => [
            UserCreatedNotification::class
        ],
        CommentAdded::class => [
            CommentAddedNotification::class
        ],
        TaskUpdated::class => [
            TaskUpdateNotification::class
        ],
        UserAddedToWorkspace::class => [
            WorkspaceUserAddNotification::class
        ],
        AssignedUserToTask::class => [
            TaskAssignUserNotification::class
        ],
        ForgotPassword::class => [
            SendForgotPasswordNotification::class
        ],
        SendMail::class => [
            SendMailNotification::class
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
