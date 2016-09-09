<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli;

use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Client\CurlClient;
use OAuth\Common\Service\ServiceInterface;
use OAuth\Common\Storage\Memory;
use OAuth\OAuth1\Token\StdOAuth1Token;
use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\ServiceFactory;

/**
 * OAuth Factory.
 */
class OAuthFactory {
    /**
     * OAuth Service Factory Instance.
     *
     * @var \OAuth\ServiceFactory
     */
    private $serviceFactory;

    /**
     * Class constructor.
     *
     * @return void
     */
    public function __construct() {
        $client = new CurlClient();
        $client->setCurlParameters([\CURLOPT_ENCODING => '']);
        $this->serviceFactory = new ServiceFactory();
        $this->serviceFactory->setHttpClient($client);
    }

    /**
     * Creates a new instance of an OAuth Service.
     *
     * @param string $serviceName
     * @param string $accessToken
     * @param string $tokenSecret
     * @param string $appKey
     * @param string $appSecret
     * @param string $apiVersion
     *
     * @return \OAuth\Common\Service\ServiceInterface
     */
    public function create(
        string $serviceName,
        string $accessToken,
        string $tokenSecret,
        string $appKey,
        string $appSecret,
        string $apiVersion
    ) : ServiceInterface {
        $client = $this->serviceFactory->createService(
            $serviceName,
            new Credentials(
                $appKey,
                $appSecret,
                ''
            ),
            new Memory(),
            [],
            null,
            $apiVersion
        );

        switch ($client::OAUTH_VERSION) {
            case 1:
                // OAuth 1.x Token
                $token = new StdOAuth1Token();
                $token->setAccessToken($accessToken);
                $token->setAccessTokenSecret($tokenSecret);
                break;
            case 2:
                // OAuth 2.x Token
                $token = new StdOAuth2Token();
                $token->setAccessToken($accessToken);
                break;
        }

        $client->getStorage()->storeAccessToken($client->service(), $token);

        return $client;
    }
}
