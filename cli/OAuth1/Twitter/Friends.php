<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth1\Twitter;

/**
 * Twitter Friends's Profile Scraper.
 */
class Friends extends AbstractTwitterThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        try {
            $rawEndpoint = $this->worker->getSdk()
                ->Profile($this->worker->getUserName())
                ->Raw;
            $buffer = [];
            foreach ($this->fetchAll('/friends/list.json', 'include_user_entities=true&count=200') as $json) {
                if ($json === false || ! isset($json['users'])) {
                    break;
                }

                if (count($json['users'])) {
                    $buffer = array_merge($buffer, $json['users']);
                    if ($this->worker->isDryRun()) {
                        $this->worker->getLogger()->debug(
                            sprintf(
                                '[%s] Retrieved %d new items (%d total)',
                                static::class,
                                count($json['users']),
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
                            count($json['users']),
                            count($buffer)
                        )
                    );
                    $rawEndpoint->upsertOne(
                        $this->worker->getSourceId(),
                        'friends',
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
