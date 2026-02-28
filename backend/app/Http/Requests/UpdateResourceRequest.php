<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResourceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $payload = [];

        if ($this->has('title') && is_string($this->input('title'))) {
            $payload['title'] = trim((string) $this->input('title'));
        }

        if ($this->has('description') && is_string($this->input('description'))) {
            $payload['description'] = trim((string) $this->input('description'));
        }

        if ($this->has('type') && is_string($this->input('type'))) {
            $payload['type'] = trim((string) $this->input('type'));
        }

        if ($this->has('url') && is_string($this->input('url'))) {
            $payload['url'] = trim((string) $this->input('url'));
        }

        if ($this->has('tags')) {
            $payload['tags'] = collect($this->input('tags', []))
                ->filter(fn ($tag): bool => is_string($tag))
                ->map(fn (string $tag): string => trim($tag))
                ->filter(fn (string $tag): bool => $tag !== '')
                ->values()
                ->all();
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'type' => ['sometimes', 'required', 'string', 'max:100'],
            'url' => ['sometimes', 'required', 'url', 'max:2048'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:80', 'distinct'],
        ];
    }
}
