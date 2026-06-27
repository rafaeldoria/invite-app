<?php

namespace App\Http\Requests\Events;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventShareMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event && $this->user()?->can('update', $event) === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'share_message' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('events.attributes');
    }

    public function shareMessage(): ?string
    {
        $value = $this->validated('share_message');

        return is_string($value) && $value !== '' ? $value : null;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('share_message')) {
            return;
        }

        $value = $this->input('share_message');

        if (is_string($value)) {
            $value = trim($value);
        }

        $this->merge([
            'share_message' => $value === '' ? null : $value,
        ]);
    }
}
