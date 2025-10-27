<?php

namespace App\Services;

use App\Domain\DataTransferObjects\Provider1EmployeeData;
use App\Domain\DataTransferObjects\TrackTikEmployeeData;

class Provider1EmployeeMapper implements EmployeeMapperInterface
{
    /**
     * Map Provider 1 employee data to TrackTik schema
     *
     * @param Provider1EmployeeData $providerData
     * @return TrackTikEmployeeData
     */
    public function mapToTrackTik(mixed $providerData): TrackTikEmployeeData
    {
        return new TrackTikEmployeeData(
            employeeId: $providerData->empId,
            firstName: $providerData->firstName,
            lastName: $providerData->lastName,
            email: $providerData->emailAddress,
            status: $this->mapStatus($providerData->employmentStatus),
            phoneNumber: $providerData->phone,
            position: $providerData->jobTitle,
            department: $providerData->dept,
            startDate: $providerData->hireDate,
        );
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

