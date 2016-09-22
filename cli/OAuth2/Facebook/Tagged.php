<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Facebook;

class Tagged extends AbstractFacebookThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        try {
            $rawEndpoint = $this->worker->getSDK()
                ->Profile($this->worker->getUserName())
                ->Raw;
            $buffer = [];
            foreach ($this->fetchAll('/me/tagged', 'fields=from,to,message,message_tags,picture,link,name,caption,description,icon,privacy,type,status_type,created_time,updated_time,is_hidden,is_expired,likes,comments') as $json) {
                if ($json === false) {
                    break;
                }

                if (count($json)) {
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
                    $rawEndpoint->createOrUpdate(
                        $this->worker->getSourceId(),
                        'tagged',
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
