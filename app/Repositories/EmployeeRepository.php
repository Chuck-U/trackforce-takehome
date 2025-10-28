<?php

namespace App\Repositories;

use App\Contracts\EmployeeRepositoryInterface;
use App\Models\Employee;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    /**
     * Find employee by provider and employee ID
     *
     * @param string $provider
     * @param string $employeeId
     * @return Employee|null
     */
    public function findByProviderAndId(string $provider, string $employeeId): ?Employee
    {
        return Employee::where('employee_id', $employeeId)
            ->where('provider', $provider)
            ->first();
    }

    /**
     * Create a new employee
     *
     * @param array $data
     * @return Employee
     */
    public function create(array $data): Employee
    {
        return Employee::create($data);
    }

    /**
     * Update an existing employee
     *
     * @param Employee $employee
     * @param array $data
     * @return Employee
     */
    public function update(Employee $employee, array $data): Employee
    {
        $employee->update($data);
        return $employee->fresh();
    }
}
