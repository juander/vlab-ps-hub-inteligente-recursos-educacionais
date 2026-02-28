<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmartAssistTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_description_using_gemini(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'description' => 'Curso focado em educação financeira aplicada.',
                                        'tags' => ['finanças', 'matemática', 'investimentos'],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => [
                    'totalTokenCount' => 150,
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/resources/smart-assist', [
            'title' => 'Matemática Financeira',
            'type' => 'curso',
            'url' => 'https://example.com/curso',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.description', 'Curso focado em educação financeira aplicada.')
            ->assertJsonPath('data.tags.0', 'finanças');

        Http::assertSent(function (Request $request): bool {
            $prompt = data_get($request->data(), 'contents.0.parts.0.text', '');

            return is_string($prompt)
                && str_contains($prompt, 'Título: Matemática Financeira')
                && str_contains($prompt, 'Tipo: curso')
                && str_contains($prompt, 'URL: https://example.com/curso');
        });

        $this->assertDatabaseHas('ai_request_logs', [
            'resource_title' => 'Matemática Financeira',
            'status' => 'success',
            'tokens_used' => 150,
        ]);
    }

    public function test_it_returns_error_when_gemini_fails(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'unavailable'], 500),
        ]);

        $response = $this->postJson('/api/v1/resources/smart-assist', [
            'title' => 'Matemática Financeira',
            'type' => 'curso',
            'url' => 'https://example.com/curso',
        ]);

        $response->assertStatus(502)
            ->assertJsonStructure(['error']);

        $this->assertDatabaseHas('ai_request_logs', [
            'resource_title' => 'Matemática Financeira',
            'status' => 'error',
        ]);
    }

    public function test_it_validates_required_fields_for_smart_assist(): void
    {
        $response = $this->postJson('/api/v1/resources/smart-assist', [
            'title' => 'Matemática Financeira',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'url']);
    }

    public function test_it_returns_error_when_ai_returns_less_than_three_tags(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'description' => 'Descrição curta.',
                                        'tags' => ['finanças', 'matemática'],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => [
                    'totalTokenCount' => 120,
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/resources/smart-assist', [
            'title' => 'Matemática Financeira',
            'type' => 'curso',
            'url' => 'https://example.com/curso',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'JSON inválido: retorne exatamente 3 tags válidas.');
    }
}
