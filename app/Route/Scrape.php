<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace App\Route;

use App\Controller\ControllerInterface;
use Interop\Container\ContainerInterface;
use Slim\App;

/**
 * Scrape routing definitions.
 *
 * @link docs/scrape/overview.md
 * @see App\Controller\Scrape
 */
class Scrape implements RouteInterface {
    /**
     * {@inheritdoc}
     */
    public static function getPublicNames() : array {
        return [
            'scrape:listDaemons',
            'scrape:scheduleJob'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function register(App $app) {
        $app->getContainer()[\App\Controller\Scrape::class] = function (ContainerInterface $container) : ControllerInterface {
            return new \App\Controller\Scrape(
                $container->get('router'),
                $container->get('commandBus'),
                $container->get('commandFactory')
            );
        };

        self::listDaemons($app);
        self::scheduleJob($app);
    }

    /**
     * List all Daemons.
     *
     * Lists all currently available daemons.
     *
     * @apiEndpoint GET /scrape
     * @apiGroup Scrape
     *
     * @param \Slim\App $app
     *
     * @return void
     *
     * @link docs/scrape/listDaemons.md
     * @see App\Controller\Scrape::listDaemons
     */
    private static function listDaemons(App $app) {
        $app
            ->get(
                '/scrape',
                'App\Controller\Scrape:listDaemons'
            )
            ->setName('scrape:listDaemons');
    }

    /**
     * Job Schedule Endpoint.
     *
     * Schedules a new scrape job.
     *
     * @apiEndpoint POST /scrape
     * @apiGroup Scrape
     *
     * @param \Slim\App $app
     *
     * @return void
     *
     * @link docs/scrape/scheduleJob.md
     * @see App\Controller\Scrape::scheduleJob
     */
    private static function scheduleJob(App $app) {
        $app
            ->post(
                '/scrape',
                'App\Controller\Scrape:scheduleJob'
            )
            ->setName('scrape:scheduleJob');
    }
}
