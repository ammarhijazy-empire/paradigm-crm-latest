<?php

namespace App\Exceptions;

use RuntimeException;

/** A business rule refused the action. Carries translated messages for the user. */
class WorkflowException extends RuntimeException
{
    public function __construct(public readonly array $problems, int $code = 422)
    {
        parent::__construct(implode(' · ', $problems), $code);
    }

    public static function one(string $key, array $params = []): self
    {
        return new self([__($key, $params)]);
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage(), 'problems' => $this->problems], 422);
        }
        return back()->withInput()->withErrors(['workflow' => $this->problems]);
    }
}
