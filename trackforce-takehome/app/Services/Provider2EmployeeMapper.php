<?php

namespace App\Services;

class Provider2EmployeeMapper implements EmployeeMapperInterface
{
    /**
     * Map Provider 2 employee data to TrackTik schema
     *
     * @param array<string, mixed> $providerData
     * @return array<string, mixed>
     */
    public function mapToTrackTik(array $providerData): array
    {
        $personalInfo = $providerData['personal_info'] ?? [];
        $workInfo = $providerData['work_info'] ?? [];

        return [
            'employeeId' => $providerData['employee_number'],
            'firstName' => $personalInfo['given_name'] ?? '',
            'lastName' => $personalInfo['family_name'] ?? '',
            'email' => $personalInfo['email'] ?? '',
            'phoneNumber' => $personalInfo['mobile'] ?? null,
            'position' => $workInfo['role'] ?? null,
            'department' => $workInfo['division'] ?? null,
            'startDate' => $workInfo['start_date'] ?? null,
            'status' => $this->mapStatus($workInfo['current_status'] ?? 'active'),
        ];
    }

    /**
     * Map provider status to TrackTik status
     */
    private function mapStatus(string $providerStatus): string
    {
        return match ($providerStatus) {
            'employed' => 'active',
            'terminated' => 'terminated',
            'on_leave' => 'inactive',
            default => 'active',
        };
    }
}

