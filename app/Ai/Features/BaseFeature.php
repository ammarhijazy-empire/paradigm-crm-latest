<?php

namespace App\Ai\Features;

use App\Ai\AiClient;
use App\Ai\AiException;
use App\Ai\AiGuard;
use App\Models\AiRun;

abstract class BaseFeature
{
    public const VERSION = 'v1';

    public function __construct(protected AiClient $ai, protected AiGuard $guard) {}

    abstract protected function feature(): string;

    protected function prompt(array $vars = []): string
    {
        $text = file_get_contents(resource_path('prompts/'.$this->feature().'.'.static::VERSION.'.md'));
        foreach ($vars + ['today' => now(config('app.display_timezone'))->toDateString()] as $k => $v) {
            $text = str_replace('{{'.$k.'}}', (string) $v, $text);
        }
        return $text;
    }

    /** Runs $work inside an ai_runs row: status, tokens, cost and errors are always recorded. */
    protected function run(AiRun $run, callable $work): AiRun
    {
        $run->update(['status' => 'running', 'started_at' => now(), 'attempts' => $run->attempts + 1, 'prompt_version' => static::VERSION]);
        try {
            $this->guard->check($this->feature());
            [$output, $usage, $model, $audio] = $work() + [3 => 0];
            $run->update([
                'status' => 'done', 'output' => $output, 'model' => $model,
                'tokens_in' => $usage['in'] ?? 0, 'tokens_out' => $usage['out'] ?? 0,
                'cost_usd' => $this->ai->cost($usage['in'] ?? 0, $usage['out'] ?? 0, $audio), 'finished_at' => now(), 'error' => null,
            ]);
        } catch (AiException $e) {
            $run->update(['status' => 'failed', 'error' => $e->getMessage(), 'finished_at' => now()]);
            throw $e;
        }
        return $run;
    }

    protected static function str(mixed $v, int $max = 255): ?string
    {
        if (! is_scalar($v) || trim((string) $v) === '') {
            return null;
        }
        return mb_substr(strip_tags(trim((string) $v)), 0, $max);
    }

    protected static function num(mixed $v): ?float
    {
        return is_numeric($v) && $v >= 0 ? round((float) $v, 2) : null;
    }

    protected static function int(mixed $v): ?int
    {
        return is_numeric($v) && $v >= 0 ? (int) $v : null;
    }

    protected static function date(mixed $v): ?string
    {
        return is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : null;
    }

    protected static function oneOf(mixed $v, array $allowed): ?string
    {
        return is_string($v) && in_array($v, $allowed, true) ? $v : null;
    }
}
