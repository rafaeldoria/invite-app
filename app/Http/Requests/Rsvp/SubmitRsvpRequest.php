<?php

namespace App\Http\Requests\Rsvp;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitRsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'adult_companions' => $this->input('attendance') === 'declined' ? 0 : $this->input('adult_companions', 0),
            'child_companions' => $this->input('attendance') === 'declined' ? 0 : $this->input('child_companions', 0),
            'response_token' => is_string($this->input('response_token')) ? trim($this->input('response_token')) : $this->input('response_token'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [$this->routeIs('public.rsvp.store') ? 'required' : 'exclude', 'string', 'min:1', 'max:120'],
            'attendance' => ['required', Rule::in(['confirmed', 'declined'])],
            'adult_companions' => ['required', 'integer', 'min:0', 'max:20'],
            'child_companions' => ['required', 'integer', 'min:0', 'max:20'],
            'response_token' => [$this->routeIs('public.rsvp.store') ? 'required' : 'exclude', 'string', 'min:32', 'max:120'],
        ];
    }

    /**
     * @return array{name?: string, attendance: string, adult_companions: int, child_companions: int}
     */
    public function rsvpData(): array
    {
        $validated = $this->validated();

        return array_filter([
            'name' => $validated['name'] ?? null,
            'attendance' => $validated['attendance'],
            'adult_companions' => (int) $validated['adult_companions'],
            'child_companions' => (int) $validated['child_companions'],
        ], fn (mixed $value): bool => $value !== null);
    }

    public function responseToken(): string
    {
        return (string) $this->validated('response_token');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('rsvp.attributes');
    }
}
