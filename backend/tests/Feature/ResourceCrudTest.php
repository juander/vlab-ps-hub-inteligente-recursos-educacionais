<?php

namespace Tests\Feature;

use App\Models\EducationalResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_performs_complete_crud(): void
    {
        $createPayload = [
            'title' => 'Matemática Financeira',
            'description' => 'Conteúdo introdutório sobre juros compostos.',
            'type' => 'video',
            'url' => 'https://example.com/matematica-financeira',
            'tags' => ['matemática', 'finanças'],
        ];

        $createResponse = $this->postJson('/api/v1/resources', $createPayload);
        $createResponse->assertCreated()->assertJsonPath('data.title', 'Matemática Financeira');

        $resourceId = $createResponse->json('data.id');

        $this->getJson('/api/v1/resources')
            ->assertOk()
            ->assertJsonPath('data.0.id', $resourceId)
            ->assertJsonPath('meta.page', 1);

        $this->putJson("/api/v1/resources/{$resourceId}", [
            'description' => 'Conteúdo atualizado para nível intermediário.',
            'tags' => ['matemática', 'juros'],
        ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Conteúdo atualizado para nível intermediário.');

        $this->deleteJson("/api/v1/resources/{$resourceId}")
            ->assertNoContent();

        $this->assertSoftDeleted('resources', ['id' => $resourceId]);
    }

    public function test_it_updates_without_overwriting_missing_fields(): void
    {
        $resource = EducationalResource::query()->create([
            'title' => 'Física Básica',
            'description' => 'Conceitos iniciais.',
            'type' => 'artigo',
            'url' => 'https://example.com/fisica-basica',
        ]);

        $this->putJson("/api/v1/resources/{$resource->id}", [
            'title' => 'Física Básica Atualizada',
        ])->assertOk();

        $resource->refresh();

        $this->assertSame('Física Básica Atualizada', $resource->title);
        $this->assertSame('Conceitos iniciais.', $resource->description);
    }
}
