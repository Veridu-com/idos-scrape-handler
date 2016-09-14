<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Facebook;

use idOS\SDK;

class Photos extends AbstractFacebookThread {
    /**
     * {@inheritdoc}
     */
    public function execute(SDK $sdk) : bool {
        try {
            $buffer = [];
            foreach ($this->fetchAll('/me/photos', 'fields=created_time,from,height,icon,images,link,name,picture,source,updated_time,width,tags,likes,comments') as $json) {
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
                            'photos',
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
