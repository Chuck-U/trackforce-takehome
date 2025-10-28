<?php

namespace App\Services\Mapping;

class Provider2StatusMapper implements StatusMapperInterface
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
            'employed' => 'active',
            'terminated' => 'terminated',
            'on_leave' => 'inactive',
            default => 'active',
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
