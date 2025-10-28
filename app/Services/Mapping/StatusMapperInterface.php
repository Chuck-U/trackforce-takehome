<?php

namespace App\Services\Mapping;

interface StatusMapperInterface
{
    /**
     * Map provider status to TrackTik status
     *
     * @param string $providerStatus
     * @return string
     */
    public function mapToTrackTik(string $providerStatus): string;

    /**
     * Map provider status to database status
     *
     * @param string $providerStatus
     * @return string
     */
    public function mapToDatabase(string $providerStatus): string;
}
