<?php

namespace App\Exceptions;

use Exception;

class InvalidProviderException extends Exception
{
    public function __construct(string $provider)
    {
        parent::__construct("Invalid provider: {$provider}. Supported providers are: " . implode(', ', \App\Enums\Provider::values()));
    }
}
