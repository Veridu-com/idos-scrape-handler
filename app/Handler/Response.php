<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace App\Handler;

use App\Command\ResponseDispatch;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Respect\Validation\Validator;
use Slim\HttpCache\CacheProvider;

/**
 * Handles HTTP Responses.
 */
class Response implements HandlerInterface {
    private $httpCache;
    private $validator;

    /**
     * Handles JSON-encoded Responses.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array                               $body
     * @param int                                 $statusCode
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function jsonResponse(
        ResponseInterface $response,
        array $body,
        int $statusCode = 200
    ) : ResponseInterface {
        unset($body['list'][0]['private_key']);
        $body     = json_encode($body);
        $response = $this->httpCache->withEtag($response, sha1($body), 'weak');

        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->write($body);
    }

    /**
     * Handles JavaScript/JSONP-encoded Responses.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array                               $body
     * @param int                                 $statusCode
     * @param string                              $callback
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function javascriptResponse(
        ResponseInterface $response,
        array $body,
        int $statusCode = 200,
        string $callback = 'jsonp'
    ) : ResponseInterface {
        $body     = sprintf('/**/%s(%s)', $callback, json_encode($body));
        $response = $this->httpCache->withEtag($response, sha1($body), 'weak');

        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/javascript')
            ->write($body);
    }

    /**
     * Handles XML-encoded Responses.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array                               $body
     * @param int                                 $statusCode
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function xmlResponse(
        ResponseInterface $response,
        array $body,
        int $statusCode = 200
    ) : ResponseInterface {
        $xml = new \SimpleXMLElement('<veridu/>');
        array_walk_recursive(
            $body,
            function ($value, $key) use ($xml) {
                if (is_bool($value))
                    $xml->addChild($key, ($value ? 'true' : 'false'));
                else
                    $xml->addChild($key, $value);
            }
        );
        $body     = $xml->asXML();
        $response = $this->httpCache->withEtag($response, sha1($body), 'weak');

        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/xml; charset=utf-8')
            ->write($body);
    }

    /**
     * Handles Text/Plain Responses.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array                               $body
     * @param int                                 $statusCode
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function textResponse(
        ResponseInterface $response,
        array $body,
        int $statusCode = 200
    ) : ResponseInterface {
        $body     = http_build_query($body);
        $response = $this->httpCache->withEtag($response, sha1($body), 'weak');

        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'text/plain')
            ->write($body);
    }

    /**
     * Dependency Container registration.
     *
     * @param \Interop\Container\ContainerInterface $container
     *
     * @return void
     */
    public static function register(ContainerInterface $container) {
        $container[self::class] = function (ContainerInterface $container) {
            return new \App\Handler\Response(
                $container->get('httpCache'),
                $container->get('validator')
            );
        };
    }

    /**
     * Class constructor.
     *
     * @param \Slim\HttpCache\CacheProvider $httpCache
     * @param \Respect\Validation\Validator $validator
     *
     * @return void
     */
    public function __construct(CacheProvider $httpCache, Validator $validator) {
        $this->httpCache = $httpCache;
        $this->validator = $validator;
    }

    /**
     * Handles a response dispatch, parsing multiple request parameters.
     *
     * Parameters:
     *  - failSilently: forces 200 HTTP Status for 4xx and 5xx responses
     *  - hideLinks: hides HATEOAS discovery/relation links
     *  - forceOutput: overrides HTTP's Accept header
     *
     * @param App\Command\ResponseDispatch $command
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handleResponseDispatch(ResponseDispatch $command) : ResponseInterface {
        $request    = $command->request;
        $response   = $command->response;
        $body       = $command->body;
        $statusCode = $command->statusCode;

        if ($body === null) {
            $body = [];
        }

        if (! isset($body['status'])) {
            $body = array_merge(['status' => true], $body);
        }

        $queryParams = $request->getQueryParams();

        // Forces HTTP errors (4xx and 5xx) to be suppressed
        if (($statusCode >= 400)
            && (isset($queryParams['failSilently']))
            && ($this->validator->trueVal()->validate($queryParams['failSilently']))
        ) {
            $statusCode = 200;
        }

        // Suppresses links field on response body
        if ((isset($body['links'], $queryParams['hideLinks']))
            && ($this->validator->trueVal()->validate($queryParams['hideLinks']))
        ) {
            unset($body['links']);
        }

        // Overrides HTTP's Accept header
        if (! empty($queryParams['forceOutput'])) {
            switch (strtolower($queryParams['forceOutput'])) {
                // case 'plain':
                // 	$accept = ['text/plain'];
                // 	break;
                case 'xml':
                    $accept = ['application/xml'];
                    break;
                case 'javascript':
                    $accept = ['application/javascript'];
                    break;
                case 'json':
                default:
                    $accept = ['application/json'];
            }
        } else {
            // Extracts HTTP's Accept header
            $accept = $request->getHeaderLine('Accept');

            if (preg_match_all('/([^\/]+\/[^;,]+)[^,]*,?/', $accept, $matches)) {
                $accept = $matches[1];
            } else {
                $accept = ['application/json'];
            }
        }

        // Last Modified Cache Header
        if (isset($body['updated'])) {
            $response = $this
                ->httpCache
                ->withLastModified($response, $body['updated']);
        } elseif (isset($body['data']['updated'])) {
            $response = $this
                ->httpCache
                ->withLastModified($response, $body['data']['updated']);
        }

        // Force Content-Type to be used
        $response = $response->withHeader('X-Content-Type-Options', 'nosniff');

        // if ((in_array('text/html', $accept)) || (in_array('text/plain', $accept)))
        // 	return $this->textResponse($response, $body, $statusCode);

        // if (in_array('application/xml', $accept))
        // 	return $this->xmlResponse($response, $body, $statusCode);

        if (in_array('application/javascript', $accept)) {
            if (empty($queryParams['callback'])) {
                $callback = 'jsonp';
            }

            if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $callback)) {
                $callback = 'jsonp';
            }

            return $this->javascriptResponse($response, $body, $statusCode, $callback);
        }

        return $this->jsonResponse($response, $body, $statusCode);
    }
}
