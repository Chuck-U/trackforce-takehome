<?php

namespace App\Services;

use App\Domain\DataTransferObjects\TrackTikEmployeeData;

interface EmployeeMapperInterface
{
    /**
     * Map provider employee data to TrackTik schema
     *
     * @param mixed $providerData Provider-specific DTO
     * @return TrackTikEmployeeData
     */
    public function mapToTrackTik(mixed $providerData): TrackTikEmployeeData;
}

