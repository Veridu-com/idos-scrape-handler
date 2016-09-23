<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Dropbox;

use Cli\Handler\AbstractHandlerThread;

/**
 * Dropbox's metadata scraper.
 */
class Metadata extends AbstractHandlerThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        try {
            $rawEndpoint = $this->worker->getSDK()
                ->Profile($this->worker->getUserName())
                ->Raw;
            // Retrieve profile data from Dropbox's API
            $rawBuffer = $this->worker->getService()->request('/metadata/auto/?&list=true&include_media_info=true&file_limit=25000');
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }

        $parsedBuffer = json_decode($rawBuffer, true);
        if ($parsedBuffer === null) {
            $this->lastError = 'Failed to parse response';

            return false;
        }

        if (isset($parsedBuffer['error'])) {
            $this->lastError = $parsedBuffer['error'];

            return false;
        }

        if (!isset($parsedBuffer['contents']) || count($parsedBuffer['contents']) == 0) {
            $this->lastError = "Root listing has no items";

            return false;
        }

        $dirs = [];

        foreach ($parsedBuffer['contents'] as $content) {
            if ($content['is_dir']) {
                $dirs[] = $content['path'];
            }
        }

        $contents[] = array('path' => $parsedBuffer['path'], 'contents' => $parsedBuffer['contents']);

        foreach ($dirs as $path) {
            $rawPathBuffer = $this->worker->getService()->request("/metadata/auto/$path?&list=true&include_media_info=true&file_limit=25000");
            $parsedPathBuffer = json_decode($rawPathBuffer, true);

            if (empty($parsedPathBuffer)) {
                $this->lastError = 'Unknown error';

                return false;
            }

            if (isset($parsedPathBuffer['error'])) {
                $this->lastError = $parsedBuffer['error'];

                return false;
            }

            if (isset($parsedPathBuffer['contents']) && count($parsedPathBuffer['contents']) != 0) {
                $contents[] = [
                    'path' => $parsedPathBuffer['path'],
                    'contents' => $parsedPathBuffer['contents']
                ];
            }
        }

        if (! $this->worker->isDryRun()) {
            // Send metadata to idOS API
            try {
                $this->worker->getLogger()->debug(
                    sprintf(
                        '[%s] Uploading metadata',
                        static::class
                    )
                );
                $rawEndpoint->createOrUpdate(
                    $this->worker->getSourceId(),
                    'metadata',
                    $contents
                );
            } catch (\Exception $exception) {
                $this->lastError = $exception->getMessage();

                return false;
            }
        }

        return true;
    }
}
