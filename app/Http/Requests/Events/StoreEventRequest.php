<?php

namespace App\Http\Requests\Events;

use Illuminate\Validation\Validator;

class StoreEventRequest extends EventFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasVerifiedEmail() === true;
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

                if ($startsAt->lessThanOrEqualTo(now())) {
                    $validator->errors()->add('starts_date', __('events.validation.future_start'));
                }
            },
        ];
    }
}
