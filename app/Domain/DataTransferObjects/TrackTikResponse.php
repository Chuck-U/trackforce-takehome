<?php

namespace App\Domain\DataTransferObjects;

readonly class TrackTikResponse
{
    public function __construct(
        public bool $success,
        public ?array $data = null,
        public ?string $error = null
    ) {}

    /**
     * Create successful response
     *
     * @param array $data
     * @return self
     */
    public static function success(array $data): self
    {
        return new self(success: true, data: $data);
    }

    /**
     * Create error response
     *
     * @param string $error
     * @return self
     */
    public static function error(string $error): self
    {
        return new self(success: false, error: $error);
    }

    /**
     * Convert to array format for backward compatibility
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    public function toArray(): array
    {
        $result = ['success' => $this->success];
        
        if ($this->data !== null) {
            $result['data'] = $this->data;
        }
        
        if ($this->error !== null) {
            $result['error'] = $this->error;
        }
        
        return $result;
    }
}
