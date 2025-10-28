<?php

namespace App\Contracts;

use App\Domain\DataTransferObjects\TrackTikEmployeeData;
use App\Domain\DataTransferObjects\TrackTikResponse;

interface TrackTikServiceInterface
{
    /**
     * Create employee in TrackTik
     *
     * @param TrackTikEmployeeData $employeeData
     * @return TrackTikResponse
     */
    public function createEmployee(TrackTikEmployeeData $employeeData): TrackTikResponse;

    /**
     * Update employee in TrackTik
     *
     * @param string $employeeId
     * @param TrackTikEmployeeData $employeeData
     * @return TrackTikResponse
     */
    public function updateEmployee(string $employeeId, TrackTikEmployeeData $employeeData): TrackTikResponse;

    /**
     * Get employee from TrackTik
     *
     * @param string $employeeId
     * @return TrackTikResponse
     */
    public function getEmployee(string $employeeId): TrackTikResponse;
}
