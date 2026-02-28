<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SmartAssistRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => is_string($this->input('title')) ? trim((string) $this->input('title')) : $this->input('title'),
            'type' => is_string($this->input('type')) ? trim((string) $this->input('type')) : $this->input('type'),
            'url' => is_string($this->input('url')) ? trim((string) $this->input('url')) : $this->input('url'),
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
            'type' => ['required', 'string', 'max:100'],
            'url' => ['required', 'url', 'max:2048'],
        ];
    }
}
