<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Provider2EmployeeRequest extends FormRequest
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
            'employee_number' => ['required', 'string'],
            'personal_info' => ['required', 'array'],
            'personal_info.given_name' => ['required', 'string'],
            'personal_info.family_name' => ['required', 'string'],
            'personal_info.email' => ['required', 'email'],
            'personal_info.mobile' => ['nullable', 'string'],
            'work_info' => ['required', 'array'],
            'work_info.role' => ['nullable', 'string'],
            'work_info.division' => ['nullable', 'string'],
            'work_info.start_date' => ['nullable', 'date'],
            'work_info.current_status' => ['nullable', 'in:employed,terminated,on_leave'],
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

