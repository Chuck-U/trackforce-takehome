<?php

namespace App\Domain\DataTransferObjects;

readonly class Provider1EmployeeData
{
    public function __construct(
        public string $empId,
        public string $firstName,
        public string $lastName,
        public string $emailAddress,
        public ?string $phone = null,
        public ?string $jobTitle = null,
        public ?string $dept = null,
        public ?string $hireDate = null,
        public string $employmentStatus = 'active',
    ) {}

    /**
     * Create from array (typically from request)
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            empId: $data['emp_id'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            emailAddress: $data['email_address'],
            phone: $data['phone'] ?? null,
            jobTitle: $data['job_title'] ?? null,
            dept: $data['dept'] ?? null,
            hireDate: $data['hire_date'] ?? null,
            employmentStatus: $data['employment_status'] ?? 'active',
        );
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'emp_id' => $this->empId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email_address' => $this->emailAddress,
            'phone' => $this->phone,
            'job_title' => $this->jobTitle,
            'dept' => $this->dept,
            'hire_date' => $this->hireDate,
            'employment_status' => $this->employmentStatus,
        ];
    }
}

