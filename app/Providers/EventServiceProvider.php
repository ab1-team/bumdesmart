<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        'cross-app-bus.event.invoice.paid' => [
            [\App\Listeners\HandleBusEvent::class, 'handleInvoicePaid'],
        ],
        'cross-app-bus.event.invoice.overdue' => [
            [\App\Listeners\HandleBusEvent::class, 'handleInvoiceOverdue'],
        ],
        'cross-app-bus.event.tenant.suspend_requested' => [
            [\App\Listeners\HandleBusEvent::class, 'handleTenantSuspendRequested'],
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
