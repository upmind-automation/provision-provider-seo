<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\Seo\Providers\Marketgoo\Data;

use Upmind\ProvisionBase\Provider\DataSet\DataSet;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * @property-read string $api_url URL of the marketgoo API
 * @property-read string $api_key marketgoo API KEY for provision API
 */
class Configuration extends DataSet
{
    public static function rules(): Rules
    {
        return new Rules([
            'api_url' => ['required', 'string'],
            'api_key' => ['required', 'string'],
        ]);
    }
}
