<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Spotify;

use Cli\Handler\AbstractHandlerThread;

abstract class AbstractSpotifyThread extends AbstractHandlerThread {
    protected function fetchAll(string $url, string $param = '') : \Generator {
        $param  = sprintf('?%s', ltrim($param, '?'));
        $buffer = [];
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

                if (! count($json['items'])) {
                    break;
                }

                $buffer = array_merge($buffer, $json['items']);
                if (count($buffer) > 100) {
                    yield $buffer;
                    $buffer = [];
                }

                if (! isset($json['paging']['next'])) {
                    break;
                }

                $param = substr($json['paging']['next'], strpos($json['paging']['next'], '?'));
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
