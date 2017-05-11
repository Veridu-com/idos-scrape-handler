<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth1\Twitter;

use Cli\Handler\AbstractHandlerThread;
use Cli\Utils\Backoff;
use Illuminate\Support\Arr;

abstract class AbstractTwitterThread extends AbstractHandlerThread {
    /**
     * Fetches twitter raw data using ids.
     *
     * @param string $url
     * @param string $param
     *
     * @throws \Exception
     *
     * @return \Generator
     */
    protected function fetchAllWithIds(string $url, string $param = '') : \Generator {
        $service = $this->worker->getService();
        $param   = sprintf('?%s', ltrim($param, '?'));
        $buffer  = [];
        $minId   = gmp_init(PHP_INT_MAX);
        $backoff = new Backoff(
            self::YIELD_ENABLED,
            self::YIELD_INTERVAL,
            self::YIELD_MULTIPLIER
        );
        try {
            $rawBuffer = $service->request(sprintf('%s%s', $url, $param));
            while (true) {
                $lastId = $minId;

                $parsedBuffer = json_decode($rawBuffer, true);
                if ($parsedBuffer === null) {
                    throw new \Exception('Failed to parse response');
                }

                if (isset($parsedBuffer['errors'])) {
                    if (isset($parsedBuffer['errors'][0]['message'])) {
                        throw new \Exception($parsedBuffer['errors'][0]['message']);
                    }

                    throw new \Exception('Unknown API error');
                }

                if (! count($parsedBuffer)) {
                    break;
                }

                $minId = min($minId, min(array_column($parsedBuffer, 'id_str')));

                $buffer = array_merge($buffer, $parsedBuffer);
                if ($backoff->canYield()) {
                    yield $buffer;
                    $buffer = [];
                }

                if (gmp_cmp($lastId, $minId) == 0) {
                    break;
                }

                $rawBuffer = $service->request(sprintf('%s%s&max_id=%d', $url, $param, $minId));
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

    /**
     * Fetches twitter raw data using cursors.
     *
     * @param string $url
     * @param string $param
     * @param string $extractField
     *
     * @throws \Exception
     *
     * @return \Generator
     */
    protected function fetchAllWithCursors(string $url, string $param = '', string $extractField = '') : \Generator {
        $service = $this->worker->getService();
        $param   = sprintf('?%s', ltrim($param, '?'));
        $buffer  = [];
        $cursor  = -1;
        $backoff = new Backoff(
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

                if (isset($parsedBuffer['errors'])) {
                    if (isset($parsedBuffer['errors'][0]['message'])) {
                        throw new \Exception($parsedBuffer['errors'][0]['message']);
                    }

                    throw new \Exception('Unknown API error');
                }

                $lastCursor = $cursor;
                if (isset($parsedBuffer['next_cursor_str'])) {
                    $cursor = $parsedBuffer['next_cursor_str'];
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
                }

                if (($lastCursor === $cursor) || ($cursor === '0')) {
                    break;
                }

                $rawBuffer = $service->request(sprintf('%s%s&cursor=%s', $url, $param, $cursor));
            }

            yield $buffer;
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
