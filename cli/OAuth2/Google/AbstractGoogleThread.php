<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Google;

use Cli\Handler\AbstractHandlerThread;
use Cli\Utils\Backoff;
use Illuminate\Support\Arr;

abstract class AbstractGoogleThread extends AbstractHandlerThread {
    /**
     * Fetches google raw data.
     *
     * @param string $url
     * @param string $param
     * @param string $extractField
     *
     * @return mixed
     */
    protected function fetchAll(string $url, string $param = '', string $extractField = '') : \Generator {
        $service   = $this->worker->getService();
        $param     = sprintf('?%s', ltrim($param, '?'));
        $buffer    = [];
        $pageToken = '';
        $backoff   = new Backoff(
            self::YIELD_ENABLED,
            self::YIELD_INTERVAL,
            self::YIELD_MULTIPLIER
        );
        try {
            $rawBuffer = $service->request(sprintf('%s%s', $url, $param));
            while (true) {
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

                $lastPageToken = $pageToken;
                if (isset($parsedBuffer['nextPageToken'])) {
                    $pageToken = $parsedBuffer['nextPageToken'];
                }

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

                if (($lastPageToken === $pageToken) || (empty($pageToken))) {
                    break;
                }

                $rawBuffer = $service->request(sprintf('%s%s&pageToken=%s&full', $url, $param, $pageToken));
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
