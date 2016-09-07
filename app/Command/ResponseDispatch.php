<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace App\Command;

/**
 * Response Dispatch Command.
 */
class ResponseDispatch extends AbstractCommand {
    /**
     * Request instance.
     *
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    public $request;
    /**
     * Response instance.
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    public $response;
    /**
     * Response Body.
     *
     * @var array
     */
    public $body;
    /**
     * HTTP Status Code.
     *
     * @var int
     */
    public $statusCode = 200;

    /**
     * {@inheritdoc}
     */
    public function setParameters(array $parameters) {
        if (isset($parameters['request'])) {
            $this->request = $parameters['request'];
        }

        if (isset($parameters['response'])) {
            $this->response = $parameters['response'];
        }

        if (isset($parameters['body'])) {
            $this->body = $parameters['body'];
        }

        if (isset($parameters['statusCode'])) {
            $this->statusCode = $parameters['statusCode'];
        }

        return $this;
    }
}
