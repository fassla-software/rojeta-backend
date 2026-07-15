<?php

namespace Modules\Nursing\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class NursingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Nursing';
    protected string $nameLower = 'nursing';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
