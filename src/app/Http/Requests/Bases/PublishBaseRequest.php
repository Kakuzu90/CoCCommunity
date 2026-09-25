<?php

namespace App\Http\Requests\Bases;

use App\Domain\Bases\Data\PublishBaseData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class PublishBaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('publish-base');
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:'.config('bases.title_max')],
            'description' => ['nullable', 'string', 'max:'.config('bases.description_max')],
            'th_level' => ['required', 'integer', 'between:'.config('bases.th_min').','.config('bases.th_max')],
            'category' => ['required', Rule::in(array_keys(config('bases.categories')))],
            'base_link' => ['required', 'string', 'max:2048'],
            'visibility' => ['required', Rule::in(array_keys(config('bases.visibilities')))],
            'tags_text' => ['nullable', 'string', 'max:300'],
            'screenshots' => ['array', 'max:'.config('bases.screenshots_max')],
            'screenshots.*' => ['required', 'ulid', 'distinct'],
            'coc_account_id' => ['nullable', 'integer'],
        ];
    }

    public function publishData(): PublishBaseData
    {
        $data = $this->validated();

        return new PublishBaseData(
            title: $data['title'],
            description: $data['description'] ?? null,
            thLevel: (int) $data['th_level'],
            category: $data['category'],
            baseLink: $data['base_link'],
            visibility: $data['visibility'],
            tags: array_values(array_filter(array_map('trim', explode(',', $data['tags_text'] ?? '')))),
            screenshots: $data['screenshots'] ?? [],
            video: null,
            cocAccountId: isset($data['coc_account_id']) ? (int) $data['coc_account_id'] : null,
        );
    }
}
