<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Facebook;

class Likes extends AbstractFacebookThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        try {
            $rawEndpoint = $this->worker->getSDK()
                ->Profile($this->worker->getUserName())
                ->Source($this->worker->getSourceId())
                ->Raw;
            $buffer = [];
            foreach ($this->fetchAll('/me/likes', 'fields=name,category,category_list,created_time') as $json) {
                if ($json === false) {
                    break;
                }

                if ((! $this->worker->isDryRun()) && (count($json))) {
                    // Send post data to idOS API
                    $buffer = array_merge($buffer, $json);
                    $this->worker->getLogger()->debug(
                        sprintf(
                            '[%s] Uploading %d new items (%d total)',
                            static::class,
                            count($json),
                            count($buffer)
                        )
                    );
                    $rawEndpoint->createNew(
                        'likes',
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
