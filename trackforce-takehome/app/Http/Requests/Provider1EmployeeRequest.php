<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Provider1EmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'emp_id' => ['required', 'string'],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'email_address' => ['required', 'email'],
            'phone' => ['nullable', 'string'],
            'job_title' => ['nullable', 'string'],
            'dept' => ['nullable', 'string'],
            'hire_date' => ['nullable', 'date'],
            'employment_status' => ['nullable', 'in:active,inactive,terminated'],
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid employee data',
                    'details' => collect($validator->errors()->messages())
                        ->map(fn ($messages, $field) => [
                            'field' => $field,
                            'message' => $messages[0],
                        ])
                        ->values()
                        ->all(),
                ],
            ], 400)
        );
    }
}

