<?php

namespace SalesForce\MarketingCloud\Api\Client;

use SalesForce\MarketingCloud\Authorization\Client\GenericClient;
use SalesForce\MarketingCloud\Env;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class ConfigBuilder
 *
 * @package SalesForce\MarketingCloud\Api\Client
 *
 * @method self setAccountId(string $accountId)
 * @method self setClientId(string $clientId)
 * @method self setClientSecret(string $clientSecret)
 */
class ConfigBuilder
{
    /**
     * Stores the container builder required to create the APIs
     *
     * @var ContainerBuilder
     */
    private $container;

    /**
     * Map of parameters
     *
     * @var array
     */
    private static $parameterMap = [
        'accountId' => GenericClient::OPT_ACCOUNT_ID,
        'clientId' => GenericClient::OPT_CLIENT_ID,
        'clientSecret' => GenericClient::OPT_CLIENT_SECRET,
        'authBaseUrl' => GenericClient::OPT_AUTH_URL,
        'resourceOwnerDetailsUrl' => GenericClient::OPT_RESOURCE_OWNER_DETAILS
    ];

    /**
     * Creates the authorization URL
     *
     * @param string $baseUrl
     * @return string
     */
    private static function createAuthUrl(string $baseUrl): string
    {
        return rtrim($baseUrl, '/') . GenericClient::PATH_AUTH;
    }

    /**
     * Creates the access token URL
     *
     * @param string $baseUrl
     * @return string
     */
    private static function createAccessTokenUrl(string $baseUrl): string
    {
        return rtrim($baseUrl, '/') . GenericClient::PATH_TOKEN;
    }

    /**
     * ConfigBuilder constructor.
     *
     * @param ContainerBuilder $container
     * @param bool $useDefaults
     */
    public function __construct(ContainerBuilder $container = null, bool $useDefaults = false)
    {
        $this->container = $container ?? new ContainerBuilder();

        // Sets some defaults
        if (!$this->container->hasParameter("auth.client.options")) {
            $this->container->setParameter("auth.client.options", [
                GenericClient::OPT_RESOURCE_OWNER_DETAILS => ''
            ]);
        }

        if ($useDefaults) {
            $this->setFromEnv();
        }
    }

    /**
     * Sets the configuration in the container using the default config location (ENV)
     *
     * @return $this
     */
    public function setFromEnv(): self
    {
        // Set default config
        $options = [
            GenericClient::OPT_ACCOUNT_ID => getenv(Env::ACCOUNT_ID),
            GenericClient::OPT_CLIENT_ID => getenv(Env::CLIENT_ID),
            GenericClient::OPT_CLIENT_SECRET => getenv(Env::CLIENT_SECRET),
            GenericClient::OPT_AUTH_URL => static::createAuthUrl(getenv(Env::AUTHORIZATION_BASE_URL)),
            GenericClient::OPT_ACCESS_TOKEN_URL => static::createAccessTokenUrl(getenv(Env::AUTHORIZATION_BASE_URL))
        ];

        foreach ($options as $name => $value) {
            $this->{"set" . ucfirst($name)}($value);
        }

        return $this;
    }

    /**
     * Sets the authorization base URL
     *
     * @param string $authBaseUrl
     * @return $this
     */
    public function setAuthBaseUrl(string $authBaseUrl): self
    {
        return $this->__call(GenericClient::OPT_AUTH_URL, [static::createAuthUrl($authBaseUrl)]);
    }

    /**
     * Sets the access token URL USING the authentication base URL
     *
     * @param string $authBaseUrl
     * @return $this
     */
    public function setAccessTokenUrl(string $authBaseUrl): self
    {
        return $this->__call(GenericClient::OPT_ACCESS_TOKEN_URL, [static::createAccessTokenUrl($authBaseUrl)]);
    }

    /**
     * Used to set the config
     *
     * @param string $name
     * @param array $arguments
     * @return self
     */
    public function __call(string $name, array $arguments): self
    {
        $paramName = lcfirst(ltrim($name, "set"));

        // Convert the parameter name
        if (isset(self::$parameterMap[$paramName])) {
            $paramName = self::$parameterMap[$paramName];
        }

        // Update the option only if it was set by default
        $options = $this->container->getParameter("auth.client.options");
        $options[$paramName] = trim(strval($arguments[0]));

        // Update the options
        $this->container->setParameter("auth.client.options", $options);

        return $this;
    }
}