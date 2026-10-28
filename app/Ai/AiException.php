<?php

namespace App\Ai;

use RuntimeException;

class AiException extends RuntimeException
{
    /** True for network / provider errors worth retrying; false for config, limits and bad output. */
    public bool $retryable = false;

    public static function retryable(string $message, ?\Throwable $prev = null): self
    {
        $e = new self($message, 0, $prev);
        $e->retryable = true;
        return $e;
    }
}
