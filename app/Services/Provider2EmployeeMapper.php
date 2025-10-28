<?php

namespace App\Services;

use App\Domain\DataTransferObjects\Provider2EmployeeData;
use App\Domain\DataTransferObjects\TrackTikEmployeeData;
use App\Services\Mapping\StatusMapperInterface;

class Provider2EmployeeMapper implements EmployeeMapperInterface
{
    public function __construct(
        private StatusMapperInterface $statusMapper
    ) {}

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
            status: $this->statusMapper->mapToTrackTik($providerData->currentStatus),
            phoneNumber: $providerData->mobile,
            position: $providerData->role,
            department: $providerData->division,
            startDate: $providerData->startDate,
        );
    }
}

