<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace App\Exception;

/**
 * Base Application Exception.
 *
 * @apiEndpointResponse 500 schema/error.json
 */
class AppException extends \Exception {
    /**
     * {@inheritdoc}
     */
    protected $code = 500;
    /**
     * {@inheritdoc}
     */
    protected $message = 'Application Internal Error.';
}
