<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Spotify;

/**
 * Spotify Tracks's Profile Scraper.
 */
class Tracks extends AbstractSpotifyThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        $rawEndpoint = $this->worker->getSdk()
            ->Profile($this->worker->getUserName())
            ->Raw;

        $logger = $this->worker->getLogger();

        try {
            // Retrieve data from Spotify's API
            $playlists = $this->fetchAll('/me/playlists', 'limit=50', 'items');
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMEssage();

            return false;
        }

        $buffer = [];
        foreach ($playlists as $playlist) {
            if (! isset($playlist['tracks']['href'])) {
                continue;
            }

            try {
                $tracks = $this->fetchAll($playlist['tracks']['href'], 'limit=100', 'items');
                if (count($tracks)) {
                    foreach ($tracks as &$track) {
                        if (! isset($track['playlists'])) {
                            $track['playlists'] = [];
                        }

                        $track['playlists'][] = $playlist['id'];
                    }

                    $buffer = array_merge($buffer, $tracks);
                }
            } catch (\Exception $exception) {
                // avoid breaking retrieval due to an exception
                continue;
            }
        }

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
                    '[%s] Tracks data',
                    static::class
                ),
                $buffer
            );

            return true;
        }

        if ($numItems) {
            // Send tracks data to idOS API
            try {
                $logger->debug(
                    sprintf(
                        '[%s] Sending data',
                        static::class
                    )
                );
                $rawEndpoint->upsertOne(
                    $this->worker->getSourceId(),
                    'tracks',
                    $buffer
                );
                $logger->debug(
                    sprintf(
                        '[%s] Data sent',
                        static::class
                    )
                );
            } catch (\Exception $exception) {
                $this->lastError = $exception->getMessage();

                return false;
            }
        }

        return true;
    }
}
