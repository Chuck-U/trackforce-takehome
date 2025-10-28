<?php

namespace App\Contracts;

use App\Models\Employee;

interface EmployeeRepositoryInterface
{
    /**
     * Find employee by provider and employee ID
     *
     * @param string $provider
     * @param string $employeeId
     * @return Employee|null
     */
    public function findByProviderAndId(string $provider, string $employeeId): ?Employee;

    /**
     * Create a new employee
     *
     * @param array $data
     * @return Employee
     */
    public function create(array $data): Employee;

    /**
     * Update an existing employee
     *
     * @param Employee $employee
     * @param array $data
     * @return Employee
     */
    public function update(Employee $employee, array $data): Employee;
}
