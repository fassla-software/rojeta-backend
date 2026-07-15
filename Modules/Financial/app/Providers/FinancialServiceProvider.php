<?php

namespace Modules\Financial\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class FinancialServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Financial';
    protected string $nameLower = 'financial';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
