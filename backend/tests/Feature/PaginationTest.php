<?php

namespace Tests\Feature;

use App\Models\EducationalResource;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_custom_pagination_format(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            EducationalResource::query()->create([
                'title' => "Recurso {$i}",
                'description' => "Descrição {$i}",
                'type' => 'artigo',
                'url' => "https://example.com/recurso-{$i}",
            ]);
        }

        $response = $this->getJson('/api/v1/resources?per_page=10&page=1');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_it_filters_resources_by_type_with_partial_case_insensitive_match(): void
    {
        EducationalResource::query()->create([
            'title' => 'Video de Matemática',
            'description' => 'Aula em vídeo.',
            'type' => 'Video Aula',
            'url' => 'https://example.com/video-matematica',
        ]);

        EducationalResource::query()->create([
            'title' => 'Artigo de Física',
            'description' => 'Texto técnico.',
            'type' => 'Artigo',
            'url' => 'https://example.com/artigo-fisica',
        ]);

        $response = $this->getJson('/api/v1/resources?type=video');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'Video Aula');
    }

    public function test_it_filters_resources_by_tag(): void
    {
        $resourceWithTag = EducationalResource::query()->create([
            'title' => 'Juros Compostos',
            'description' => 'Conceitos fundamentais.',
            'type' => 'video',
            'url' => 'https://example.com/juros-compostos',
        ]);

        $resourceWithoutTag = EducationalResource::query()->create([
            'title' => 'Introdução à Química',
            'description' => 'Conceitos básicos.',
            'type' => 'artigo',
            'url' => 'https://example.com/quimica',
        ]);

        $financasTag = Tag::query()->create(['name' => 'financas']);
        $quimicaTag = Tag::query()->create(['name' => 'quimica']);

        $resourceWithTag->tags()->sync([$financasTag->id]);
        $resourceWithoutTag->tags()->sync([$quimicaTag->id]);

        $response = $this->getJson('/api/v1/resources?tag=fin');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Juros Compostos');
    }

    public function test_it_filters_resources_by_title(): void
    {
        EducationalResource::query()->create([
            'title' => 'Matemática Financeira',
            'description' => 'Curso sobre juros.',
            'type' => 'video',
            'url' => 'https://example.com/matematica',
        ]);

        EducationalResource::query()->create([
            'title' => 'História do Brasil',
            'description' => 'Conteúdo de história.',
            'type' => 'artigo',
            'url' => 'https://example.com/historia',
        ]);

        $response = $this->getJson('/api/v1/resources?title=matemática');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Matemática Financeira');
    }
}
