<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Facebook;

use Cli\Handler\AbstractHandlerThread;

abstract class AbstractFacebookThread extends AbstractHandlerThread {
    /**
     * Fetches facebook raw data.
     *
     * @param string $url
     * @param string $param
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function fetchAll(string $url, string $param = '') : array {
        $service = $this->worker->getService();
        $param   = sprintf('?%s', ltrim($param, '?'));
        $buffer  = [];
        try {
            while (true) {
                $rawBuffer = $service->request(sprintf('%s%s', $url, $param));

                $parsedBuffer = json_decode($rawBuffer, true);
                if ($parsedBuffer === null) {
                    throw new \Exception('Failed to parse response');
                }

                if (isset($parsedBuffer['error']['message'])) {
                    throw new \Exception($parsedBuffer['error']['message']);
                }

                if (! count($parsedBuffer['data'])) {
                    break;
                }

                $buffer = array_merge($buffer, $parsedBuffer['data']);

                if (! isset($parsedBuffer['paging']['next'])) {
                    break;
                }

                $param = substr($parsedBuffer['paging']['next'], strpos($parsedBuffer['paging']['next'], '?'));
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
