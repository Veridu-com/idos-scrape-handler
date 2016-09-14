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

// use Veridu\idOS\SDK;

/**
 * Command definition for Scraper Daemon.
 */
class Daemon extends Command {
    /**
     * Command configuration.
     *
     * @return void
     */
    protected function configure() {
        $this
            ->setName('scrape:daemon')
            ->setDescription('idOS Scrape - Daemon')
            ->addArgument(
                'serverList',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Gearman server host list (separate values by space)'
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

        $logger->debug('Initializing idOS Scrape Handler Daemon');

        // Server List setup
        $servers = $input->getArgument('serverList');

        $gearman = new \GearmanWorker();
        foreach ($servers as $server) {
            if (strpos($server, ':') === false) {
                $logger->debug(sprintf('Adding Gearman Server: %s', $server));
                $gearman->addServer($server);
            } else {
                $server    = explode(':', $server);
                $server[1] = intval($server[1]);
                $logger->debug(sprintf('Adding Gearman Server: %s:%d', $server[0], $server[1]));
                $gearman->addServer($server[0], $server[1]);
            }
        }

        // Run the worker in non-blocking mode
        $gearman->addOptions(\GEARMAN_WORKER_NON_BLOCKING);

        // 1 second I/O timeout
        $gearman->setTimeout(1000);

        $logger->debug('Registering Worker Function "scrape"');

        $gearman->addFunction(
            'scrape',
            function (\GearmanJob $job) use ($logger) {
                $logger->debug('Got a new job!');
                $jobData = json_decode($job->workload(), true);
                if ($jobData === null) {
                    $logger->debug('Invalid Job Workload!');
                    $job->sendComplete('invalid');

                    return;
                }

                // Scrape Handler Factory
                $factory = new HandlerFactory(
                    new OAuthFactory(),
                    [
                        'Linkedin' => 'Cli\\Handler\\LinkedIn',
                        'Paypal'   => 'Cli\\Handler\\PayPal'
                    ]
                );

                // Checks if $jobData['providerName'] is a supported Data Provider
                if (! $factory->check($jobData['providerName'])) {
                    throw new \RuntimeException(
                        sprintf(
                            'Invalid provider "%s".',
                            $jobData['providerName']
                        )
                    );
                }

                $provider = $factory->create(
                    $logger,
                    $jobData['providerName'],
                    isset($jobData['accessToken']) ? $jobData['accessToken'] : '',
                    isset($jobData['tokenSecret']) ? $jobData['tokenSecret'] : '',
                    isset($jobData['appKey']) ? $jobData['appKey'] : '',
                    isset($jobData['appSecret']) ? $jobData['appSecret'] : '',
                    isset($jobData['apiVersion']) ? $jobData['apiVersion'] : ''
                );

                $provider->handle(
                    $jobData['publicKey'],
                    $jobData['userName'],
                    (int) $jobData['sourceId']
                );

                $logger->debug('Job done!');
                $job->sendComplete('ok');
            }
        );

        $logger->debug('Registering Ping Function "ping"');

        // Register Thread's Ping Function
        $gearman->addFunction(
            'ping',
            function (\GearmanJob $job) use ($logger) {
                $logger->debug('Ping!');

                return 'pong';
            }
        );

        $logger->debug('Entering Gearman Worker Loop');

        // Gearman's Loop
        while ($gearman->work()
                || ($gearman->returnCode() == \GEARMAN_IO_WAIT)
                || ($gearman->returnCode() == \GEARMAN_NO_JOBS)
                || ($gearman->returnCode() == \GEARMAN_TIMEOUT)
        ) {
            if ($gearman->returnCode() == \GEARMAN_SUCCESS) {
                continue;
            }

            if (! @$gearman->wait()) {
                if ($gearman->returnCode() == \GEARMAN_NO_ACTIVE_FDS) {
                    // No server connection, sleep before reconnect
                    $logger->debug('No active server, sleep before retry');
                    sleep(5);
                    continue;
                }

                if ($gearman->returnCode() == \GEARMAN_TIMEOUT) {
                    // Job wait timeout, sleep before retry
                    sleep(1);
                    continue;
                }
            }
        }

        $logger->debug('Leaving Gearman Worker Loop');
    }
}
