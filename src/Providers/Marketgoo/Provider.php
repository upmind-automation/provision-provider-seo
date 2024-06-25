<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\Seo\Providers\Marketgoo;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Str;
use Upmind\ProvisionProviders\Seo\Category;
use Upmind\ProvisionBase\Provider\Contract\ProviderInterface;
use Upmind\ProvisionBase\Provider\DataSet\AboutData;
use Upmind\ProvisionProviders\Seo\Data\CreateParams;
use Upmind\ProvisionProviders\Seo\Data\CreateResult;
use Upmind\ProvisionProviders\Seo\Data\EmptyResult;
use Upmind\ProvisionProviders\Seo\Data\LoginResult;
use Upmind\ProvisionProviders\Seo\Data\AccountIdentifierParams;
use Upmind\ProvisionProviders\Seo\Data\ChangePackageParams;
use Upmind\ProvisionProviders\Seo\Providers\Marketgoo\Data\Configuration;
use Upmind\ProvisionProviders\Seo\Providers\Marketgoo\Exceptions\OperationFailed;
use Upmind\ProvisionProviders\Seo\Providers\Marketgoo\ResponseHandlers\CreateAccountResponseHandler;
use Upmind\ProvisionProviders\Seo\Providers\Marketgoo\ResponseHandlers\ResponseHandler;
use Upmind\ProvisionProviders\Seo\Providers\Marketgoo\ResponseHandlers\LoginResponseHandler;

class Provider extends Category implements ProviderInterface
{
    /**
     * @var Configuration
     */
    protected $configuration;

    public static function aboutProvider(): AboutData
    {
        return AboutData::create()
            ->setName('marketgoo')
            ->setDescription('Create, login to and delete marketgoo accounts')
            ->setLogoUrl('https://apps.marketgoo.com/assets/branding/marketgoo/logo-squared.png');
    }

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionProviders\Seo\Providers\Marketgoo\Exceptions\OperationFailed
     */
    public function create(CreateParams $params): CreateResult
    {
        $domainName = $params->domain;
        $productKey = $params->package_identifier;
        $email = $params->customer_email;
        $name = $params->customer_name ?: substr($email, 0, strrpos($email, '@'));
        $promoCode = is_array($params->promo_codes)
            ? head($params->promo_codes)
            : $params->promo_codes;

        $accountId = $this->createAccount($domainName, $productKey, $email, $name, $promoCode);

        return CreateResult::create()
            ->setUsername($accountId)
            ->setDomain($domainName)
            ->setPackageIdentifier($productKey)
            ->setMessage('Account created');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionProviders\Seo\Providers\Marketgoo\Exceptions\OperationFailed
     */
    public function login(AccountIdentifierParams $params): LoginResult
    {
        return LoginResult::create()->setUrl($this->getLoginUrl($params->username));
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionProviders\Seo\Providers\Marketgoo\Exceptions\OperationFailed
     */
    public function changePackage(ChangePackageParams $params): EmptyResult
    {
        try {
            $this->upgradeAccount($params->username, $params->package_identifier);
        } catch (OperationFailed $e) {
            if (Str::contains($e->getMessage(), 'product token')) {
                return EmptyResult::create()->setMessage('Invalid product token');
            }

            throw $e;
        }

        return EmptyResult::create()->setMessage('Account updated');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionProviders\Seo\Providers\Marketgoo\Exceptions\OperationFailed
     */
    public function suspend(AccountIdentifierParams $params): EmptyResult
    {
        try {
            $this->suspendAccount($params->username);
        } catch (OperationFailed $e) {
            if (Str::contains($e->getMessage(), 'already suspended')) {
                return EmptyResult::create()->setMessage('Account already suspended');
            }

            throw $e;
        }

        return EmptyResult::create()->setMessage('Account suspended');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionProviders\Seo\Providers\Marketgoo\Exceptions\OperationFailed
     */
    public function unsuspend(AccountIdentifierParams $params): EmptyResult
    {
        try {
            $this->resumeAccount($params->username);
        } catch (OperationFailed $e) {
            if (Str::contains($e->getMessage(), 'not suspended')) {
                return EmptyResult::create()->setMessage('Account already unsuspended');
            }

            throw $e;
        }

        return EmptyResult::create()->setMessage('Account unsuspended');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionProviders\Seo\Providers\Marketgoo\Exceptions\OperationFailed
     */
    public function terminate(AccountIdentifierParams $params): EmptyResult
    {
        $this->deleteAccount($params->username);
        return EmptyResult::create()->setMessage('Account deleted');
    }

    protected function client(): Client
    {
        $apiUrl = Str::start($this->configuration->api_url, 'https://');

        return new Client([
            'base_uri' => rtrim($apiUrl, '/') . '/api/',
            'handler' => $this->getGuzzleHandlerStack(),
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::HEADERS => [
                'Accept' => 'application/vnd.marketgoo.api+json',
                'Content-Type' => 'application/vnd.marketgoo.api+json;charset=utf-8',
                'X-Auth-Token' => $this->configuration->api_key,
            ]
        ]);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionProviders\Seo\Providers\Marketgoo\Exceptions\OperationFailed
     */
    protected function getLoginUrl(string $username): string
    {
        // get 5-minute ttl sso link
        $response = $this->client()->get(sprintf('accounts/%s/login?expires=%s', $username, 5));
        $handler = new LoginResponseHandler($response);
        return $handler->getLoginUrl();
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionProviders\Seo\Providers\Marketgoo\Exceptions\OperationFailed
     */
    private function createAccount(
        string $domainName,
        string $productKey,
        string $email,
        string $name,
        ?string $promoCode = null
    ): string {
        $response = $this->client()->post('accounts', [
            RequestOptions::FORM_PARAMS => [
                'data' => [
                    'type' => 'account',
                    'attributes' => [
                        'domain' => $domainName,
                        'product' => $productKey,
                        'name' => $name,
                        'email' => $email,
                        'promo' => $promoCode,
                    ],
                ],
            ],
        ]);

        $handler = new CreateAccountResponseHandler($response);
        return $handler->getAccountIdentifier('create');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionProviders\Seo\Providers\Marketgoo\Exceptions\OperationFailed
     */
    private function upgradeAccount(string $accountId, string $productKey): void
    {
        $response = $this->client()->patch("accounts/{$accountId}/upgrade", [
            RequestOptions::FORM_PARAMS => [
                'data' => [
                    'type' => 'account',
                    'id' => $accountId,
                    'attributes' => [
                        'product' => $productKey,
                        'force' => true,
                    ],
                ],
            ],
        ]);
        $handler = new ResponseHandler($response);
        $handler->assertSuccess('upgrade');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionProviders\Seo\Providers\Marketgoo\Exceptions\OperationFailed
     */
    private function suspendAccount(string $accountId): void
    {
        $response = $this->client()->patch("accounts/{$accountId}/suspend");
        $handler = new ResponseHandler($response);
        $handler->assertSuccess('suspend');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionProviders\Seo\Providers\Marketgoo\Exceptions\OperationFailed
     */
    private function resumeAccount(string $accountId): void
    {
        $response = $this->client()->patch("accounts/{$accountId}/resume");
        $handler = new ResponseHandler($response);
        $handler->assertSuccess('resume');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionProviders\Seo\Providers\Marketgoo\Exceptions\OperationFailed
     */
    private function deleteAccount(string $accountId): void
    {
        $response = $this->client()->delete("accounts/{$accountId}");
        $handler = new ResponseHandler($response);
        $handler->assertSuccess('delete');
    }
}
