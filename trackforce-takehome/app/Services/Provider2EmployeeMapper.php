<?php

namespace App\Services;

use App\Domain\DataTransferObjects\Provider2EmployeeData;
use App\Domain\DataTransferObjects\TrackTikEmployeeData;

class Provider2EmployeeMapper implements EmployeeMapperInterface
{
    /**
     * Map Provider 2 employee data to TrackTik schema
     *
     * @param Provider2EmployeeData $providerData
     * @return TrackTikEmployeeData
     */
    public function mapToTrackTik(mixed $providerData): TrackTikEmployeeData
    {
        return new TrackTikEmployeeData(
            employeeId: $providerData->employeeNumber,
            firstName: $providerData->givenName,
            lastName: $providerData->familyName,
            email: $providerData->email,
            status: $this->mapStatus($providerData->currentStatus),
            phoneNumber: $providerData->mobile,
            position: $providerData->role,
            department: $providerData->division,
            startDate: $providerData->startDate,
        );
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

