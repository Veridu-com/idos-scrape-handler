<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Google\Drive;

use Cli\OAuth2\Google\AbstractGoogleThread;

/**
 * Google Drive File's Profile Scraper.
 */
class Files extends AbstractGoogleThread {
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
            $fetch = $this->fetchAll(
                'https://www.googleapis.com/drive/v2/files',
                'maxResults=100',
                'items'
            );

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
                            '[%s] Files data',
                            static::class
                        ),
                        $buffer
                    );

                    continue;
                }

                if ($numItems) {
                    // Send data to idOS API
                    foreach ($buffer as &$item) {
                        if (isset($item['exportLinks'])) {
                            unset($item['exportLinks']);
                        }
                    }

                    $logger->debug(
                        sprintf(
                            '[%s] Sending data',
                            static::class
                        )
                    );
                    $data = array_merge($data, $buffer);
                    $rawEndpoint->upsertOne(
                        $this->worker->getSourceId(),
                        'files',
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
