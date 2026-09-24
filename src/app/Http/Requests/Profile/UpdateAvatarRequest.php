<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shape check for attaching an avatar: the client sends the ULID of media it already uploaded and
 * processed through the pipeline. Ownership and collection are enforced by the MediaLibrary on
 * attach — this only validates the identifier's shape.
 */
class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'media_ulid' => ['required', 'string', 'size:26', 'regex:/^[0-9A-HJKMNP-TV-Za-hjkmnp-tv-z]{26}$/'],
        ];
    }
}
