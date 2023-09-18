<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\Seo;

use Upmind\ProvisionBase\Provider\BaseCategory;
use Upmind\ProvisionBase\Provider\DataSet\AboutData;
use Upmind\ProvisionProviders\Seo\Data\AccountIdentifierParams;
use Upmind\ProvisionProviders\Seo\Data\CreateParams;
use Upmind\ProvisionProviders\Seo\Data\CreateResult;
use Upmind\ProvisionProviders\Seo\Data\EmptyResult;
use Upmind\ProvisionProviders\Seo\Data\LoginResult;

/**
 * This provision category contains functions to facilitate basic online service
 * account creation/management including an automatic login feature.
 */
abstract class Category extends BaseCategory
{
    public static function aboutCategory(): AboutData
    {
        return AboutData::create()
            ->setName('SEO')
            ->setDescription('Provisioning category + providers for creating and logging into SEO services')
            ->setIcon('chart-line');
    }

    /**
     * Creates an account and returns the `username` which can be used to
     * identify the account in subsequent requests, plus other account
     * information.
     */
    abstract public function create(CreateParams $params): CreateResult;

    /**
     * Obtain a signed login URL for the service that the system client can redirect to.
     */
    abstract public function login(AccountIdentifierParams $params): LoginResult;

    /**
     * Suspend an account.
     */
    abstract public function suspend(AccountIdentifierParams $params): EmptyResult;

    /**
     * Unsuspend an account.
     */
    abstract public function unsuspend(AccountIdentifierParams $params): EmptyResult;

    /**
     * Permanently delete an account.
     */
    abstract public function terminate(AccountIdentifierParams $params): EmptyResult;
}
