<?php

namespace App\Services;

interface EmployeeMapperInterface
{
    /**
     * Map provider employee data to TrackTik schema
     *
     * @param array<string, mixed> $providerData
     * @return array<string, mixed>
     */
    public function mapToTrackTik(array $providerData): array;
}

