<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Dropbox;

use Cli\Handler\AbstractHandlerThread;
use Cli\Utils\Backoff;

/**
 * Dropbox's metadata scraper.
 */
class Metadata extends AbstractHandlerThread {
    /**
     * Fetches dropbox metadata data.
     *
     * @throws \Exception
     *
     * @return \Generator
     */
    private function fetchAllMetadata() : \Generator {
        $service = $this->worker->getService();
        $buffer  = [];
        $backoff = new Backoff(
            self::YIELD_ENABLED,
            self::YIELD_INTERVAL,
            self::YIELD_MULTIPLIER
        );
        try {
            $rawBuffer = $service->request(
                'files/list_folder',
                'POST',
                json_encode(
                    [
                        'path'                                => '',
                        'recursive'                           => false,
                        'include_media_info'                  => true,
                        'include_deleted'                     => false,
                        'include_has_explicit_shared_members' => true
                    ]
                ),
                ['Content-Type' => 'application/json']
            );
            while (true) {
                $parsedBuffer = json_decode($rawBuffer, true);
                if ($parsedBuffer === null) {
                    throw new \Exception('Failed to parse response');
                }

                if (isset($parsedBuffer['error_summary'])) {
                    throw new \Exception($parsedBuffer['error_summary']);
                }

                if (empty($parsedBuffer['entries'])) {
                    break;
                }

                $buffer = array_merge($buffer, $parsedBuffer['entries']);
                if ($backoff->canYield()) {
                    yield $buffer;
                    $buffer = [];
                }

                if ((empty($parsedBuffer['has_more'])) || (empty($parsedBuffer['cursor']))) {
                    break;
                }

                $rawBuffer = $service->request(
                    'files/list_folder/continue',
                    'POST',
                    json_encode(
                        [
                            'cursor' => $parsedBuffer['cursor']
                        ]
                    ),
                    ['Content-Type' => 'application/json']
                );
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
            // Retrieve data from Dropbox's API
            $fetch = $this->fetchAllMetadata();

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
                            '[%s] Metadata data',
                            static::class
                        ),
                        $buffer
                    );

                    continue;
                }

                if ($numItems) {
                    // Send metadata to idOS API
                    $logger->debug(
                        sprintf(
                            '[%s] Sending data',
                            static::class
                        )
                    );
                    $data = array_merge($data, $buffer);
                    $rawEndpoint->upsertOne(
                        $this->worker->getSourceId(),
                        'metadata',
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
