<?php

namespace App\Services\Mapping;

class Provider1StatusMapper implements StatusMapperInterface
{
    /**
     * Map provider status to TrackTik status
     *
     * @param string $providerStatus
     * @return string
     */
    public function mapToTrackTik(string $providerStatus): string
    {
        return match ($providerStatus) {
            'active' => 'active',
            'inactive' => 'inactive',
            'terminated' => 'terminated',
            default => 'terminated',
        };
    }

    /**
     * Map provider status to database status
     *
     * @param string $providerStatus
     * @return string
     */
    public function mapToDatabase(string $providerStatus): string
    {
        return $this->mapToTrackTik($providerStatus);
    }
}
