<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth1\Twitter;

use Cli\Handler\AbstractHandlerThread;
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
     * @return array
     */
    protected function fetchAllWithIds(string $url, string $param = '') : array {
        $service = $this->worker->getService();
        $param   = sprintf('?%s', ltrim($param, '?'));
        $buffer  = [];
        $minId   = gmp_init(PHP_INT_MAX);
        try {
            $rawBuffer = $service->request(sprintf('%s%s', $url, $param));
            while (true) {
                $lastId = $minId;

                $parsedBuffer = json_decode($rawBuffer, true);
                if ($parsedBuffer === null) {
                    throw new \Exception('Failed to parse response');
                }

                if (isset($parsedBuffer['errors'][0]['message'])) {
                    throw new \Exception($parsedBuffer['errors'][0]['message']);
                }

                if (! count($parsedBuffer)) {
                    break;
                }

                $minId = min($minId, min(array_column($parsedBuffer, 'id_str')));

                $buffer = array_merge($buffer, $parsedBuffer);

                if (gmp_cmp($lastId, $minId) == 0) {
                    break;
                }

                $rawBuffer = $service->request(sprintf('%s%s&max_id=%d', $url, $param, $minId));
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

    /**
     * Fetches twitter raw data using cursors.
     *
     * @param string $url
     * @param string $param
     * @param string $extractField
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function fetchAllWithCursors(string $url, string $param = '', string $extractField = '') : array {
        $service = $this->worker->getService();
        $param   = sprintf('?%s', ltrim($param, '?'));
        $buffer  = [];
        $cursor  = -1;
        try {
            $rawBuffer = $service->request(sprintf('%s%s', $url, $param));
            while (true) {
                $lastCursor = $cursor;

                $parsedBuffer = json_decode($rawBuffer, true);
                if ($parsedBuffer === null) {
                    throw new \Exception('Failed to parse response');
                }

                if (isset($parsedBuffer['errors'][0]['message'])) {
                    throw new \Exception($parsedBuffer['errors'][0]['message']);
                }

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

                if (($lastCursor === $cursor) || ($cursor === '0')) {
                    break;
                }

                $rawBuffer = $service->request(sprintf('%s%s&cursor=%s', $url, $param, $cursor));
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
