<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Google\GMail;

use Cli\OAuth2\Google\AbstractGoogleThread;

/**
 * Gmail Labels's Profile Scraper.
 */
class Labels extends AbstractGoogleThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        try {
            $rawEndpoint = $this->worker->getSDK()
                ->Profile($this->worker->getUserName())
                ->Raw;
            $buffer = [];
            foreach ($this->fetchAll('https://www.googleapis.com/gmail/v1/users/me/labels', '') as $json) {
                if ($json === false) {
                    break;
                }

                if (count($json)) {
                    $buffer = array_merge($buffer, $json);
                }
            }

            if (empty($buffer['labels'])) {
                return false;
            }

            $labels = [];
            foreach ($buffer['labels'] as $label) {
                $data      = $this->worker->getService()->request("https://www.googleapis.com/gmail/v1/users/me/labels/{$label['id']}");
                $jsonLabel = json_decode($data, true);

                if ($jsonLabel === false) {
                    $this->lastError = "Failed to fetch /me/labels/id ($this->lastError})";
                    break;
                }

                $labels[] = $jsonLabel;
            }

            if ($this->worker->isDryRun()) {
                $this->worker->getLogger()->debug(
                    sprintf(
                        '[%s] Retrieved %d new items (%d total)',
                        static::class,
                        count($json),
                        count($labels)
                    )
                );

                return true;
            }

            // Send post data to idOS API
            $this->worker->getLogger()->debug(
                sprintf(
                    '[%s] Uploading %d new items (%d total)',
                    static::class,
                    count($json),
                    count($labels)
                )
            );
            $rawEndpoint->upsertOne(
                $this->worker->getSourceId(),
                'labels',
                $labels
            );

            return true;
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }
    }
}
