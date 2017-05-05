<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Spotify;

use Cli\Handler\AbstractHandlerThread;
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
     * @return array
     */
    protected function fetchAll(string $url, string $param = '', string $extractField = '') : array {
        $service = $this->worker->getService();
        $url     = sprintf('%s?%s', $url, ltrim($param, '?'));
        $buffer  = [];
        try {
            while (true) {
                $rawBuffer = $service->request($url);

                $parsedBuffer = json_decode($rawBuffer, true);
                if ($parsedBuffer === null) {
                    throw new \Exception('Failed to parse response');
                }

                if (isset($parsedBuffer['error']['message'])) {
                    throw new \Exception($parsedBuffer['error']['message']);
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

                if ($url === null) {
                    break;
                }
            }

            return $buffer;
        } catch (\Exception $exception) {
            // ensure that even if an exception get thrown, all buffer is returned
            if (count($buffer)) {
                return $buffer;
            }

            throw $exception;
        }
    }
}
