<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Google;

use Cli\Handler\AbstractHandlerThread;

abstract class AbstractGoogleThread extends AbstractHandlerThread {
    /**
     * Fetches google raw data.
     *
     * @param string $url
     * @param string $param
     * @param int    $bufferSize
     *
     * @return mixed
     */
    protected function fetchAll(string $url, string $param = '', int $bufferSize = 100) : \Generator {
        $defaultParam = ltrim($param, '?');
        $param        = sprintf('?%s', $defaultParam);
        $buffer       = [];
        try {
            while (true) {
                $data = $this->worker->getService()->request(sprintf('%s%s', $url, $param));
                $json = json_decode($data, true);
                if ($json === null) {
                    throw new \Exception('Failed to parse response');
                }

                if (isset($json['error'])) {
                    throw new \Exception($json['error']['message']);
                }

                if (! count($json)) {
                    break;
                }

                $buffer = array_merge($buffer, $json);
                if (count($buffer) > $bufferSize) {
                    yield $buffer;
                    $buffer = [];
                }

                if (! isset($json['nextPageToken'])) {
                    break;
                }

                $param = sprintf('?%s&pageToken=%s&full', $defaultParam, $json['nextPageToken']);
            }

            if (count($buffer)) {
                yield $buffer;
            }
        } catch (\Exception $exception) {
            // ensure that even if an exception get thrown, all buffer is returned
            if (count($buffer)) {
                yield $buffer;
            }

            throw $exception;
        }
    }
}
