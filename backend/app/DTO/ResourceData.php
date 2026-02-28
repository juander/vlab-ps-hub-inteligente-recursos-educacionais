<?php

namespace App\DTO;

class ResourceData
{
    public function __construct(
        public string $title,
        public string $description,
        public string $type,
        public string $url,
        public array $tags = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: trim((string) $data['title']),
            description: trim((string) $data['description']),
            type: trim((string) $data['type']),
            url: trim((string) $data['url']),
            tags: $data['tags'] ?? [],
        );
    }

    public function toResourceAttributes(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'url' => $this->url,
        ];
    }
}
