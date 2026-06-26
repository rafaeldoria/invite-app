<?php

namespace App\Http\Requests\Events;

use App\Models\Event;
use Illuminate\Validation\Validator;

class UpdateEventRequest extends EventFormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event && $this->user()?->can('update', $event) === true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function additionalRules(): array
    {
        return [
            'remove_cover_image' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['starts_date', 'starts_time', 'timezone'])) {
                    return;
                }

                $startsAt = $this->parseLocalStart();

                if ($startsAt === null) {
                    $validator->errors()->add('starts_date', __('events.validation.invalid_start'));

                    return;
                }

                $event = $this->route('event');

                if (! $event instanceof Event) {
                    return;
                }

                $submittedExistingStart = $event->starts_at->timestamp === $startsAt->setTimezone('UTC')->timestamp;

                if (! $submittedExistingStart && $startsAt->lessThanOrEqualTo(now())) {
                    $validator->errors()->add('starts_date', __('events.validation.future_start'));
                }
            },
        ];
    }

    public function shouldRemoveCoverImage(): bool
    {
        return $this->boolean('remove_cover_image') && ! $this->hasFile('cover_image');
    }
}
