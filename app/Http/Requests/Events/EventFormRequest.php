<?php

namespace App\Http\Requests\Events;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Throwable;

abstract class EventFormRequest extends FormRequest
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
            'cover_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
            ...$this->additionalRules(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function additionalRules(): array
    {
        return [];
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
        } catch (Throwable) {
            return null;
        }

        if ($date === false || $date->format('Y-m-d H:i') !== $value) {
            return null;
        }

        return CarbonImmutable::instance($date);
    }
}
