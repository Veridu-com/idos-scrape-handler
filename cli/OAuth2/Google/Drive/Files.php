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
        try {
            $rawEndpoint = $this->worker->getSDK()
                ->Profile($this->worker->getUserName())
                ->Raw;
            $buffer = [];
            foreach ($this->fetchAll('https://www.googleapis.com/drive/v2/files', '') as $json) {
                if ($json === false) {
                    break;
                }

                if (isset($json['items']) && count($json)) {
                    foreach ($json['items'] as $item) {
                        if (isset($item['exportLinks'])) {
                            unset($item['exportLinks']);
                        }
                    }

                    $buffer = array_merge($buffer, $json);
                    if ($this->worker->isDryRun()) {
                        $this->worker->getLogger()->debug(
                            sprintf(
                                '[%s] Retrieved %d new items (%d total)',
                                static::class,
                                count($json),
                                count($buffer)
                            )
                        );
                        continue;
                    }

                    // Send post data to idOS API
                    $this->worker->getLogger()->debug(
                        sprintf(
                            '[%s] Uploading %d new items (%d total)',
                            static::class,
                            count($json),
                            count($buffer)
                        )
                    );
                    $rawEndpoint->upsertOne(
                        $this->worker->getSourceId(),
                        'files',
                        $buffer
                    );
                }
            }

            return true;
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }
    }
}
