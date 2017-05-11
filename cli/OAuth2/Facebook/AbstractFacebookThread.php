<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Facebook;

use Cli\Handler\AbstractHandlerThread;
use Cli\Utils\Backoff;

abstract class AbstractFacebookThread extends AbstractHandlerThread {
    /**
     * Fetches facebook raw data.
     *
     * @param string $url
     * @param string $param
     *
     * @throws \Exception
     *
     * @return \Generator
     */
    protected function fetchAll(string $url, string $param = '') : \Generator {
        $service = $this->worker->getService();
        $param   = sprintf('?%s', ltrim($param, '?'));
        $buffer  = [];
        $backoff = new Backoff(
            self::YIELD_ENABLED,
            self::YIELD_INTERVAL,
            self::YIELD_MULTIPLIER
        );
        try {
            while (true) {
                $rawBuffer = $service->request(sprintf('%s%s', $url, $param));

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

                if (! count($parsedBuffer['data'])) {
                    break;
                }

                $buffer = array_merge($buffer, $parsedBuffer['data']);
                if ($backoff->canYield()) {
                    yield $buffer;
                    $buffer = [];
                }

                if (! isset($parsedBuffer['paging']['next'])) {
                    break;
                }

                $param = substr($parsedBuffer['paging']['next'], strpos($parsedBuffer['paging']['next'], '?'));
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
