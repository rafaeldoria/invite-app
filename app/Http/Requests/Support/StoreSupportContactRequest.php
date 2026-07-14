<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSupportContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'contact' => is_string($this->input('contact')) ? trim($this->input('contact')) : $this->input('contact'),
            'subject' => is_string($this->input('subject')) ? preg_replace('/\s+/', ' ', trim($this->input('subject'))) : $this->input('subject'),
            'message' => is_string($this->input('message')) ? trim($this->input('message')) : $this->input('message'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:120'],
            'contact' => ['required', 'string', 'min:5', 'max:255'],
            'subject' => ['required', 'string', 'min:1', 'max:160'],
            'message' => ['required', 'string', 'min:1', 'max:4000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $contact = $this->input('contact');

                if (! is_string($contact) || $this->isValidEmailOrWhatsapp($contact)) {
                    return;
                }

                $validator->errors()->add('contact', __('support.validation.contact'));
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('support.attributes');
    }

    private function isValidEmailOrWhatsapp(string $contact): bool
    {
        if (filter_var($contact, FILTER_VALIDATE_EMAIL) !== false) {
            return true;
        }

        if (! preg_match('/^\+?[0-9\s().-]+$/', $contact)) {
            return false;
        }

        $digits = preg_replace('/\D+/', '', $contact) ?? '';

        return strlen($digits) >= 8 && strlen($digits) <= 15;
    }
}
