<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Facebook;

class Friends extends AbstractFacebookThread {
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
            $fetch = $this->fetchAll(
                '/me/friends',
                'fields=id,first_name,last_name,gender,locale,languages,birthday,education,hometown,location,picture.width(1024).height(1024),relationship_status,significant_other,work,friends'
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

                if ($this->worker->isDryRun()) {
                    $logger->debug(
                        sprintf(
                            '[%s] Friends data',
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
                        'friends',
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
