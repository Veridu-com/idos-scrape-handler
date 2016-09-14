<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Facebook;

use idOS\SDK;

class Likes extends AbstractFacebookThread {
    /**
     * {@inheritdoc}
     */
    public function execute(SDK $sdk) : bool {
        try {
            $buffer = [];
            foreach ($this->fetchAll('/me/likes', 'fields=name,category,category_list,created_time') as $json) {
                if ($json === false) {
                    break;
                }

                if ((! $this->dryRun) && (count($json))) {
                    // Send post data to idOS API
                    $buffer = array_merge($buffer, $json);
                    printf('Uploading %d new items (%d total)', count($json), count($buffer));
                    echo PHP_EOL;
                    $sdk
                        ->Profile($this->userName)
                        ->Source($this->sourceId)
                        ->Raw
                        ->createNew(
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
