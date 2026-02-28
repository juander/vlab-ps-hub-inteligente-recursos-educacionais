<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResourceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $tags = collect($this->input('tags', []))
            ->filter(fn ($tag): bool => is_string($tag))
            ->map(fn (string $tag): string => trim($tag))
            ->filter(fn (string $tag): bool => $tag !== '')
            ->values()
            ->all();

        $this->merge([
            'title' => is_string($this->input('title')) ? trim((string) $this->input('title')) : $this->input('title'),
            'description' => is_string($this->input('description')) ? trim((string) $this->input('description')) : $this->input('description'),
            'type' => is_string($this->input('type')) ? trim((string) $this->input('type')) : $this->input('type'),
            'url' => is_string($this->input('url')) ? trim((string) $this->input('url')) : $this->input('url'),
            'tags' => $tags,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type' => ['required', 'string', 'max:100'],
            'url' => ['required', 'url', 'max:2048'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:80', 'distinct'],
        ];
    }
}
