<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth1\Twitter;

use Cli\Handler\AbstractHandlerThread;

abstract class AbstractTwitterThread extends AbstractHandlerThread {
    protected function fetchAll(string $url, string $param = '') : \Generator {
        $param  = sprintf('?%s', ltrim($param, '?'));
        $buffer = [];
        $cursor = -1;
        try {
            while (true) {
                $data = $this->worker->getService()->request(sprintf('%s%s&cursor=%s', $url, $param, $cursor));
                $json = json_decode($data, true);
                if ($json === null) {
                    throw new \Exception('Failed to parse response');
                }

                if (isset($json['errors'])) {
                    throw new \Exception($json['errors'][0]['message']);
                }

                if (! count($json)) {
                    break;
                }

                $buffer = array_merge($buffer, $json);
                if (count($buffer) > 100) {
                    yield $buffer;
                    $buffer = [];
                }

                if (! isset($json['next_cursor_str']) || $json['next_cursor_str'] === "0") {
                    break;
                }

                $cursor = $json['next_cursor_str'];
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
