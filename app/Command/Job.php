<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace App\Command;

/**
 * Job Command.
 */
class Job extends AbstractCommand {
    /**
     * Username.
     *
     * @var string
     */
    public $userName;
    /**
     * Source Id.
     *
     * @var int
     */
    public $sourceId;
    /**
     * Credential's Public Key.
     *
     * @var string
     */
    public $pubKey;
    /**
     * Provider name.
     *
     * @var string
     */
    public $providerName;
    /**
     * Provider Access Token.
     *
     * @var string
     */
    public $accessToken;
    /**
     * Provider Token Secret.
     *
     * @var string
     */
    public $tokenSecret;
    /**
     * Application Key.
     *
     * @var string
     */
    public $appKey;
    /**
     * Application Secret.
     *
     * @var string
     */
    public $appSecret;
    /**
     * API Version.
     *
     * @var string
     */
    public $apiVersion;

    /**
     * {@inheritdoc}
     */
    public function setParameters(array $parameters) : self {
        if (isset($parameters['userName'])) {
            $this->userName = $parameters['userName'];
        }

        if (isset($parameters['sourceId'])) {
            $this->sourceId = $parameters['sourceId'];
        }

        if (isset($parameters['pubKey'])) {
            $this->pubKey = $parameters['pubKey'];
        }

        if (isset($parameters['providerName'])) {
            $this->providerName = $parameters['providerName'];
        }

        if (isset($parameters['accessToken'])) {
            $this->accessToken = $parameters['accessToken'];
        }

        if (isset($parameters['tokenSecret'])) {
            $this->tokenSecret = $parameters['tokenSecret'];
        }

        if (isset($parameters['appKey'])) {
            $this->appKey = $parameters['appKey'];
        }

        if (isset($parameters['appSecret'])) {
            $this->appSecret = $parameters['appSecret'];
        }

        if (isset($parameters['apiVersion'])) {
            $this->apiVersion = $parameters['apiVersion'];
        }

        return $this;
    }
}
