<?php

namespace Tests\Fakes;

use App\Ai\AiClient;
use App\Ai\AiException;

/** Stands in for OmniRoute in tests. Records every prompt it receives. */
class FakeAiClient extends AiClient
{
    public array $calls = [];

    /** @param array<int, array|string> $responses queue of JSON arrays, or the string 'BAD' to simulate invalid JSON */
    public function __construct(public array $responses = []) {}

    public function configured(): bool
    {
        return true;
    }

    public function json(string $system, array $content, ?string $model = null): array
    {
        $this->calls[] = ['system' => $system, 'content' => $content];
        $next = array_shift($this->responses) ?? [];
        if ($next === 'BAD') {
            throw new AiException('The AI did not return valid JSON.');
        }
        return ['data' => $next, 'model' => 'fake-model', 'usage' => ['in' => 1000, 'out' => 200]];
    }

    public function transcribe(string $absolutePath, string $filename): array
    {
        $this->calls[] = ['transcribe' => $filename];
        return ['text' => 'عايز السعر النهارده والنقل للمصنع في العاشر', 'seconds' => 24];
    }

    public function sentText(): string
    {
        return json_encode($this->calls, JSON_UNESCAPED_UNICODE);
    }
}
