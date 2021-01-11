<?php

namespace App\Providers;

use App\Events\LoginEvent;
use App\Listeners\LoginListener;
use App\Listeners\QueryListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Database\Events\QueryExecuted;
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
        /*Registered::class => [
            SendEmailVerificationNotification::class,
        ],*/

        // 测试事件和监听器
        LoginEvent::class => [
            LoginListener::class
        ],

        // SQL 语句日志记录
        QueryExecuted::class => [
            QueryListener::class
        ]
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
