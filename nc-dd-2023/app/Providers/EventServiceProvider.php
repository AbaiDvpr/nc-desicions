<?php declare(strict_types=1);

namespace App\Providers;

use App\Listeners\Cabinet\CentrifugoNotifier;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * Провайдер слушателей
 */
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
        NotificationSent::class => [
            // Слушатель новых уведомлений для отправки пользователям в centrifugo
            CentrifugoNotifier::class
        ],
    ];

    /**
     * @inheritdoc
     *
     * @var string[]
     */
    protected $observers = [
        \App\Models\Accounting::class => \App\Observers\Frontend\Cabinet\Company\AccountingObserver::class
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
        if (false === isFrontend()) {
            \App\Models\User::observe(\App\Observers\Backend\UserObserver::class);
        }
    }
}
