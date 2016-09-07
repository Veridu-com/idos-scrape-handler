<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace App\Controller;

use App\Factory\Command;
use League\Tactician\CommandBus;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Router;

/**
 * Handles requests to /.
 */
class Main implements ControllerInterface {
    /**
     * Router instance.
     *
     * @var \Slim\Router
     */
    private $router;
    /**
     * Command Bus instance.
     *
     * @var \League\Tactician\CommandBus
     */
    private $commandBus;
    /**
     * Command Factory instance.
     *
     * @var App\Factory\Command
     */
    private $commandFactory;

    /**
     * Class constructor.
     *
     * @param \Slim\Router                 $router
     * @param \League\Tactician\CommandBus $commandBus
     * @param App\Factory\Command          $commandFactory
     *
     * @return void
     */
    public function __construct(
        Router $router,
        CommandBus $commandBus,
        Command $commandFactory
    ) {
        $this->router         = $router;
        $this->commandBus     = $commandBus;
        $this->commandFactory = $commandFactory;
    }

    /**
     * Lists all public endpoints.
     *
     * @apiEndpointResponse 200 schema/listAll.json
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface      $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function listAll(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface {
        $files = glob(__DIR__ . '/../Route/*.php');

        $classList = array_map(
            function ($filename) {
                return basename($filename, '.php');
            }, array_filter(
                $files, function ($filename) {
                    $add = true;
                    $add &= strpos($filename, 'Interface') === false;

                    return $add;
                }
            )
        );

        $routeList    = [];
        $publicRoutes = [];

        foreach ($this->router->getRoutes() as $route)
            $routeList[$route->getName()] = [
                'name'    => $route->getName(),
                'uri'     => $route->getPattern(),
                'methods' => $route->getMethods()
            ];

        foreach ($classList as $className) {
            $routeClass = sprintf('\\App\\Route\\%s', $className);
            foreach ($routeClass::getPublicNames() as $routeName)
                $publicRoutes[] = $routeList[$routeName];
        }

        $command = $this->commandFactory->create('ResponseDispatch');
        $command
            ->setParameter('request', $request)
            ->setParameter('response', $response)
            ->setParameter('body', ['data' => $publicRoutes]);

        return $this->commandBus->handle($command);
    }
}
