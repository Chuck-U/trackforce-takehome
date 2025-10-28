<?php

namespace App\Domain\DataTransferObjects;

readonly class Provider2EmployeeData
{
    public function __construct(
        public string $employeeNumber,
        public string $givenName,
        public string $familyName,
        public string $email,
        public ?string $mobile = null,
        public ?string $role = null,
        public ?string $division = null,
        public ?string $startDate = null,
        public string $currentStatus = 'employed',
    ) {}

    /**
     * Create from array (typically from request)
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $personalInfo = $data['personal_info'] ?? [];
        $workInfo = $data['work_info'] ?? [];

        return new self(
            employeeNumber: $data['employee_number'],
            givenName: $personalInfo['given_name'] ?? '',
            familyName: $personalInfo['family_name'] ?? '',
            email: $personalInfo['email'] ?? '',
            mobile: $personalInfo['mobile'] ?? null,
            role: $workInfo['role'] ?? null,
            division: $workInfo['division'] ?? null,
            startDate: $workInfo['start_date'] ?? null,
            currentStatus: $workInfo['current_status'] ?? 'employed',
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
            'employee_number' => $this->employeeNumber,
            'personal_info' => [
                'given_name' => $this->givenName,
                'family_name' => $this->familyName,
                'email' => $this->email,
                'mobile' => $this->mobile,
            ],
            'work_info' => [
                'role' => $this->role,
                'division' => $this->division,
                'start_date' => $this->startDate,
                'current_status' => $this->currentStatus,
            ],
        ];
    }
}

