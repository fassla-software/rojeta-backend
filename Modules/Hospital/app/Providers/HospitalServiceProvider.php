<?php

namespace Modules\Hospital\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class HospitalServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Hospital';
    protected string $nameLower = 'hospital';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
