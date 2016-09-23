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
        try {
            $rawEndpoint = $this->worker->getSDK()
                ->Profile($this->worker->getUserName())
                ->Raw;

            // FIXME
            $rawPlaylists = $rawEndpoint->getOne('playlists');

            if ($rawPlaylists === null) {
                $this->lastError = 'Failed to parse response';

                return false;
            }

            $buffer = [];
            foreach ($rawPlaylists as $rawPlaylist) {
                if (! isset($playlist['tracks']['href'])) {
                    continue;
                }

                foreach ($this->fetchAll($playlist['tracks']['href']) as $track) {
                    if ($track === false) {
                        continue;
                    }

                    if (count($track)) {
                        foreach ($track as &$item) {
                            if (! isset($item['playlist'])) {
                                $item['playlists'] = [];
                            }

                            $item['playlists'][] = $playlist['id'];
                        }

                        $buffer = array_merge($buffer, $track);

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
                        $rawEndpoint->createOrUpdate(
                            $this->worker->getSourceId(),
                            'tracks',
                            $buffer
                        );
                    }
                }
            }

            return true;
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }
    }
}
