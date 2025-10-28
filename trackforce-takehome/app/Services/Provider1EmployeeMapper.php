<?php

namespace App\Services;

use App\Domain\DataTransferObjects\Provider1EmployeeData;
use App\Domain\DataTransferObjects\TrackTikEmployeeData;
use App\Services\Mapping\StatusMapperInterface;

readonly class Provider1EmployeeMapper implements EmployeeMapperInterface
{
    public function __construct(
        private StatusMapperInterface $statusMapper
    ) {}

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
            status: $this->statusMapper->mapToTrackTik($providerData->employmentStatus),
            phoneNumber: $providerData->phone,
            position: $providerData->jobTitle,
            department: $providerData->dept,
            startDate: $providerData->hireDate,
        );
    }
}

