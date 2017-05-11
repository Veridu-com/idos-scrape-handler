<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Spotify;

use Cli\Handler\AbstractHandlerThread;
use Cli\Utils\Backoff;
use Illuminate\Support\Arr;

abstract class AbstractSpotifyThread extends AbstractHandlerThread {
    /**
     * Fetches spotify raw data.
     *
     * @param string $url
     * @param string $param
     *
     * @throws \Exception
     *
     * @return \Generator
     */
    protected function fetchAll(string $url, string $param = '', string $extractField = '') : \Generator {
        $service = $this->worker->getService();
        $url     = sprintf('%s?%s', $url, ltrim($param, '?'));
        $buffer  = [];
        $backoff = new Backoff(
            self::YIELD_ENABLED,
            self::YIELD_INTERVAL,
            self::YIELD_MULTIPLIER
        );
        try {
            while (true) {
                $rawBuffer = $service->request($url);

                $parsedBuffer = json_decode($rawBuffer, true);
                if ($parsedBuffer === null) {
                    throw new \Exception('Failed to parse response');
                }

                if (isset($parsedBuffer['error'])) {
                    if (isset($parsedBuffer['error']['message'])) {
                        throw new \Exception($parsedBuffer['error']['message']);
                    }

                    throw new \Exception('Unknown API error');
                }

                $url = $parsedBuffer['next'];

                if (! empty($extractField)) {
                    $parsedBuffer = Arr::get($parsedBuffer, $extractField);
                    if ($parsedBuffer === null) {
                        break;
                    }
                }

                if (! count($parsedBuffer)) {
                    break;
                }

                $buffer = array_merge($buffer, $parsedBuffer);
                if ($backoff->canYield()) {
                    yield $buffer;
                    $buffer = [];
                }

                if ($url === null) {
                    break;
                }
            }

            if (count($buffer)) {
                yield $buffer;
            }
        } catch (\Exception $exception) {
            // ensure that even if an exception get thrown, all buffer is returned
            if (count($buffer)) {
                yield $buffer;

                return;
            }

            throw $exception;
        }
    }
}
