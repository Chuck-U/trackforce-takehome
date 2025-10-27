<?php

namespace App\Domain\DataTransferObjects;

readonly class TrackTikEmployeeData
{
    public function __construct(
        public string $employeeId,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $status,
        public ?string $phoneNumber = null,
        public ?string $position = null,
        public ?string $department = null,
        public ?string $startDate = null,
    ) {}

    /**
     * Create from array
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: $data['employeeId'],
            firstName: $data['firstName'],
            lastName: $data['lastName'],
            email: $data['email'],
            status: $data['status'],
            phoneNumber: $data['phoneNumber'] ?? null,
            position: $data['position'] ?? null,
            department: $data['department'] ?? null,
            startDate: $data['startDate'] ?? null,
        );
    }

    /**
     * Convert to array for API requests
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'employeeId' => $this->employeeId,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'phoneNumber' => $this->phoneNumber,
            'position' => $this->position,
            'department' => $this->department,
            'startDate' => $this->startDate,
            'status' => $this->status,
        ];
    }

    /**
     * Convert to array excluding null values (for API requests)
     *
     * @return array<string, mixed>
     */
    public function toArrayWithoutNulls(): array
    {
        return array_filter($this->toArray(), fn ($value) => $value !== null);
    }
}

