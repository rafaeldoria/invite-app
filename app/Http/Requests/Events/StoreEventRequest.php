<?php

namespace App\Http\Requests\Events;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEventRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'description' => is_string($this->input('description')) ? trim($this->input('description')) : $this->input('description'),
            'location' => is_string($this->input('location')) ? trim($this->input('location')) : $this->input('location'),
            'theme' => is_string($this->input('theme')) ? trim($this->input('theme')) : $this->input('theme'),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->hasVerifiedEmail() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:120'],
            'description' => ['required', 'string', 'min:1', 'max:2000'],
            'starts_date' => ['required', 'date_format:Y-m-d'],
            'starts_time' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'timezone'],
            'location' => ['required', 'string', 'min:1', 'max:255'],
            'theme' => ['nullable', 'string', 'max:80'],
            'cover_image' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'],
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

                if ($startsAt->lessThanOrEqualTo(now())) {
                    $validator->errors()->add('starts_date', __('events.validation.future_start'));
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function eventAttributes(): array
    {
        $validated = $this->validated();

        return [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'starts_at' => $this->parseLocalStart()?->setTimezone('UTC'),
            'timezone' => $validated['timezone'],
            'location' => $validated['location'],
            'theme' => filled($validated['theme'] ?? null) ? $validated['theme'] : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('events.attributes');
    }

    protected function parseLocalStart(): ?CarbonImmutable
    {
        $timezone = $this->string('timezone')->toString();
        $value = $this->string('starts_date')->toString().' '.$this->string('starts_time')->toString();

        try {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $value, new DateTimeZone($timezone));
        } catch (\Throwable) {
            return null;
        }

        if ($date === false || $date->format('Y-m-d H:i') !== $value) {
            return null;
        }

        return CarbonImmutable::instance($date);
    }
}
