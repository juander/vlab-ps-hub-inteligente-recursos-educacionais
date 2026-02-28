<?php

namespace App\Services;

use App\Exceptions\GeminiException;
use App\Models\AiRequestLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private const CIRCUIT_FAILURE_KEY = 'gemini:circuit:failures';

    private const CIRCUIT_OPEN_KEY = 'gemini:circuit:open_until';

    public function generateDescriptionAndTags(string $title, string $type, string $url): array
    {
        $startedAt = microtime(true);
        $tokensUsed = 0;

        try {
            $this->guardCircuit();

            $apiKey = (string) config('services.gemini.api_key');
            $model = (string) config('services.gemini.model', 'gemini-2.5-flash');
            if (trim($apiKey) === '') {
                throw new GeminiException('Configuração ausente: defina GEMINI_API_KEY no backend.', 500);
            }

            $endpoint = sprintf(
                '%s/%s:generateContent?key=%s',
                config('services.gemini.endpoint'),
                $model,
                $apiKey,
            );

            $response = Http::timeout(10)
                ->retry(1, 250)
                ->acceptJson()
                ->post($endpoint, [
                    'system_instruction' => [
                        'parts' => [
                            ['text' => $this->buildSystemPrompt()],
                        ],
                    ],
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $this->buildUserPrompt($title, $type, $url)],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            $payload = $response->json();

            if (! $response->successful()) {
                $apiError = Arr::get($payload, 'error.message');
                $message = is_string($apiError) && trim($apiError) !== ''
                    ? sprintf('Falha ao gerar descrição com IA: %s', $apiError)
                    : 'Falha ao gerar descrição com IA.';

                throw new GeminiException($message, 502);
            }

            $tokensUsed = (int) Arr::get($payload, 'usageMetadata.totalTokenCount', 0);
            $generatedText = Arr::get($payload, 'candidates.0.content.parts.0.text');

            if (! is_string($generatedText) || trim($generatedText) === '') {
                throw new GeminiException('Resposta da IA vazia ou inválida.', 422);
            }

            $parsed = json_decode($generatedText, true);
            if (! is_array($parsed)) {
                throw new GeminiException('A IA não retornou JSON válido.', 422);
            }

            $validated = $this->validateJsonResponse($parsed);
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

            $this->registerSuccess($title, $tokensUsed, $latencyMs);

            return $validated;
        } catch (GeminiException $exception) {
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);
            $this->registerFailure($title, $tokensUsed, $latencyMs, $exception->getMessage());
            throw $exception;
        } catch (\Throwable $throwable) {
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);
            $this->registerFailure($title, $tokensUsed, $latencyMs, $throwable->getMessage());
            throw new GeminiException('Erro inesperado ao chamar a IA.', 502);
        }
    }

    private function buildSystemPrompt(): string
    {
        return implode("\n", [
            'Você é um Assistente Pedagógico.',
            'Retorne SOMENTE JSON válido.',
            'Não inclua texto fora do JSON.',
            'A descrição deve ser curta (máx. 2 frases) e prática.',
            'As tags devem ser 3 itens curtos e relevantes.',
            'Formato obrigatório: {"description":"string","tags":["string","string","string"]}.',
        ]);
    }

    private function buildUserPrompt(string $title, string $type, string $url): string
    {
        return sprintf(
            'Gere descrição objetiva e 3 tags para recurso educacional. Título: %s. Tipo: %s. URL: %s.',
            $title,
            $type,
            $url,
        );
    }

    private function validateJsonResponse(array $response): array
    {
        if (! isset($response['description']) || ! is_string($response['description']) || trim($response['description']) === '') {
            throw new GeminiException('JSON inválido: campo description ausente ou inválido.', 422);
        }

        if (! isset($response['tags']) || ! is_array($response['tags']) || count($response['tags']) === 0) {
            throw new GeminiException('JSON inválido: campo tags ausente ou inválido.', 422);
        }

        $tags = [];
        foreach ($response['tags'] as $tag) {
            if (! is_string($tag) || trim($tag) === '') {
                throw new GeminiException('JSON inválido: todas as tags devem ser strings.', 422);
            }

            $tags[] = trim($tag);
        }

        $tags = array_values(array_unique($tags));
        if (count($tags) < 3) {
            throw new GeminiException('JSON inválido: retorne exatamente 3 tags válidas.', 422);
        }

        return [
            'description' => trim($response['description']),
            'tags' => array_slice($tags, 0, 3),
        ];
    }

    private function guardCircuit(): void
    {
        $openUntil = Cache::get(self::CIRCUIT_OPEN_KEY);

        if (is_int($openUntil) && $openUntil > time()) {
            throw new GeminiException('Serviço de IA temporariamente indisponível. Tente novamente em instantes.', 503);
        }
    }

    private function registerSuccess(string $title, int $tokensUsed, int $latencyMs): void
    {
        Cache::forget(self::CIRCUIT_FAILURE_KEY);
        Cache::forget(self::CIRCUIT_OPEN_KEY);

        Log::info('AI Request', [
            'title' => $title,
            'tokens' => $tokensUsed,
            'latency_ms' => $latencyMs,
            'status' => 'success',
        ]);

        AiRequestLog::query()->create([
            'resource_title' => $title,
            'tokens_used' => $tokensUsed,
            'latency_ms' => $latencyMs,
            'status' => 'success',
            'error_message' => null,
        ]);
    }

    private function registerFailure(string $title, int $tokensUsed, int $latencyMs, string $error): void
    {
        $failures = (int) Cache::get(self::CIRCUIT_FAILURE_KEY, 0) + 1;
        Cache::put(self::CIRCUIT_FAILURE_KEY, $failures, now()->addMinutes(10));

        if ($failures >= 3) {
            Cache::put(self::CIRCUIT_OPEN_KEY, time() + 60, now()->addSeconds(60));
        }

        Log::error('AI Request', [
            'title' => $title,
            'tokens' => $tokensUsed,
            'latency_ms' => $latencyMs,
            'status' => 'error',
            'error' => $error,
        ]);

        AiRequestLog::query()->create([
            'resource_title' => $title,
            'tokens_used' => $tokensUsed,
            'latency_ms' => $latencyMs,
            'status' => 'error',
            'error_message' => $error,
        ]);
    }
}
