<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Utils;

class Backoff {
    protected $enabled;
    protected $interval;
    protected $multiplier;
    protected $counter;
    protected $timer;

    public function __construct(
        bool $enabled = true,
        int $interval = 10,
        float $multiplier = 1.0
    ) {
        $this->enabled    = $enabled;
        $this->interval   = $interval;
        $this->multiplier = $multiplier;
        $this->counter    = 0;
        $this->timer      = time();
    }

    public function enable() : self {
        $this->enabled = true;

        return $this;
    }

    public function disable() : self {
        $this->enabled = false;

        return $this;
    }

    public function isEnabled() : bool {
        return $this->enabled;
    }

    public function setInterval(int $interval) : self {
        if ($interval <= 0) {
            throw new \RuntimeException('Interval must be a positive, non-zero value!');
        }

        $this->interval = $interval;

        return $this;
    }

    public function getInterval() : int {
        return $this->interval;
    }

    public function setMultiplier(float $multiplier) : self {
        if ($multiplier < 0.0) {
            throw new \RuntimeException('Multiplier must be a positive value!');
        }

        $this->multiplier = $multiplier;

        return $this;
    }

    public function getMultiplier() : float {
        return $this->multiplier;
    }

    public function canYield() : bool {
        if ($this->enabled) {
            $interval = (time() - $this->timer);
            $backoff  = ($this->interval * (1 + ($this->multiplier * $this->counter)));

            if ($interval >= $backoff) {
                $this->timer = time();
                $this->counter++;

                return true;
            }
        }

        return false;
    }

    public function mustBackoff() : bool {
        return ! $this->canRetry();
    }
}
