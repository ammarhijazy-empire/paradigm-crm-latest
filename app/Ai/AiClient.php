<?php

namespace App\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * The single door to the AI gateway (OmniRoute, OpenAI-compatible API).
 * Nothing else in the app talks to an AI provider.
 */
class AiClient
{
    public function configured(): bool
    {
        return (bool) (config('crm.ai.base_url') && config('crm.ai.api_key'));
    }

    /**
     * Ask for a JSON object. $content is a list of OpenAI-style content parts
     * (text and image_url). Returns ['data' => array, 'usage' => [...], 'model' => string].
     */
    public function json(string $system, array $content, ?string $model = null): array
    {
        $this->assertConfigured();
        $model ??= config('crm.ai.model');
        try {
            $res = Http::withToken(config('crm.ai.api_key'))
                ->timeout(config('crm.ai.timeout'))
                ->acceptJson()
                ->post(rtrim(config('crm.ai.base_url'), '/').'/chat/completions', [
                    'model' => $model,
                    'temperature' => 0,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $content],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw AiException::retryable('AI gateway unreachable: '.$e->getMessage(), $e);
        }
        if ($res->failed()) {
            $msg = 'AI gateway error '.$res->status().': '.mb_substr($res->body(), 0, 400);
            throw ($res->status() === 429 || $res->serverError()) ? AiException::retryable($msg) : new AiException($msg);
        }
        $text = (string) data_get($res->json(), 'choices.0.message.content', '');
        $data = $this->decode($text);
        return [
            'data' => $data,
            'model' => (string) data_get($res->json(), 'model', $model),
            'usage' => [
                'in' => (int) data_get($res->json(), 'usage.prompt_tokens', 0),
                'out' => (int) data_get($res->json(), 'usage.completion_tokens', 0),
            ],
        ];
    }

    /** Speech to text for voice notes. Returns ['text' => string, 'seconds' => float]. */
    public function transcribe(string $absolutePath, string $filename): array
    {
        $this->assertConfigured();
        $res = Http::withToken(config('crm.ai.api_key'))
            ->timeout(config('crm.ai.timeout'))
            ->attach('file', fopen($absolutePath, 'r'), $filename)
            ->post(rtrim(config('crm.ai.base_url'), '/').'/audio/transcriptions', [
                'model' => config('crm.ai.transcribe_model'),
                'response_format' => 'verbose_json',
            ]);
        if ($res->failed()) {
            throw new AiException('Transcription error '.$res->status().': '.mb_substr($res->body(), 0, 300));
        }
        return ['text' => (string) $res->json('text', ''), 'seconds' => (float) $res->json('duration', 0)];
    }

    public function cost(int $in, int $out, float $audioSeconds = 0): float
    {
        return round($in / 1e6 * config('crm.ai.price_in_per_m') + $out / 1e6 * config('crm.ai.price_out_per_m') + $audioSeconds / 60 * config('crm.ai.price_per_audio_minute'), 4);
    }

    private function decode(string $text): array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text);
        $data = json_decode($text, true);
        if (! is_array($data)) {
            throw new AiException('The AI did not return valid JSON.');
        }
        return $data;
    }

    private function assertConfigured(): void
    {
        if (! $this->configured()) {
            throw new AiException('AI is not configured (AI_BASE_URL / AI_API_KEY).');
        }
    }
}
