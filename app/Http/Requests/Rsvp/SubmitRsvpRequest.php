<?php

namespace App\Http\Requests\Rsvp;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SubmitRsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $hasNamedCompanions = is_array($this->input('companions'));
        $companions = $this->normalizedCompanions();

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'companions' => $this->input('attendance') === 'declined' ? [] : $companions,
            'adult_companions' => $this->input('attendance') === 'declined' ? 0 : ($hasNamedCompanions ? $this->adultCompanionCount($companions) : $this->input('adult_companions', 0)),
            'child_companions' => $this->input('attendance') === 'declined' ? 0 : ($hasNamedCompanions ? $this->childCompanionCount($companions) : $this->input('child_companions', 0)),
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
            'adult_companions' => ['required', 'integer', 'min:0', 'max:5'],
            'child_companions' => ['required', 'integer', 'min:0', 'max:5'],
            'companions' => ['sometimes', 'array', 'max:5'],
            'companions.*.name' => ['required', 'string', 'min:1', 'max:120'],
            'companions.*.is_child' => ['required', 'boolean'],
            'response_token' => [$this->routeIs('public.rsvp.store') ? 'required' : 'exclude', 'string', 'min:32', 'max:120'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('attendance') !== 'confirmed') {
                    return;
                }

                if (((int) $this->input('adult_companions', 0) + (int) $this->input('child_companions', 0)) > 5) {
                    $validator->errors()->add('companions', __('validation.max.array', [
                        'attribute' => __('rsvp.attributes.companions'),
                        'max' => 5,
                    ]));
                }
            },
        ];
    }

    /**
     * @return array{name?: string, attendance: string, adult_companions: int, child_companions: int, companions: list<array{name: string, is_child: bool}>}
     */
    public function rsvpData(): array
    {
        $validated = $this->validated();
        $companions = [];

        if ($validated['attendance'] === 'confirmed') {
            $companions = array_key_exists('companions', $validated) && $validated['companions'] !== []
                ? array_values($validated['companions'])
                : $this->legacyCompanions((int) $validated['adult_companions'], (int) $validated['child_companions']);
        }

        return array_filter([
            'name' => $validated['name'] ?? null,
            'attendance' => $validated['attendance'],
            'adult_companions' => (int) $validated['adult_companions'],
            'child_companions' => (int) $validated['child_companions'],
            'companions' => array_map(fn (array $companion): array => [
                'name' => $companion['name'],
                'is_child' => (bool) $companion['is_child'],
            ], $companions),
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

    /**
     * @return list<array{name: mixed, is_child: mixed}>
     */
    private function normalizedCompanions(): array
    {
        $input = $this->input('companions');

        if (! is_array($input)) {
            return [];
        }

        return array_map(fn (mixed $companion): array => [
            'name' => is_array($companion) && is_string($companion['name'] ?? null) ? trim($companion['name']) : ($companion['name'] ?? null),
            'is_child' => is_array($companion) ? ($companion['is_child'] ?? false) : false,
        ], array_values($input));
    }

    /**
     * @return list<array{name: string, is_child: bool}>
     */
    private function legacyCompanions(int $adults, int $children): array
    {
        $companions = [];

        for ($adult = 0; $adult < $adults; $adult++) {
            $companions[] = ['name' => 'Adult companion '.($adult + 1), 'is_child' => false];
        }

        for ($child = 0; $child < $children; $child++) {
            $companions[] = ['name' => 'Child companion '.($child + 1), 'is_child' => true];
        }

        return $companions;
    }

    /**
     * @param  list<array{name: mixed, is_child: mixed}>  $companions
     */
    private function adultCompanionCount(array $companions): int
    {
        return count(array_filter($companions, fn (array $companion): bool => ! filter_var($companion['is_child'], FILTER_VALIDATE_BOOL)));
    }

    /**
     * @param  list<array{name: mixed, is_child: mixed}>  $companions
     */
    private function childCompanionCount(array $companions): int
    {
        return count(array_filter($companions, fn (array $companion): bool => filter_var($companion['is_child'], FILTER_VALIDATE_BOOL)));
    }
}
