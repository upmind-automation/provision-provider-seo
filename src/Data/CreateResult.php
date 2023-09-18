<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\Seo\Data;

use Upmind\ProvisionBase\Provider\DataSet\DataSet;
use Upmind\ProvisionBase\Provider\DataSet\ResultData;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * @property-read mixed $username Username or other unique service identifier
 * @property-read string $domain Domain name the account is for
 * @property-read string|null $package_identifier Service package identifier, if any
 */
class CreateResult extends ResultData
{
    public static function rules(): Rules
    {
        return new Rules([
            'username' => ['required'],
            'domain' => ['required', 'string'],
            'package_identifier' => ['nullable', 'string'],
        ]);
    }

    /**
     * Set the result username.
     */
    public function setUsername(string $username): self
    {
        $this->setValue('username', $username);
        return $this;
    }

    /**
     * Set the account domain name.
     */
    public function setDomain(?string $domain): self
    {
        $this->setValue('domain', $domain);
        return $this;
    }

    /**
     * Set the result package identifier.
     */
    public function setPackageIdentifier(?string $packageIdentifier): self
    {
        $this->setValue('package_identifier', $packageIdentifier);
        return $this;
    }
}
