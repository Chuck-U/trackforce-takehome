<?php

namespace App\Services\Provider;

class ProviderValidationRules
{
    /**
     * Get validation rules for a given provider key.
     *
     * @param string $provider
     * @return array<string, array<int, string>>
     */
    public static function rulesFor(string $provider): array
    {
        return match (self::normalizeProvider($provider)) {
            'provider1' => [
                'emp_id' => ['required', 'string'],
                'first_name' => ['required', 'string'],
                'last_name' => ['required', 'string'],
                'email_address' => ['required', 'email'],
                'phone' => ['nullable', 'string'],
                'job_title' => ['nullable', 'string'],
                'dept' => ['nullable', 'string'],
                'hire_date' => ['nullable', 'date'],
                'employment_status' => ['nullable', 'in:active,inactive,terminated'],
            ],
            'provider2' => [
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
            ],
            default => throw new \InvalidArgumentException('Unknown provider: ' . $provider),
        };
    }

    /**
     * Normalize provider string (e.g., Provider1 -> provider1)
     */
    public static function normalizeProvider(string $provider): string
    {
        return strtolower(trim($provider));
    }
}


