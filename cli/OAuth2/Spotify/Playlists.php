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
        try {
            $rawEndpoint = $this->worker->getSDK()
                ->Profile($this->worker->getUserName())
                ->Raw;
            $playlists = [];

            $rawIdBuffer = $this->worker->getService()->request('/me');

            $parsedIdBuffer = json_decode($rawIdBuffer, true);
            if ($parsedIdBuffer === null) {
                $this->lastError = 'Failed to parse id response';

                return false;
            }

            if (isset($parsedIdBuffer['error'])) {
                $this->lastError = $parsedIdBuffer['error']['message'];

                return false;
            }

            $profileId = $parsedIdBuffer['id'];

            foreach ($this->fetchAll('/users/' . $profileId . '/playlists', '') as $json) {
                if ($json === false) {
                    break;
                }

                if (count($json)) {
                    foreach ($json as $item) {
                        $playlists[] = $item;
                    }

                    if ($this->worker->isDryRun()) {
                        $this->worker->getLogger()->debug(
                            sprintf(
                                '[%s] Retrieved %d new items (%d total)',
                                static::class,
                                count($json),
                                count($playlists)
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
                            count($playlists)
                        )
                    );
                    $rawEndpoint->upsertOne(
                        $this->worker->getSourceId(),
                        'playlists',
                        $playlists
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
