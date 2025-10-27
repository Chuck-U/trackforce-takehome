<?php

namespace App\Services;

class Provider1EmployeeMapper implements EmployeeMapperInterface
{
    /**
     * Map Provider 1 employee data to TrackTik schema
     *
     * @param array<string, mixed> $providerData
     * @return array<string, mixed>
     */
    public function mapToTrackTik(array $providerData): array
    {
        return [
            'employeeId' => $providerData['emp_id'],
            'firstName' => $providerData['first_name'],
            'lastName' => $providerData['last_name'],
            'email' => $providerData['email_address'],
            'phoneNumber' => $providerData['phone'] ?? null,
            'position' => $providerData['job_title'] ?? null,
            'department' => $providerData['dept'] ?? null,
            'startDate' => $providerData['hire_date'] ?? null,
            'status' => $this->mapStatus($providerData['employment_status'] ?? 'active'),
        ];
    }

    /**
     * Map provider status to TrackTik status
     */
    private function mapStatus(string $providerStatus): string
    {
        return match ($providerStatus) {
            'active' => 'active',
            'inactive' => 'inactive',
            'terminated' => 'terminated',
            default => 'active',
        };
    }
}

