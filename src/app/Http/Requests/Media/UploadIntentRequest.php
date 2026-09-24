<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // Only collections configured for this phase accept uploads; per-collection size/mime
        // limits are enforced in the service against config('media.collections').
        $collections = array_keys(config('media.collections'));

        return [
            'collection' => ['required', 'string', Rule::in($collections)],
            'filename' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1'],
            'mime' => ['required', 'string', 'max:100'],
        ];
    }
}
