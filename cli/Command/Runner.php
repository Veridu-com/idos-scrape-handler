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
use Monolog\Handler\StreamHandler;
use Monolog\Logger as Monolog;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\UidProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption(
                'devMode',
                'd',
                InputOption::VALUE_NONE,
                'Development mode'
            )
            ->addOption(
                'logFile',
                'l',
                InputOption::VALUE_REQUIRED,
                'Path to log file'
            )
            ->addArgument(
                'handlerPublicKey',
                InputArgument::REQUIRED,
                'Handler public key'
            )
            ->addArgument(
                'handlerPrivateKey',
                InputArgument::REQUIRED,
                'Handler private key'
            )
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
        $logFile = $input->getOption('logFile') ?? 'php://stdout';
        $monolog = new Monolog('Scrape');
        $monolog
            ->pushProcessor(new UidProcessor())
            ->pushProcessor(new ProcessIdProcessor())
            ->pushHandler(new StreamHandler($logFile, Monolog::DEBUG));
        $logger = new Logger($monolog);

        $logger->debug('Initializing idOS Scrape Handler Runner');

        // Development mode
        $devMode = ! empty($input->getOption('devMode'));
        if ($devMode) {
            $logger->debug(
                'Running in developer mode',
                [
                    'api_url' => getenv('IDOS_API_URL') ?: 'https://api.idos.io/1.0/'
                ]
            );
            ini_set('display_errors', 'On');
            error_reporting(-1);
        }

        $handlerPublicKey  = $input->getArgument('handlerPublicKey');
        $handlerPrivateKey = $input->getArgument('handlerPrivateKey');

        $factory = new HandlerFactory(
            new OAuthFactory(),
            [
                'Linkedin' => 'Cli\\Handler\\LinkedIn',
                'Paypal'   => 'Cli\\Handler\\PayPal'
            ]
        );

        $providerName = $input->getArgument('providerName');

        // Checks if $providerName is a supported Data Provider
        if (! $factory->check($providerName)) {
            throw new \RuntimeException(
                sprintf(
                    'Invalid provider "%s".',
                    $providerName
                )
            );
        }

        $provider = $factory->create(
            $logger,
            $providerName,
            $input->getArgument('accessToken'),
            $input->getArgument('tokenSecret') ?: '',
            $input->getArgument('appKey') ?: '',
            $input->getArgument('appSecret') ?: '',
            $input->getArgument('apiVersion') ?: '',
            $handlerPublicKey,
            $handlerPrivateKey
        );

        $provider->handle(
            $input->getArgument('publicKey'),
            $input->getArgument('userName'),
            (int) $input->getArgument('sourceId'),
            $devMode,
            $input->getArgument('dryRun') ? true : false
        );

        $logger->debug('Runner completed');
    }
}
