<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Utils;

use GuzzleHttp\Client;
use idOS\Auth\AuthInterface;
use idOS\SDK;
use OAuth\Common\Service\ServiceInterface;

/**
 * Handles Thread's context.
 */
class Context extends \Worker {
    /**
     * Thread-safe Logger instance.
     *
     * @var Cli\Utils\Logger
     */
    private $logger;
    /**
     * idOS Authentication Token instance.
     *
     * @var \idOS\Auth\AuthInterface
     */
    private $authToken;
    /**
     * OAuth Service instance.
     *
     * @var \OAuth\Common\Service\ServiceInterface
     */
    private $service;
    /**
     * User name.
     *
     * @var string
     */
    private $userName;
    /**
     * Source Id.
     *
     * @var int
     */
    private $sourceId;
    /**
     * Developer mode flag.
     *
     * @var bool
     */
    private $devMode;
    /**
     * Dry run mode flag.
     *
     * If $dryRun is true, no data is sent do idOS API.
     *
     * @var bool
     */
    private $dryRun;
    /**
     * Path to write data output to.
     *
     * @var string|null
     */
    private $outputPath;

    protected static $sdk;

    /**
     * Class constructor.
     *
     * @param Cli\Utils\Logger                       $logger
     * @param \idOS\Auth\AuthInterface               $authToken
     * @param \OAuth\Common\Service\ServiceInterface $service
     * @param string                                 $userName
     * @param int                                    $sourceId
     * @param bool                                   $devMode
     * @param bool                                   $dryRun
     * @param string|null                            $outputPath
     *
     * @return void
     */
    public function __construct(
        Logger $logger,
        AuthInterface $authToken,
        ServiceInterface $service,
        string $userName,
        int $sourceId,
        bool $devMode = false,
        bool $dryRun = false,
        string $outputPath = null
    ) {
        $this->logger     = $logger;
        $this->authToken  = $authToken;
        $this->service    = $service;
        $this->userName   = $userName;
        $this->sourceId   = $sourceId;
        $this->devMode    = $devMode;
        $this->dryRun     = $dryRun;
        $this->outputPath = rtrim($outputPath, \DIRECTORY_SEPARATOR);

    }

    /**
     * Worker's start function.
     *
     * Required for autoloading to work.
     *
     * @param int $options
     *
     * @return bool
     */
    public function start(int $options = null) : bool {
        return parent::start(\PTHREADS_INHERIT_NONE);
    }

    /**
     * Worker's run function.
     *
     * Ensures autoloading works on thread's context.
     *
     * @return void
     */
    public function run() {
        require_once __DIR__ . '/../../vendor/autoload.php';
    }

    /**
     * Returns the Logger instance.
     *
     * @return Cli\Utils\Logger
     */
    public function getLogger() : Logger {
        return $this->logger;
    }

    /**
     * Returns the idOS SDK instance.
     *
     * @return \idOS\SDK
     */
    public function getSdk() : SDK {
        if (! self::$sdk) {
            self::$sdk = SDK::create($this->authToken, true);

            // development mode: disable ssl check
            if ($this->devMode) {
                self::$sdk
                    ->setBaseUrl(getenv('IDOS_API_URL') ?: 'https://api.idos.io/1.0/')
                    ->setClient(
                        new Client(
                            [
                                'verify'   => false
                            ]
                        )
                    );
            }
        }

        return self::$sdk;
    }

    /**
     * Returns the oAuth Service instance.
     *
     * @return mixed
     */
    public function getService() {
        return $this->service;
    }

    /**
     * Returns the User Name.
     *
     * @return string
     */
    public function getUserName() : string {
        return $this->userName;
    }

    /**
     * Returns the Source Id.
     *
     * @return int
     */
    public function getSourceId() : int {
        return $this->sourceId;
    }

    /**
     * Returns if is running in dry run mode.
     *
     * @return bool
     */
    public function isDryRun() : bool {
        return $this->dryRun;
    }

    /**
     * Writes data to output path.
     *
     * @param array  $data
     * @param string $className
     *
     * @return void
     */
    public function writeData(array $data, string $className) {
        if ($this->outputPath === null) {
            return;
        }

        $fileName = ltrim(str_replace('\\', '-', strtolower($className)), \DIRECTORY_SEPARATOR);
        $filePath = sprintf('%s%s%s.json', $this->outputPath, \DIRECTORY_SEPARATOR, $fileName);
        file_put_contents($filePath, json_encode($data), \LOCK_EX);
    }
}
