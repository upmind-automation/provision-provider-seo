<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\Seo;

use Upmind\ProvisionBase\Laravel\ProvisionServiceProvider;
use Upmind\ProvisionProviders\Seo\Providers\Example\Provider as ExampleProvider;
use Upmind\ProvisionProviders\Seo\Providers\Marketgoo\Provider as MarketgooProvider;

class LaravelServiceProvider extends ProvisionServiceProvider
{
    public function boot()
    {
        $this->bindCategory('seo', Category::class);

        // $this->bindProvider('seo', 'example', ExampleProvider::class);

        $this->bindProvider('seo', 'marketgoo', MarketgooProvider::class);
    }
}
