<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace App\Validator;

use Respect\Validation\Validator;

/**
 * Schedule Validation Rules.
 */
class Schedule implements ValidatorInterface {
    /**
     * Asserts a valid name, 1-50 chars long, alpha numeric, no white spaces.
     *
     * @param mixed $userName
     *
     * @throws \Respect\Validation\Exceptions\ExceptionInterface
     *
     * @return void
     */
    public function assertUserName($userName) {
        Validator::alnum()
            ->noWhitespace()
            ->length(1, 50)
            ->assert($userName);
    }

    /**
     * Asserts a valid id, digit.
     *
     * @param mixed $id
     *
     * @throws \Respect\Validation\Exceptions\ExceptionInterface
     *
     * @return void
     */
    public function assertId($id) {
        Validator::digit()
            ->assert($id);
    }

    /**
     * Asserts a valid (1-15 chars long) name.
     *
     * @param mixed $name
     *
     * @throws \Respect\Validation\Exceptions\ExceptionInterface
     *
     * @return void
     */
    public function assertName($name) {
        Validator::prnt()
            ->length(1, 15)
            ->assert($name);
    }

    /**
     * Asserts a valid token.
     *
     * @param mixed $token
     *
     * @throws \Respect\Validation\Exceptions\ExceptionInterface
     *
     * @return void
     */
    public function assertToken($token) {
        Validator::prnt()
            ->assert($token);
    }

    /**
     * Asserts a valid token (optional).
     *
     * @param mixed $token
     *
     * @throws \Respect\Validation\Exceptions\ExceptionInterface
     *
     * @return void
     */
    public function assertOptionalToken($token) {
        Validator::optional(
            Validator::prnt()
        )->assert($token);
    }

    /**
     * Asserts a valid version number (optional).
     *
     * @param mixed $version
     *
     * @throws \Respect\Validation\Exceptions\ExceptionInterface
     *
     * @return void
     */
    public function assertOptionalVersion($version) {
        Validator::optional(
            Validator::regex('/^((?:(\d+)\.)?(?:(\d+)\.)?(\*|\d+)|)$/')
        )->assert($version);
    }

    /**
     * Asserts a valid API Key.
     *
     * @param mixed $key
     *
     * @throws \Respect\Validation\Exceptions\ExceptionInterface
     *
     * @return void
     */
    public function assertKey($key) {
        Validator::regex('/[a-zA-Z0-9]+/')
            ->length(1, 32)
            ->assert($key);
    }
}
