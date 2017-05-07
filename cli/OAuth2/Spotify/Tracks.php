<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Spotify;

use Cli\Utils\Backoff;

/**
 * Spotify Tracks's Profile Scraper.
 */
class Tracks extends AbstractSpotifyThread {
    /**
     * Fetches tracks data.
     *
     * @throws \Exception
     *
     * @return \Generator
     */
    private function fetchAllTracks() : \Generator {
        $buffer  = [];
        $backoff = new Backoff(
            self::YIELD_ENABLED,
            self::YIELD_INTERVAL,
            self::YIELD_MULTIPLIER
        );
        try {
            $fetch = $this->fetchAll('/me/playlists', 'limit=50', 'items');

            foreach ($fetch as $playlists) {
                foreach ($playlists as $playlist) {
                    if (! isset($playlist['tracks']['href'])) {
                        continue;
                    }

                    try {
                        $fetch = $this->fetchAll($playlist['tracks']['href'], 'limit=100', 'items');

                        foreach ($fetch as $tracks) {
                            foreach ($tracks as &$track) {
                                if (! isset($track['playlists'])) {
                                    $track['playlists'] = [];
                                }

                                $track['playlists'][] = $playlist['id'];
                            }

                            $buffer = array_merge($buffer, $tracks);
                            if ($backoff->canYield()) {
                                yield $buffer;
                                $buffer = [];
                            }
                        }
                    } catch (\Exception $exception) {
                        // exceptions on track retrieval should not stop outer retrieval
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
            // Retrieve data from Spotify's API
            $fetch = $this->fetchAllTracks();

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
                            '[%s] Tracks data',
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
                        'tracks',
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
