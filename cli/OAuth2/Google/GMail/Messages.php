<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Google\GMail;

use Cli\Handler\AbstractHandlerThread;

/**
 * Gmail Messages's Profile Scraper.
 */
class Messages extends AbstractHandlerThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        $slices = [
            [
                'after'  => strtotime('45 days ago'),
                'before' => strtotime('today')
            ],
            [
                'after'  => strtotime('90 days ago'),
                'before' => strtotime('46 days ago')
            ],
            [
                'after'  => strtotime('405 days ago'),
                'before' => strtotime('91 days ago')
            ],
            [
                'after'  => strtotime('1079 days ago'),
                'before' => strtotime('406 days ago')
            ],
            [
                'after'  => strtotime('1080 days ago'),
                'before' => strtotime('3000 days ago')
            ]
        ];

        try {
            $rawEndpoint = $this->worker->getSDK()
                ->Profile($this->worker->getUserName())
                ->Raw;
        } catch (\Exception $e) {
            $this->lastError = $exception->getMessage();
        }

        $buffer = [];
        foreach ($slices as $slice) {
            try {
                // Retrieve profile data from Google's API
                $query = sprintf('after:%s before:%s is:sent', date('Y/m/d', $slice['after']), date('Y/m/d', $slice['before']));
                $query = sprintf('maxResults=25&q=%s', urlencode($query));

                $rawBuffer = $this->worker->getService()->request(sprintf('https://www.googleapis.com/gmail/v1/users/me/threads?%s', $query));
            } catch (\Exception $exception) {
                $this->lastError = $exception->getMessage();

                return false;
            }

            $parsedBuffer = json_decode($rawBuffer, true);
            if ($parsedBuffer === null) {
                $this->lastError = 'Failed to parse response';

                return false;
            }

            if (isset($parsedBuffer['error'])) {
                $this->lastError = $parsedBuffer['error_description'];

                return false;
            }

            if (empty($parsedBuffer['threads'])) {
                break;
            }

            foreach ($parsedBuffer['threads'] as $thread) {
                $data       = $this->worker->getService()->request("https://www.googleapis.com/gmail/v1/users/me/threads/{$thread['id']}?format=metadata");
                $jsonThread = json_decode($data, true);

                if (empty($jsonThread)) {
                    $this->lastError = 'Failed to parse response';
                    continue;
                }

                if (isset($jsonThread['error'])) {
                    $this->lastError = "[{$jsonThread['error']['code']}] {$jsonThread['error']['message']}";
                    continue;
                }

                $buffer = array_merge($buffer, $jsonThread['messages']);
                if ((count($buffer) % 25) == 0) {
                    if (! $this->worker->isDryRun()) {
                        // Send message data to idOS API
                        try {
                            $this->worker->getLogger()->debug(
                                sprintf(
                                    '[%s] Uploading messages',
                                    static::class
                                )
                            );
                            $rawEndpoint->createOrUpdate(
                                $this->worker->getSourceId(),
                                'messages',
                                $buffer
                            );
                        } catch (\Exception $exception) {
                            $this->lastError = $exception->getMessage();

                            return false;
                        }
                    }

                    $buffer = [];
                }
            }

            if (! $this->worker->isDryRun()) {
                // Send message data to idOS API
                try {
                    $this->worker->getLogger()->debug(
                        sprintf(
                            '[%s] Uploading messages',
                            static::class
                        )
                    );
                    $rawEndpoint->createOrUpdate(
                        $this->worker->getSourceId(),
                        'messages',
                        $buffer
                    );
                } catch (\Exception $exception) {
                    $this->lastError = $exception->getMessage();

                    return false;
                }
            }
        }

        return true;
    }
}
