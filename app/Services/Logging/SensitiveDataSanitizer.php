<?php

namespace App\Services\Logging;

class SensitiveDataSanitizer
{
    private array $sensitiveFields = [
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'secret',
        'authorization',
    ];

    /**
     * Sanitize sensitive data from request
     */
    public function sanitize(array $data): array
    {
        foreach ($this->sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }

    /**
     * Add additional sensitive field
     */
    public function addSensitiveField(string $field): void
    {
        if (!in_array($field, $this->sensitiveFields)) {
            $this->sensitiveFields[] = $field;
        }
    }

    /**
     * Get list of sensitive fields
     */
    public function getSensitiveFields(): array
    {
        return $this->sensitiveFields;
    }
}
