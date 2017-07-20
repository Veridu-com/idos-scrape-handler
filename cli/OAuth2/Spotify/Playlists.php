<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Spotify;

/**
 * Spotify Playlist's Profile Scraper.
 */
class Playlists extends AbstractSpotifyThread {
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
            // Retrieve data from Spotify's API
            $fetch = $this->fetchAll(
                '/me/playlists',
                'limit=50',
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

                if ($numItems) {
                    $data = array_merge($data, $buffer);

                    if ($this->worker->isDryRun()) {
                        $this->worker->writeData(
                            $data,
                            static::class
                        );

                        continue;
                    }

                    // Send data to idOS API
                    $logger->debug(
                        sprintf(
                            '[%s] Sending data',
                            static::class
                        )
                    );
                    $rawEndpoint->upsertOne(
                        $this->worker->getSourceId(),
                        'playlists',
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
