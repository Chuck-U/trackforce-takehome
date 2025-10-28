<?php

namespace App\Services\Provider;

use App\Domain\DataTransferObjects\Provider1EmployeeData;
use App\Domain\DataTransferObjects\Provider2EmployeeData;
use App\Enums\Provider;
use App\Exceptions\InvalidProviderException;
use App\Services\EmployeeMapperInterface;
use App\Services\Mapping\Provider1StatusMapper;
use App\Services\Mapping\Provider2StatusMapper;
use App\Services\Mapping\StatusMapperInterface;
use App\Services\Provider1EmployeeMapper;
use App\Services\Provider2EmployeeMapper;

class ProviderResolver
{
    /**
     * Validate provider key.
     *
     * @throws InvalidProviderException
     */
    public function validateProvider(string $provider): void
    {
        if (!Provider::isValid($provider)) {
            throw new InvalidProviderException($provider);
        }
    }

    public function normalizeProvider(string $provider): string
    {
        return strtolower(trim($provider));
    }

    /**
     * Resolve the appropriate mapper for the provider.
     */
    public function resolveMapper(string $provider): EmployeeMapperInterface
    {
        $providerEnum = Provider::fromString($provider);
        
        return match ($providerEnum) {
            Provider::PROVIDER1 => app()->make(Provider1EmployeeMapper::class),
            Provider::PROVIDER2 => app()->make(Provider2EmployeeMapper::class),
        };
    }

    /**
     * Resolve the appropriate status mapper for the provider.
     */
    public function resolveStatusMapper(string $provider): StatusMapperInterface
    {
        $providerEnum = Provider::fromString($provider);
        
        return match ($providerEnum) {
            Provider::PROVIDER1 => app()->make(Provider1StatusMapper::class),
            Provider::PROVIDER2 => app()->make(Provider2StatusMapper::class),
        };
    }

    /**
     * Create provider-specific DTO from array.
     */
    public function createDtoFromArray(string $provider, array $data): mixed
    {
        $providerEnum = Provider::fromString($provider);
        
        return match ($providerEnum) {
            Provider::PROVIDER1 => Provider1EmployeeData::fromArray($data),
            Provider::PROVIDER2 => Provider2EmployeeData::fromArray($data),
        };
    }

    /**
     * Extract the provider-specific employee identifier from validated data.
     */
    public function extractEmployeeId(string $provider, array $validated): string
    {
        $providerEnum = Provider::fromString($provider);
        
        return match ($providerEnum) {
            Provider::PROVIDER1 => $validated['emp_id'] ?? '',
            Provider::PROVIDER2 => $validated['employee_number'] ?? '',
        };
    }

    /**
     * Build unified employee data array for local database storage.
     */
    public function prepareEmployeeData(string $provider, array $validated): array
    {
        $providerEnum = Provider::fromString($provider);
        $statusMapper = $this->resolveStatusMapper($providerEnum->value);

        return match ($providerEnum) {
            Provider::PROVIDER1 => [
                'employee_id' => $validated['emp_id'],
                'provider' => 'provider1',
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email_address'],
                'phone_number' => $validated['phone'] ?? null,
                'position' => $validated['job_title'] ?? null,
                'department' => $validated['dept'] ?? null,
                'start_date' => $validated['hire_date'] ?? null,
                'status' => $statusMapper->mapToDatabase($validated['employment_status'] ?? 'active'),
                'provider_data' => $validated,
            ],
            Provider::PROVIDER2 => (function () use ($validated, $statusMapper) {
                $personal = $validated['personal_info'];
                $work = $validated['work_info'];
                return [
                    'employee_id' => $validated['employee_number'],
                    'provider' => 'provider2',
                    'first_name' => $personal['given_name'],
                    'last_name' => $personal['family_name'],
                    'email' => $personal['email'],
                    'phone_number' => $personal['mobile'] ?? null,
                    'position' => $work['role'] ?? null,
                    'department' => $work['division'] ?? null,
                    'start_date' => $work['start_date'] ?? null,
                    'status' => $statusMapper->mapToDatabase($work['current_status'] ?? 'employed'),
                    'provider_data' => $validated,
                ];
            })(),
        };
    }
}


