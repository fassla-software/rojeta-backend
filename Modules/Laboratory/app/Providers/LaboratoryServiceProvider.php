<?php

namespace Modules\Laboratory\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class LaboratoryServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Laboratory';
    protected string $nameLower = 'laboratory';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
