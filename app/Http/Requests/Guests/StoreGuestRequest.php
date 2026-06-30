<?php

namespace App\Http\Requests\Guests;

use App\Enums\GuestStatus;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event && $this->user()?->can('update', $event);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'status' => $this->input('status', GuestStatus::Pending->value),
            'adult_companions' => $this->input('adult_companions', 0),
            'child_companions' => $this->input('child_companions', 0),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:120'],
            'status' => ['required', Rule::enum(GuestStatus::class)],
            'adult_companions' => ['required', 'integer', 'min:0', 'max:5'],
            'child_companions' => ['required', 'integer', 'min:0', 'max:5'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('status') !== GuestStatus::Confirmed->value) {
                    return;
                }

                if (((int) $this->input('adult_companions', 0) + (int) $this->input('child_companions', 0)) > 5) {
                    $validator->errors()->add('child_companions', __('validation.max.array', [
                        'attribute' => __('guests.attributes.companions'),
                        'max' => 5,
                    ]));
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function guestAttributes(): array
    {
        $validated = $this->validated();
        $status = GuestStatus::from($validated['status']);

        return [
            'name' => $validated['name'],
            'status' => $status,
            'adult_companions' => $status->allowsCompanions() ? (int) $validated['adult_companions'] : 0,
            'child_companions' => $status->allowsCompanions() ? (int) $validated['child_companions'] : 0,
            'responded_at' => $status === GuestStatus::Pending ? null : now(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('guests.attributes');
    }
}
