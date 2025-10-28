<?php

namespace App\Enums;

use App\Exceptions\InvalidProviderException;

enum Provider: string
{
    case PROVIDER1 = 'provider1';
    case PROVIDER2 = 'provider2';

    /**
     * Get all provider values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get provider values as a pipe-separated string for route constraints.
     */
    public static function routeConstraint(): string
    {
        return implode('|', self::values());
    }

    /**
     * Check if a given string is a valid provider.
     */
    public static function isValid(string $provider): bool
    {
        return in_array(strtolower(trim($provider)), self::values(), true);
    }

    /**
     * Get provider enum from string value.
     *
     * @throws \InvalidArgumentException
     */
    public static function fromString(string $provider): self
    {
        $normalized = strtolower(trim($provider));
        
        foreach (self::cases() as $case) {
            if ($case->value === $normalized) {
                return $case;
            }
        }
        
        throw new InvalidProviderException("Unknown provider: {$provider}");
    }
}
