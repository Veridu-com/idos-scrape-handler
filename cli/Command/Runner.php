<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Command;

use Cli\HandlerFactory;
use Cli\OAuthFactory;
use Cli\Utils\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command definition for Scraper Runner.
 */
class Runner extends Command {
    /**
     * Command configuration.
     *
     * @return void
     */
    protected function configure() {
        $this
            ->setName('scrape:runner')
            ->setDescription('idOS Scrape - Runner')
            ->addArgument(
                'providerName',
                InputArgument::REQUIRED,
                'Provider name'
            )
            ->addArgument(
                'userName',
                InputArgument::REQUIRED,
                'User name'
            )
            ->addArgument(
                'sourceId',
                InputArgument::REQUIRED,
                'Source Id'
            )
            ->addArgument(
                'publicKey',
                InputArgument::REQUIRED,
                'Public Key'
            )
            ->addArgument(
                'accessToken',
                InputArgument::REQUIRED,
                'Access Token (oAuth v1.x and v2.x)'
            )
            ->addArgument(
                'tokenSecret',
                InputArgument::OPTIONAL,
                'Token Secret (oAuth v1.x only)'
            )
            ->addArgument(
                'appKey',
                InputArgument::OPTIONAL,
                'Application Key'
            )
            ->addArgument(
                'appSecret',
                InputArgument::OPTIONAL,
                'Application Secret'
            )
            ->addArgument(
                'apiVersion',
                InputArgument::OPTIONAL,
                'API Version'
            )
            ->addArgument(
                'dryRun',
                InputArgument::OPTIONAL,
                'On dry run mode, no data is sent to idOS API'
            );
    }

    /**
     * Command execution.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $outpput
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $logger = new Logger();

        $logger->debug('Initializing idOS Scrape Handler Runner');

        $factory = new HandlerFactory(
            new OAuthFactory(),
            [
                'Linkedin' => 'Cli\\Handler\\LinkedIn',
                'Paypal'   => 'Cli\\Handler\\PayPal'
            ]
        );
        $provider = $factory->create(
            $logger,
            $input->getArgument('providerName'),
            $input->getArgument('accessToken'),
            $input->getArgument('tokenSecret') ?: '',
            $input->getArgument('appKey') ?: '',
            $input->getArgument('appSecret') ?: '',
            $input->getArgument('apiVersion') ?: ''
        );

        // idOS SDK
        $auth = new \idOS\Auth\CredentialToken(
            $input->getArgument('publicKey'),
            __HNDKEY__,
            __HNDSEC__
        );
        $sdk = \idOS\SDK::create($auth);

        $provider->handle(
            $sdk,
            $input->getArgument('userName'),
            $input->getArgument('sourceId'),
            $input->getArgument('dryRun') ?: false
        );

        $logger->debug('Runner completed');
    }
}
