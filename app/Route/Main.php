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
 * Root routing definitions.
 *
 * @link docs/overview.md
 * @see App\Controller\Main
 */
class Main implements RouteInterface {
    /**
     * {@inheritdoc}
     */
    public static function getPublicNames() : array {
        return ['main:listAll'];
    }

    /**
     * {@inheritdoc}
     */
    public static function register(App $app) {
        $app->getContainer()[\App\Controller\Main::class] = function (ContainerInterface $container) : ControllerInterface {
            return new \App\Controller\Main(
                $container->get('router'),
                $container->get('commandBus'),
                $container->get('commandFactory')
            );
        };

        self::listAll($app);
    }

    /**
     * List all Endpoints.
     *
     * Retrieve a complete list of all public endpoints.
     *
     * @apiEndpoint GET /
     * @apiGroup General
     *
     * @param \Slim\App $app
     *
     * @return void
     *
     * @link docs/listAll.md
     * @see App\Controller\Main::listAll
     */
    private static function listAll(App $app) {
        $app
            ->get(
                '/',
                'App\Controller\Main:listAll'
            )
            ->setName('main:listAll');
    }
}
