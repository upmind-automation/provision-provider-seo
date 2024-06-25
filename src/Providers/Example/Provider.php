<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\Seo\Providers\Example;

use GuzzleHttp\Client;
use Upmind\ProvisionBase\Provider\Contract\ProviderInterface;
use Upmind\ProvisionBase\Provider\DataSet\AboutData;
use Upmind\ProvisionProviders\Seo\Category;
use Upmind\ProvisionProviders\Seo\Data\AccountIdentifierParams;
use Upmind\ProvisionProviders\Seo\Data\ChangePackageParams;
use Upmind\ProvisionProviders\Seo\Data\CreateParams;
use Upmind\ProvisionProviders\Seo\Data\CreateResult;
use Upmind\ProvisionProviders\Seo\Data\EmptyResult;
use Upmind\ProvisionProviders\Seo\Data\LoginResult;
use Upmind\ProvisionProviders\Seo\Providers\Example\Data\Configuration;

/**
 * Empty provider for demonstration purposes.
 */
class Provider extends Category implements ProviderInterface
{
    protected Configuration $configuration;
    protected Client|null $client = null;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @inheritDoc
     */
    public static function aboutProvider(): AboutData
    {
        return AboutData::create()
            ->setName('Example Provider')
            // ->setLogoUrl('https://example.com/logo.png')
            ->setDescription('Empty provider for demonstration purposes');
    }

    /**
     * @inheritDoc
     *
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     */
    public function create(CreateParams $params): CreateResult
    {
        $this->errorResult('Not Implemented');
    }

    /**
     * @inheritDoc
     *
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     */
    public function changePackage(ChangePackageParams $params): EmptyResult
    {
        $this->errorResult('Not Implemented');
    }

    /**
     * @inheritDoc
     */
    public function login(AccountIdentifierParams $params): LoginResult
    {
        // $this->apiCall();

        return LoginResult::create()
            ->setMessage('Login URL generated')
            ->setUrl('https://example.com/login/foo/?auth=xxxxxx');
    }

    /**
     * @inheritDoc
     */
    public function suspend(AccountIdentifierParams $params): EmptyResult
    {
        return EmptyResult::create()
            ->setMessage('Account suspended');
    }

    /**
     * @inheritDoc
     */
    public function unsuspend(AccountIdentifierParams $params): EmptyResult
    {
        return EmptyResult::create()
            ->setMessage('Account unsuspended');
    }

    /**
     * @inheritDoc
     */
    public function terminate(AccountIdentifierParams $params): EmptyResult
    {
        return EmptyResult::create()
            ->setMessage('Account terminated');
    }

    /**
     * Get a Guzzle HTTP client instance.
     */
    protected function client(): Client
    {
        return $this->client ??= new Client([
            'handler' => $this->getGuzzleHandlerStack(),
            'base_uri' => 'https://example.com/api/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->configuration->api_token,
            ],
        ]);
    }
}
