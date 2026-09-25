<?php

namespace App\Http\Requests\Admin;

use App\Domain\Moderation\Enums\ReasonCode;
use App\Domain\Moderation\Enums\SanctionType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Validates a sanction submission (specs/12 §6, specs/04 §2). The reason is required here *and* at the
 * service layer — this form is the first gate, not the only one. Duration is required and bounded only
 * for the time-boxed sanctions (restriction, suspension); bounds come from config, never inline
 * numbers. The exact per-ability authorization is done in the controller against the resolved target.
 */
class ApplySanctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(SanctionType::class)],
            'reason_code' => ['required', new Enum(ReasonCode::class)],
            'public_reason' => ['required', 'string', 'max:255'],
            'internal_note' => ['nullable', 'string', 'max:2000'],
            'duration_days' => [
                Rule::requiredIf(fn (): bool => $this->sanctionType()?->isTimeBoxed() === true),
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->sanctionType();

            if ($type === null || ! $type->isTimeBoxed()) {
                return;
            }

            $max = (int) config("accounts.sanctions.{$type->value}.max_days");
            $days = $this->input('duration_days');

            if ($max > 0 && is_numeric($days) && (int) $days > $max) {
                $validator->errors()->add('duration_days', "A {$type->label()} may not exceed {$max} days.");
            }
        });
    }

    public function sanctionType(): ?SanctionType
    {
        $value = $this->input('type');

        return is_string($value) ? SanctionType::tryFrom($value) : null;
    }

    public function reasonCode(): ReasonCode
    {
        return ReasonCode::from((string) $this->input('reason_code'));
    }
}
