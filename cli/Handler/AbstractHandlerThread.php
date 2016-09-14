<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Handler;

use idOS\Auth\CredentialToken;
use idOS\SDK;
use OAuth\Common\Service\ServiceInterface;

/**
 * Abstract Scraper Thread implementation.
 */
abstract class AbstractHandlerThread extends \Thread {
    /**
     * Public Key.
     *
     * @var string
     */
    protected $publicKey;
    /**
     * User name.
     *
     * @var string
     */
    protected $userName;
    /**
     * Source Id.
     *
     * @var int
     */
    protected $sourceId;
    /**
     * OAuth Service instance.
     *
     * @var \OAuth\Common\Service\ServiceInterface
     */
    protected $service;
    /**
     * Dry run flag.
     *
     * Controls if data is sent (dryRun=false) to idOS or not (dryRun=true).
     *
     * @var bool
     */
    protected $dryRun;
    /**
     * Failure flag.
     *
     * @var bool
     */
    protected $failed = false;
    /**
     * Last Thread error.
     *
     * @var string
     */
    protected $lastError = '';
    /**
     * Execution time.
     *
     * @var float
     */
    protected $execTime = 0.0;

    /**
     * Class constructor.
     *
     * @param string                                 $publicKey
     * @param string                                 $userName
     * @param int                                    $sourceId
     * @param \OAuth\Common\Service\ServiceInterface $service
     * @param bool                                   $dryRun
     *
     * @return void
     */
    public function __construct(
        string $publicKey,
        string $userName,
        int $sourceId,
        ServiceInterface $service,
        bool $dryRun = false
    ) {
        $this->publicKey = $publicKey;
        $this->userName  = $userName;
        $this->sourceId  = $sourceId;
        $this->service   = $service;
        $this->dryRun    = $dryRun;
        $this->auth      = new CredentialToken(
            $publicKey,
            __HNDKEY__,
            __HNDSEC__
        );
        $sdk = SDK::create($this->auth);
        $x = (string) $this->auth;
        $Raw = $sdk->Profile('x')->Source(0)->Raw->listAll();
    }

    /**
     * Returns Thread's failure flag.
     *
     * @return bool
     */
    public function failed() : bool {
        return $this->failed;
    }

    /**
     * Returns Thread's last error.
     *
     * @return string
     */
    public function getLastError() : string {
        return $this->lastError;
    }

    /**
     * Returns Thread's execution time.
     *
     * @return float
     */
    public function getExecTime() : float {
        return $this->execTime;
    }

    /**
     * Thread run method.
     *
     * @return void
     */
    public function run() {
        require_once __DIR__ . '/../../vendor/autoload.php';
        // $auth = new CredentialToken($this->publicKey, __HNDKEY__, __HNDSEC__);
        $sdk = SDK::create($this->auth);
        $startTime = microtime(true);
        if (! $this->execute($sdk)) {
            $this->failed = true;
        }

        $this->execTime = microtime(true) - $startTime;
    }

    /**
     * Main execution function.
     *
     * @param \idOS\SDK $sdk
     *
     * @return void
     */
    abstract public function execute(SDK $sdk);
}
