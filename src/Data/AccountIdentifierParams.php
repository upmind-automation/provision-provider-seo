<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\Seo\Data;

use Upmind\ProvisionBase\Provider\DataSet\DataSet;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * @property-read mixed $username Username or other unique service identifier
 * @property-read string $domain Domain name the account is for
 * @property-read array|null $extra Extra data, if any
 */
class AccountIdentifierParams extends DataSet
{
    public static function rules(): Rules
    {
        return new Rules([
            'username' => ['required'],
            'domain' => ['required', 'string'],
            'extra' => ['nullable', 'array'],
        ]);
    }
}
