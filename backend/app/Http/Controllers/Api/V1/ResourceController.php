<?php

namespace App\Http\Controllers\Api\V1;

use App\DTO\ResourceData;
use App\Http\Controllers\Controller;
use App\Http\Requests\SmartAssistRequest;
use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Models\EducationalResource;
use App\Models\Tag;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ResourceController extends Controller
{
    public function __construct(private readonly GeminiService $geminiService) {}

    public function index(): JsonResponse
    {
        $perPage = max((int) request()->integer('per_page', 10), 1);
        $title = trim((string) request()->query('title', ''));
        $type = trim((string) request()->query('type', ''));
        $tag = trim((string) request()->query('tag', ''));

        $paginator = EducationalResource::query()
            ->with('tags')
            ->when($title !== '', function ($query) use ($title): void {
                $query->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($title).'%']);
            })
            ->when($type !== '', function ($query) use ($type): void {
                $query->whereRaw('LOWER(TRIM(type)) LIKE ?', ['%'.mb_strtolower($type).'%']);
            })
            ->when($tag !== '', function ($query) use ($tag): void {
                $query->whereHas('tags', function ($tagQuery) use ($tag): void {
                    $tagQuery->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($tag).'%']);
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())
                ->map(fn (EducationalResource $resource): array => $this->transformResource($resource))
                ->values(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreResourceRequest $request): JsonResponse
    {
        $dto = ResourceData::fromArray($request->validated());

        $resource = EducationalResource::query()->create($dto->toResourceAttributes());
        $resource->tags()->sync($this->resolveTagIds($dto->tags));
        $resource->load('tags');

        return response()->json([
            'data' => $this->transformResource($resource),
        ], 201);
    }

    public function update(UpdateResourceRequest $request, EducationalResource $resource): JsonResponse
    {
        $payload = $request->validated();
        $tags = $payload['tags'] ?? null;

        unset($payload['tags']);
        $resource->fill($payload);
        $resource->save();

        if (is_array($tags)) {
            $resource->tags()->sync($this->resolveTagIds($tags));
        }

        $resource->load('tags');

        return response()->json([
            'data' => $this->transformResource($resource),
        ]);
    }

    public function destroy(EducationalResource $resource): Response
    {
        $resource->delete();

        return response()->noContent();
    }

    public function smartAssist(SmartAssistRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $response = $this->geminiService->generateDescriptionAndTags(
            title: $validated['title'],
            type: $validated['type'],
            url: $validated['url'],
        );

        return response()->json([
            'data' => $response,
        ]);
    }

    private function resolveTagIds(array $tags): array
    {
        return collect($tags)
            ->filter(fn ($tag): bool => is_string($tag) && trim($tag) !== '')
            ->map(fn ($tag): string => trim($tag))
            ->unique()
            ->map(function (string $name): int {
                return Tag::query()->firstOrCreate(['name' => $name])->id;
            })
            ->values()
            ->all();
    }

    private function transformResource(EducationalResource $resource): array
    {
        return [
            'id' => $resource->id,
            'title' => $resource->title,
            'description' => $resource->description,
            'type' => $resource->type,
            'url' => $resource->url,
            'tags' => $resource->tags->pluck('name')->values()->all(),
            'created_at' => optional($resource->created_at)?->toISOString(),
            'updated_at' => optional($resource->updated_at)?->toISOString(),
        ];
    }
}
