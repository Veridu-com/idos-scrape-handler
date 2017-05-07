<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Google\GMail;

use Cli\OAuth2\Google\AbstractGoogleThread;
use Cli\Utils\Backoff;

/**
 * Gmail Messages's Profile Scraper.
 */
class Messages extends AbstractGoogleThread {
    /**
     * Fetches Gmail message sample data.
     *
     * @throws \Exception
     *
     * @return \Generator
     */
    private function fetchMessageSample() : \Generator {
        $service = $this->worker->getService();
        $buffer  = [];
        $backoff = new Backoff(
            self::YIELD_ENABLED,
            self::YIELD_INTERVAL,
            self::YIELD_MULTIPLIER
        );
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
            foreach ($slices as $slice) {
                $query = sprintf(
                    'after:%s before:%s is:sent',
                    date('Y/m/d', $slice['after']),
                    date('Y/m/d', $slice['before'])
                );
                $fetch = $this->fetchAll(
                    'https://www.googleapis.com/gmail/v1/users/me/threads',
                    sprintf('maxResults=20&q=%s', urlencode($query)),
                    'threads'
                );

                foreach ($fetch as $threads) {
                    foreach ($threads as $thread) {
                        try {
                            $rawBuffer = $service->request(
                                sprintf(
                                    'https://www.googleapis.com/gmail/v1/users/me/threads/%s?format=metadata',
                                    $thread['id']
                                )
                            );

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

                            $buffer = array_merge($buffer, $parsedBuffer['messages']);
                            if ($backoff->canYield()) {
                                yield $buffer;
                                $buffer = [];
                            }
                        } catch (\Exception $exception) {
                            // exceptions on thread retrieval should not stop outer retrieval
                        }
                    }
                }
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
     * {@inheritdoc}
     */
    public function execute() : bool {
        $rawEndpoint = $this->worker->getSdk()
            ->Profile($this->worker->getUserName())
            ->Raw;

        $logger = $this->worker->getLogger();
        $data   = [];

        try {
            // Retrieve data from Google's API
            $fetch = $this->fetchMessageSample();

            foreach ($fetch as $buffer) {
                $numItems = count($buffer);

                $logger->debug(
                    sprintf(
                        '[%s] Retrieved %d items',
                        static::class,
                        $numItems
                    )
                );

                if ($this->worker->isDryRun()) {
                    $logger->debug(
                        sprintf(
                            '[%s] Messages data',
                            static::class
                        ),
                        $buffer
                    );

                    continue;
                }

                if ($numItems) {
                    // Send data to idOS API
                    $logger->debug(
                        sprintf(
                            '[%s] Sending data',
                            static::class
                        )
                    );
                    $data = array_merge($data, $buffer);
                    $rawEndpoint->upsertOne(
                        $this->worker->getSourceId(),
                        'messages',
                        $data
                    );
                    $logger->debug(
                        sprintf(
                            '[%s] Data sent',
                            static::class
                        )
                    );
                }
            }
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }

        return true;
    }
}
