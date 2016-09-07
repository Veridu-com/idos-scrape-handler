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
 * Status routing definitions.
 *
 * @link docs/status/overview.md
 * @see App\Controller\Status
 */
class Status implements RouteInterface {
    /**
     * {@inheritdoc}
     */
    public static function getPublicNames() : array {
        return [
            'status:generalHealth'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function register(App $app) {
        $app->getContainer()[\App\Controller\Status::class] = function (ContainerInterface $container) : ControllerInterface {
            return new \App\Controller\Status(
                $container->get('router'),
                $container->get('commandBus'),
                $container->get('commandFactory')
            );
        };

        self::generalHealth($app);
    }

    /**
     * General system health.
     *
     * Performs a general system check and returns its current state.
     *
     * @apiEndpoint GET /status
     * @apiGroup Status
     *
     * @param \Slim\App $app
     *
     * @return void
     *
     * @link docs/status/generalHealth.md
     * @see App\Controller\Status:generalHealth
     */
    private static function generalHealth(App $app) {
        $app
            ->get(
                '/status',
                'App\Controller\Status:generalHealth'
            )
            ->setName('status:generalHealth');
    }
}
