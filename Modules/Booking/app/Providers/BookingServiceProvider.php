<?php

namespace Modules\Booking\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class BookingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Booking';
    protected string $nameLower = 'booking';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
